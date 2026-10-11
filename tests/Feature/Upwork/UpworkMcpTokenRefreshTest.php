<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkApiCredential;
use App\Models\UpworkSetting;
use App\Services\Upwork\Api\McpUpworkClient;
use App\Services\Upwork\Api\UpworkMcpOAuth;
use App\Services\Upwork\Api\UpworkTokenRefreshBusy;
use App\Services\Upwork\Notifications\AlertService;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serialized MCP token refresh. Upwork rotates the refresh token on every
 * refresh (verified live, 9 Oct 2026), so only one process may refresh at a
 * time and the others must reuse its result. All responses are mocked.
 */
class UpworkMcpTokenRefreshTest extends UpworkTestCase
{
    use FakesUpworkMcp;

    const TOKEN_URL = 'https://www.upwork.com/api/v3/oauth2/token';

    /** @var int */
    private $tokenRequests = 0;

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Http::swap(new Factory());
        config(['upwork.mcp.refresh_lock_wait_seconds' => 0]); // never block the test suite
    }

    /** Token endpoint returning $respond(form) and counting requests. */
    private function fakeTokenEndpoint(callable $respond): void
    {
        Http::fake(function (Request $r) use ($respond) {
            if ($r->url() !== self::TOKEN_URL) {
                return Http::response('unexpected host', 500);
            }
            $this->tokenRequests++;

            return $respond($r->data());
        });
    }

    private function rotated(string $suffix = '2'): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response(['access_token' => "mcp-access-{$suffix}", 'refresh_token' => "mcp-refresh-{$suffix}", 'token_type' => 'Bearer', 'expires_in' => 86400]);
    }

    private function rawRow(): array
    {
        return (array) DB::table('upwork_api_credentials')->where('label', 'mcp')->first(['access_token', 'refresh_token', 'token_type', 'expires_at', 'refresh_expires_at', 'last_refreshed_at']);
    }

    private function assertLockFree(): void
    {
        $probe = Cache::lock(UpworkMcpOAuth::LOCK_KEY, 5);
        $this->assertTrue($probe->get(), 'refresh lock was released');
        $probe->release();
    }

    // ---- Rotation and persistence ---------------------------------------------------------

    public function test_rotation_persists_every_field_together_and_releases_the_lock(): void
    {
        $this->mcpConnect(['token_type' => 'Bearer', 'last_error' => 'old problem']);
        $this->fakeTokenEndpoint(fn() => $this->rotated());

        $fresh = (new UpworkMcpOAuth())->refresh();

        $stored = UpworkApiCredential::mcp();
        $this->assertSame(['mcp-access-2', 'mcp-refresh-2', 'Bearer'], [$stored->access_token, $stored->refresh_token, $stored->token_type]);
        $this->assertTrue($stored->expires_at->between(now()->addSeconds(86390), now()->addSeconds(86410)), '24-hour access token from expires_in');
        $this->assertTrue($stored->refresh_expires_at->gt(now()->addDays(13)));
        $this->assertTrue($stored->last_refreshed_at->gte(now()->subMinute()));
        $this->assertNull($stored->last_error);
        $this->assertSame($stored->access_token, $fresh->access_token);
        $this->assertStringNotContainsString('mcp-refresh-2', $this->rawRow()['refresh_token'], 'encrypted at rest');
        $this->assertSame(1, $this->tokenRequests);
        $this->assertLockFree();
        Http::assertSent(fn(Request $r) => $r['grant_type'] === 'refresh_token' && $r['refresh_token'] === 'mcp-refresh-1' && $r['client_id'] === 'pms-client');
    }

    // ---- Concurrency ----------------------------------------------------------------------

    public function test_two_processes_refreshing_at_once_send_one_request_and_share_the_result(): void
    {
        $this->mcpConnect();
        $staleCopyOfB = UpworkApiCredential::mcp(); // process B read the credential before A refreshed
        $busyWhileAInFlight = null;

        $this->fakeTokenEndpoint(function () use ($staleCopyOfB, &$busyWhileAInFlight) {
            // Process B tries while A's request is in flight: A holds the lock.
            try {
                (new UpworkMcpOAuth())->refresh($staleCopyOfB);
            } catch (UpworkTokenRefreshBusy $e) {
                $busyWhileAInFlight = $e->getMessage();
            }

            return $this->rotated();
        });

        $a = (new UpworkMcpOAuth())->refresh(UpworkApiCredential::mcp());
        $this->assertSame('Another process is refreshing the Upwork MCP token; try again shortly.', $busyWhileAInFlight);

        // B tries again once A is done: it finds rotated tokens and does not refresh again.
        $b = (new UpworkMcpOAuth())->refresh($staleCopyOfB);

        $this->assertSame(1, $this->tokenRequests, 'only one refresh reached Upwork');
        $this->assertSame(['mcp-access-2', 'mcp-refresh-2'], [$a->access_token, $a->refresh_token]);
        $this->assertSame(['mcp-access-2', 'mcp-refresh-2'], [$b->access_token, $b->refresh_token]);
        $this->assertLockFree();
    }

    public function test_a_process_that_finds_the_credential_already_refreshed_skips(): void
    {
        $this->mcpConnect();
        $stale = UpworkApiCredential::mcp();
        UpworkApiCredential::mcp()->forceFill(['access_token' => 'mcp-access-other', 'refresh_token' => 'mcp-refresh-other', 'expires_at' => now()->addDay()])->save();
        $this->fakeTokenEndpoint(fn() => $this->rotated());

        $result = (new UpworkMcpOAuth())->refresh($stale);

        $this->assertSame(0, $this->tokenRequests, 'no redundant refresh');
        $this->assertSame(['mcp-access-other', 'mcp-refresh-other'], [$result->access_token, $result->refresh_token]);
        $this->assertLockFree();
    }

    // ---- Lock timeout, expiry and cleanup ---------------------------------------------------

    public function test_lock_timeout_leaves_credentials_untouched_and_is_retriable(): void
    {
        $this->mcpConnect();
        $before = $this->rawRow();
        $this->fakeTokenEndpoint(fn() => $this->rotated());
        $other = Cache::lock(UpworkMcpOAuth::LOCK_KEY, UpworkMcpOAuth::LOCK_SECONDS);
        $this->assertTrue($other->get(), 'another process holds the lock');

        try {
            (new UpworkMcpOAuth())->refresh();
            $this->fail('expected UpworkTokenRefreshBusy');
        } catch (UpworkTokenRefreshBusy $e) {
            $this->assertStringContainsString('Another process is refreshing', $e->getMessage());
        }
        $this->assertSame(0, $this->tokenRequests);
        $this->assertSame($before, $this->rawRow());
        $this->assertNull(UpworkApiCredential::mcp()->last_error, 'a busy lock is not an error');

        $other->release();
        $this->assertSame('mcp-access-2', (new UpworkMcpOAuth())->refresh()->access_token, 'retry succeeds once the lock is free');
    }

    public function test_an_abandoned_lock_expires_so_refresh_can_recover(): void
    {
        $this->mcpConnect();
        $this->fakeTokenEndpoint(fn() => $this->rotated());
        Cache::lock(UpworkMcpOAuth::LOCK_KEY, UpworkMcpOAuth::LOCK_SECONDS)->get(); // holder crashed, never released

        $this->travel(UpworkMcpOAuth::LOCK_SECONDS + 1)->seconds();

        $this->assertSame('mcp-access-2', (new UpworkMcpOAuth())->refresh()->access_token);
        $this->assertLockFree();
    }

    public function test_lock_duration_covers_the_http_timeout(): void
    {
        $this->assertGreaterThan(30, UpworkMcpOAuth::LOCK_SECONDS, 'held longer than the 30 s token request timeout');
        $this->assertGreaterThan(30, UpworkMcpOAuth::LOCK_WAIT_SECONDS, 'waiters outlast a normal refresh');
    }

    // ---- Failures and malformed responses ---------------------------------------------------

    public function malformedResponses(): array
    {
        return [
            'HTTP 400 invalid_grant' => [fn() => Http::response(['error' => 'invalid_grant', 'error_description' => 'refresh_token=mcp-refresh-1 is revoked'], 400), 'HTTP 400, invalid_grant'],
            'HTTP 500' => [fn() => Http::response('<html>oops mcp-refresh-1</html>', 500), 'HTTP 500'],
            'timeout' => [function () { throw new ConnectionException('cURL error 28 refresh_token=mcp-refresh-1'); }, 'timed out'],
            'not JSON' => [fn() => Http::response('ok', 200), 'not JSON'],
            'missing access_token' => [fn() => Http::response(['refresh_token' => 'mcp-refresh-2', 'expires_in' => 86400]), 'missing access_token'],
            'empty access_token' => [fn() => Http::response(['access_token' => ' ', 'refresh_token' => 'mcp-refresh-2']), 'missing access_token'],
            'empty refresh_token' => [fn() => Http::response(['access_token' => 'mcp-access-2', 'refresh_token' => '', 'expires_in' => 86400]), 'empty refresh_token'],
            'invalid expires_in' => [fn() => Http::response(['access_token' => 'mcp-access-2', 'refresh_token' => 'mcp-refresh-2', 'expires_in' => 'soon']), 'invalid expires_in'],
            'zero expires_in' => [fn() => Http::response(['access_token' => 'mcp-access-2', 'refresh_token' => 'mcp-refresh-2', 'expires_in' => 0]), 'invalid expires_in'],
            'unexpected token_type' => [fn() => Http::response(['access_token' => 'mcp-access-2', 'refresh_token' => 'mcp-refresh-2', 'token_type' => 'mac']), 'unexpected token_type'],
        ];
    }

    /** @dataProvider malformedResponses */
    public function test_failed_or_malformed_responses_never_overwrite_stored_credentials(callable $respond, string $expected): void
    {
        $this->mcpConnect();
        $before = $this->rawRow();
        $this->fakeTokenEndpoint($respond);

        try {
            (new UpworkMcpOAuth())->refresh();
            $this->fail('expected the refresh to fail');
        } catch (\RuntimeException $e) {
            $this->assertNotInstanceOf(UpworkTokenRefreshBusy::class, $e);
            $this->assertStringContainsString($expected, $e->getMessage());
        }

        $stored = UpworkApiCredential::mcp();
        $this->assertSame($before, $this->rawRow(), 'tokens and expiry metadata unchanged');
        $this->assertSame(['mcp-access-1', 'mcp-refresh-1'], [$stored->access_token, $stored->refresh_token]);
        $this->assertStringContainsString($expected, $stored->last_error);
        foreach (['mcp-refresh-1', 'mcp-access-1', 'mcp-refresh-2', 'mcp-access-2'] as $secret) {
            $this->assertStringNotContainsString($secret, $e->getMessage());
            $this->assertStringNotContainsString($secret, (string) $stored->last_error);
        }
        $this->assertLockFree();
    }

    public function test_tokens_rotated_but_not_saved_is_reported_without_secrets(): void
    {
        $this->mcpConnect();
        $before = $this->rawRow();
        $this->fakeTokenEndpoint(fn() => $this->rotated());
        Log::spy();
        UpworkApiCredential::saving(function () {
            throw new \RuntimeException('SQLSTATE[HY000]: disk full');
        });

        try {
            (new UpworkMcpOAuth())->refresh();
            $this->fail('expected a save failure');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('could not be saved; reconnect', $e->getMessage());
        }

        $this->assertSame($before, $this->rawRow());
        Log::shouldHaveReceived('critical')->withArgs(fn($message, $context) => strpos($message, 'saving the new tokens failed') !== false
            && strpos(json_encode($context), 'mcp-access-2') === false && strpos(json_encode($context), 'mcp-refresh-2') === false)->once();
        $this->assertLockFree();
    }

    // ---- Callers --------------------------------------------------------------------------

    public function test_a_busy_refresh_during_discovery_is_transient_not_an_auth_failure(): void
    {
        $this->mcpConnect(['expires_at' => now()->subMinute()]); // forces a refresh before the call
        $this->fakeUpworkMcp();
        $other = Cache::lock(UpworkMcpOAuth::LOCK_KEY, UpworkMcpOAuth::LOCK_SECONDS);
        $other->get();

        $result = (new McpUpworkClient())->search(['query' => 'Laravel']);

        $this->assertSame('refresh_busy', $result->errorCode);
        $this->assertSame([], $this->mcpCalls, 'no tool call with an expired token');
        $other->release();
        $this->assertTrue((new McpUpworkClient())->search(['query' => 'Laravel'])->ok, 'next attempt refreshes and succeeds');
    }

    public function test_token_refresh_command_skips_quietly_while_another_refresh_runs(): void
    {
        $this->mcpConnect();
        $this->fakeTokenEndpoint(fn() => $this->rotated());
        $other = Cache::lock(UpworkMcpOAuth::LOCK_KEY, UpworkMcpOAuth::LOCK_SECONDS);
        $other->get();

        $this->artisan('upwork:token-refresh')
            ->expectsOutput('Upwork MCP: another process is refreshing right now; skipped.')
            ->assertExitCode(0);

        $this->assertSame(0, $this->tokenRequests);
        $this->assertSame([], (array) UpworkSetting::get(AlertService::STATE_KEY), 'no alert for a busy lock');
        $other->release();
    }

    public function test_the_mcp_client_still_only_allows_read_only_tools(): void
    {
        $this->assertSame(['find_jobs' => ['search', 'get'], 'list_accounts' => null], McpUpworkClient::TOOLS);
    }
}
