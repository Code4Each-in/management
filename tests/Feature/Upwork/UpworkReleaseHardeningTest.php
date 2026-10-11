<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkAiUsage;
use App\Models\UpworkApiCredential;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiBudgetGuard;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\Ai\AiRequest;
use App\Services\Upwork\Ai\Providers\OpenAiProvider;
use App\Services\Upwork\Api\UpworkOAuth;
use App\Services\Upwork\Notifications\AlertService;
use App\Services\Upwork\Notifications\LogNotifier;
use App\Services\Upwork\Notifications\NotificationService;
use App\Services\Upwork\Notifications\OpportunityNotifier;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

/**
 * Release hardening: preflight checks the selected discovery source; provider,
 * OAuth and notification errors are sanitized before they are stored, shown
 * or logged. All HTTP is faked.
 */
class UpworkReleaseHardeningTest extends UpworkTestCase
{
    use FakesUpworkMcp;

    const OPENAI = 'https://api.openai.test/v1/responses';
    const MASKED_KEY = 'sk-proj-********************************abcd';

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Http::swap(new Factory());
    }

    // ---- Preflight: the selected source must be usable ---------------------------------------

    private function productionReady(string $source): void
    {
        Schema::create('migrations', function ($t) { $t->increments('id'); $t->string('migration'); $t->integer('batch'); });
        foreach (glob(database_path('migrations/*_upwork_*.php')) as $file) {
            DB::table('migrations')->insert(['migration' => basename($file, '.php'), 'batch' => 1]);
        }
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function ($t) { $t->id(); $t->string('uuid')->unique(); $t->text('connection'); $t->text('queue'); $t->longText('payload'); $t->longText('exception'); $t->timestamp('failed_at')->useCurrent(); });
        }
        Http::fake();
        $this->app['env'] = 'production';
        config(['cache.default' => 'file', 'mail.default' => 'smtp', 'app.url' => 'https://pms.example.test']);
        URL::forceRootUrl('https://pms.example.test');
        URL::forceScheme('https');
        UpworkSetting::set('notifications.channels', ['log', 'mail']);
        UpworkSetting::set('notifications.mail_to', ['alerts@example.test']);
        UpworkSetting::set('discovery.client', $source);
    }

    public function test_production_preflight_fails_when_the_default_api_source_is_not_connected(): void
    {
        $this->productionReady('graphql');

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✗ Discovery source Upwork API is connected: select "Upwork MCP, direct" as the source, or connect the Upwork API, under Agent & discovery')
            ->assertExitCode(1);
    }

    public function test_production_preflight_passes_with_a_connected_api_source(): void
    {
        $this->productionReady('graphql');
        config(['upwork.api.client_id' => 'client', 'upwork.api.client_secret' => 'secret']);
        UpworkApiCredential::create(['label' => 'default', 'access_token' => 'a', 'refresh_token' => 'r', 'expires_at' => now()->addDay(), 'refresh_expires_at' => now()->addDays(10)]);

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✓ Discovery source Upwork API is connected')
            ->expectsOutput('Preflight passed.')
            ->assertExitCode(0);
    }

    public function test_production_preflight_with_the_mcp_source_disconnected_fails(): void
    {
        $this->productionReady('mcp');

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✗ Discovery source Upwork MCP is connected with an account: connect it under Agent & discovery')
            ->assertExitCode(1);
    }

    public function test_production_preflight_with_the_mcp_source_connected_passes(): void
    {
        $this->productionReady('mcp');
        $this->mcpConnect();

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✓ Discovery source Upwork MCP is connected with an account')
            ->expectsOutput('Preflight passed.')
            ->assertExitCode(0);
    }

    // ---- OpenAI errors ------------------------------------------------------------------

    private function gateway(): AiGateway
    {
        foreach (['enabled' => true, 'provider' => 'openai', 'max_retries' => 0] as $k => $v) {
            UpworkSetting::set("ai.{$k}", $v);
        }

        return new AiGateway(new OpenAiProvider('sk-test-key', 'https://api.openai.test/v1'), new AiBudgetGuard(), function () {});
    }

    private function request(): AiRequest
    {
        $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['ok'], 'properties' => ['ok' => ['type' => 'boolean']]];

        return new AiRequest('qualify', 'qualify-v1', 'Static.', 'Job.', $schema, 'test', 200);
    }

    public function test_openai_auth_errors_store_a_fixed_message_never_the_key(): void
    {
        Http::fake([self::OPENAI => Http::response(['error' => ['message' => 'Incorrect API key provided: ' . self::MASKED_KEY . '. Find your key at platform.openai.com.']], 401)]);

        $result = $this->gateway()->run($this->request(), null);

        $this->assertSame('auth', $result['error_code']);
        $this->assertSame('OpenAI rejected the API key or project (HTTP 401). Check OPENAI_API_KEY on the server.', $result['error']);
        $this->assertStringNotContainsString('sk-', (string) UpworkAiUsage::first()->error);
    }

    public function openAiFailures(): array
    {
        return [
            'quota' => [fn() => Http::response(['error' => ['code' => 'insufficient_quota', 'message' => 'Quota exceeded for key ' . self::MASKED_KEY]], 429)],
            'rate limit' => [fn() => Http::response(['error' => ['code' => 'rate_limit_exceeded', 'message' => 'Rate limit for sk-proj-abcd1234 reached']], 429)],
            'server' => [fn() => Http::response(['error' => ['message' => 'Upstream error, Bearer abc.def.ghi rejected']], 503)],
            'bad request' => [fn() => Http::response(['error' => ['message' => 'Invalid request: api_key=sk-live-1234567890abcdef']], 400)],
        ];
    }

    /** @dataProvider openAiFailures */
    public function test_openai_error_messages_are_scrubbed_before_storage(callable $response): void
    {
        Http::fake([self::OPENAI => $response()]);

        $result = $this->gateway()->run($this->request(), null);

        $this->assertFalse($result['ok']);
        $stored = (string) UpworkAiUsage::first()->error;
        foreach (['sk-proj', 'sk-live', 'abcd1234', 'abc.def.ghi', '1234567890abcdef'] as $secret) {
            $this->assertStringNotContainsString($secret, $stored);
            $this->assertStringNotContainsString($secret, (string) $result['error']);
        }
    }

    public function test_openai_network_errors_are_scrubbed(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28 for https://api.openai.test (Authorization: Bearer sk-test-key)');
        });

        $result = $this->gateway()->run($this->request(), null);

        $this->assertSame('timeout', $result['error_code']);
        $this->assertStringNotContainsString('sk-test-key', (string) $result['error']);
        $this->assertStringNotContainsString('sk-test-key', (string) UpworkAiUsage::first()->error);
    }

    // ---- Notification channel and dormant API OAuth errors ------------------------------------

    public function test_failing_notification_channel_logs_a_scrubbed_message(): void
    {
        $this->app->instance(LogNotifier::class, new class implements OpportunityNotifier {
            public function send(array $message): void
            {
                throw new \RuntimeException('Expected response code 250 but got 535: auth failed for password=Sup3rS3cret token ' . str_repeat('x9', 20));
            }
        });
        Log::spy();
        $payload = $this->payload(['score' => 88, 'recommendation' => 'apply']);
        [$o] = app(UpworkOpportunityService::class)->ingest($payload);

        $this->assertSame('apply', $o->fresh()->notified_level, 'ingestion unaffected');
        Log::shouldHaveReceived('warning')->withArgs(fn($message) => strpos($message, "notification channel 'log' failed") !== false
            && strpos($message, 'Sup3rS3cret') === false && strpos($message, str_repeat('x9', 20)) === false)->once();
    }

    public function test_dormant_api_oauth_stores_only_a_sanitized_error_code(): void
    {
        config(['upwork.api.client_id' => 'client', 'upwork.api.client_secret' => 'secret']);
        $credential = UpworkApiCredential::create(['label' => 'default', 'access_token' => 'a', 'refresh_token' => 'refresh-1', 'expires_at' => now()->subMinute(), 'refresh_expires_at' => now()->addDays(10)]);
        Http::fake(['https://www.upwork.com/api/v3/oauth2/token' => Http::response(['error' => 'invalid_grant<script>alert(1)</script>', 'error_description' => 'refresh_token=refresh-1 revoked'], 400)]);

        try {
            (new UpworkOAuth())->refresh($credential);
            $this->fail('expected the refresh to fail');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('<script>', $e->getMessage());
        }

        $error = (string) UpworkApiCredential::current()->last_error;
        $this->assertStringContainsString('HTTP 400', $error);
        $this->assertStringNotContainsString('<', $error);
        $this->assertStringNotContainsString('refresh-1', $error);
        $this->assertSame('refresh-1', UpworkApiCredential::current()->refresh_token, 'stored token unchanged');
    }

    public function test_scrub_removes_provider_masked_keys(): void
    {
        $clean = AlertService::scrub('Incorrect API key provided: ' . self::MASKED_KEY . ' and rk-live-abc123');

        $this->assertStringNotContainsString('sk-proj', $clean);
        $this->assertStringNotContainsString('abcd', $clean);
        $this->assertStringNotContainsString('rk-live', $clean);
        $this->assertStringContainsString('Incorrect API key provided', $clean);
    }
}
