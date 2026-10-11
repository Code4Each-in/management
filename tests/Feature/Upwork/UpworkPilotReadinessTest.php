<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkApiCredential;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Notifications\AlertService;
use App\Services\Upwork\Notifications\LogNotifier;
use App\Services\Upwork\Notifications\OpportunityNotifier;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only pilot readiness: the real Artisan entry points with the Upwork MCP
 * source (faked), token refresh and rotation, preflight, safe skips, and
 * operational alerts (sent once per cooldown, scrubbed of credentials).
 */
class UpworkPilotReadinessTest extends UpworkTestCase
{
    use FakesUpworkMcp;

    /** @var array messages the log channel received */
    private $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Http::swap(new Factory());

        $test = $this;
        $this->app->instance(LogNotifier::class, new class($test) implements OpportunityNotifier {
            private $test;
            public function __construct($test) { $this->test = $test; }
            public function send(array $message): void { $this->test->record($message); }
        });

        UpworkSetting::set('discovery.client', 'mcp');
        UpworkSetting::set('discovery.api_pause_ms', 0);
        UpworkSetting::set('discovery.max_queries_per_run', 2);
        UpworkSetting::set('agent.enrich_limit_per_run', 1);
    }

    public function record(array $message): void
    {
        $this->sent[] = $message;
    }

    private function alerts(): array
    {
        return array_values(array_filter($this->sent, fn($m) => in_array($m['level'], ['alert', 'resolved'], true)));
    }

    private function everythingSent(): string
    {
        return json_encode($this->sent) . json_encode(UpworkSetting::get(AlertService::STATE_KEY));
    }

    // ---- upwork:discover with the MCP source ----------------------------------------------

    public function test_scheduled_discover_command_runs_through_the_mcp_read_only(): void
    {
        $this->mcpConnect();
        $this->fakeUpworkMcp();
        UpworkSetting::set('agent.enabled', true);

        $this->artisan('upwork:discover')->assertExitCode(0);

        $run = UpworkDiscoveryRun::latest('id')->first();
        $this->assertSame(['completed', 'scheduled', 'server:upwork-mcp'], [$run->status, $run->trigger, $run->agent_host]);
        $this->assertGreaterThan(0, UpworkOpportunity::count());
        $this->assertSame(1, UpworkOpportunity::where('stage', UpworkOpportunity::STAGE_ENRICHED)->count());
        $this->assertEmpty(array_diff($this->mcpToolNames(), ['find_jobs:search', 'find_jobs:get']), 'only read-only calls');
        $this->assertSame([], $this->alerts(), 'healthy run: no alert');
    }

    public function test_scheduled_discovery_with_mcp_disconnected_skips_safely_and_alerts_once(): void
    {
        UpworkSetting::set('agent.enabled', true);
        Http::fake();

        $this->artisan('upwork:discover')->expectsOutput("Discovery skipped: Upwork source 'upwork-mcp' is not connected (Upwork → Configuration → Agent & discovery).")->assertExitCode(0);
        $this->artisan('upwork:discover')->assertExitCode(0);

        $this->assertSame(0, UpworkDiscoveryRun::count(), 'no misleading run record');
        Http::assertNothingSent();
        $this->assertCount(1, $this->alerts(), 'second skip is inside the cooldown');
        $this->assertStringContainsString('Upwork connection is missing or expired', $this->alerts()[0]['title']);
        $this->assertSame(2, UpworkSetting::get(AlertService::STATE_KEY)[AlertService::UPWORK_CONNECTION]['count']);
    }

    public function test_manual_or_switched_off_discovery_does_not_alert(): void
    {
        Http::fake();

        $this->artisan('upwork:discover', ['--manual' => true])->assertExitCode(0); // disconnected, manual
        $this->artisan('upwork:discover')->expectsOutput('Discovery skipped: Discovery is switched off (Upwork → Configuration → Agent & discovery).');

        $this->assertSame([], $this->alerts());
        $this->assertSame(0, UpworkDiscoveryRun::count());
    }

    // ---- Failures during a run --------------------------------------------------------------

    public function test_rejected_token_stops_the_run_and_alerts_without_secrets(): void
    {
        $this->mcpConnect();
        $this->fakeUpworkMcp();
        $this->mcpToolStatuses = [401];
        $this->mcpTokenResponder = fn() => Http::response(['error' => 'invalid_grant', 'error_description' => 'refresh_token=mcp-refresh-1 revoked'], 400);
        UpworkSetting::set('agent.enabled', true);

        $this->artisan('upwork:discover')->assertExitCode(0);

        $run = UpworkDiscoveryRun::latest('id')->first();
        $this->assertSame('Stopped early: auth.', $run->error);
        $this->assertCount(1, $this->mcpCalls, 'no further calls once the token is rejected');
        $this->assertCount(1, $this->alerts());
        $this->assertSame('Upwork rejected the discovery connection', $this->alerts()[0]['title']);
        foreach (['mcp-access-1', 'mcp-refresh-1', 'mcp-access-2'] as $secret) {
            $this->assertStringNotContainsString($secret, $this->everythingSent());
        }
        $this->assertStringNotContainsString('mcp-refresh-1', (string) UpworkApiCredential::mcp()->last_error);
    }

    public function test_a_run_where_every_search_fails_alerts_and_a_healthy_run_resolves_it(): void
    {
        $this->mcpConnect();
        $this->fakeUpworkMcp();
        $this->mcpToolStatuses = [503, 503];

        $this->artisan('upwork:discover', ['--manual' => true])->assertExitCode(0);
        $this->assertSame('Every Upwork search in a discovery run failed', $this->alerts()[0]['title']);

        UpworkDiscoveryRun::query()->update(['status' => 'completed']);
        \App\Models\UpworkSearchProfile::query()->update(['last_run_at' => null, 'backoff_until' => null]);
        $this->artisan('upwork:discover', ['--manual' => true])->assertExitCode(0);

        $this->assertSame(['alert', 'resolved'], array_column($this->alerts(), 'level'));
        $this->assertSame([], UpworkSetting::get(AlertService::STATE_KEY));
    }

    public function test_rate_limits_do_not_alert(): void
    {
        $this->mcpConnect();
        $this->fakeUpworkMcp();
        $this->mcpToolStatuses = [429];

        $this->artisan('upwork:discover', ['--manual' => true])->assertExitCode(0);

        $this->assertSame([], $this->alerts(), 'rate limits back off quietly');
    }

    // ---- upwork:token-refresh ---------------------------------------------------------------

    public function test_token_refresh_rotates_tokens_and_extends_the_window(): void
    {
        $this->mcpConnect(['refresh_expires_at' => now()->addDays(2)]);
        $this->fakeUpworkMcp();

        $this->artisan('upwork:token-refresh')->assertExitCode(0);

        $c = UpworkApiCredential::mcp();
        $this->assertSame(['mcp-access-2', 'mcp-refresh-2'], [$c->access_token, $c->refresh_token]);
        $this->assertTrue($c->expires_at->between(now()->addHours(23), now()->addHours(25)));
        $this->assertTrue($c->refresh_expires_at->gt(now()->addDays(13)), 'a rotated refresh token restarts the window');
        $this->assertStringNotContainsString('mcp-refresh-2', DB::table('upwork_api_credentials')->where('label', 'mcp')->value('refresh_token'));
        Http::assertSent(fn($r) => $r['grant_type'] === 'refresh_token' && $r['client_id'] === 'pms-client' && $r['resource'] === 'https://mcp.upwork.com/mcp');
    }

    public function test_token_refresh_without_rotation_keeps_the_old_refresh_token_and_window(): void
    {
        $window = now()->addDays(5)->startOfSecond();
        $this->mcpConnect(['refresh_expires_at' => $window]);
        $this->fakeUpworkMcp();
        $this->mcpTokenResponder = fn() => Http::response(['access_token' => 'mcp-access-3', 'token_type' => 'Bearer', 'expires_in' => 3600]);

        $this->artisan('upwork:token-refresh')->assertExitCode(0);

        $c = UpworkApiCredential::mcp();
        $this->assertSame(['mcp-access-3', 'mcp-refresh-1'], [$c->access_token, $c->refresh_token]);
        $this->assertTrue($c->refresh_expires_at->eq($window), 'window not extended without a new refresh token');
    }

    public function test_token_refresh_failure_exits_non_zero_alerts_once_and_recovers(): void
    {
        $this->mcpConnect();
        $this->fakeUpworkMcp();
        $this->mcpTokenResponder = fn() => Http::response(['error' => 'invalid_grant'], 400);

        $this->artisan('upwork:token-refresh')->assertExitCode(1);
        $this->artisan('upwork:token-refresh')->assertExitCode(1);

        $c = UpworkApiCredential::mcp();
        $this->assertSame('mcp-access-1', $c->access_token, 'stored tokens untouched on failure');
        $this->assertStringContainsString('invalid_grant', $c->last_error);
        $this->assertCount(1, $this->alerts(), 'the repeat is inside the cooldown');
        $this->assertSame('Upwork MCP token refresh failed', $this->alerts()[0]['title']);

        $this->travel(7)->hours();
        $this->artisan('upwork:token-refresh')->assertExitCode(1);
        $this->assertCount(2, $this->alerts(), 'reminder after the cooldown');
        $this->assertStringContainsString('occurred 3 times', $this->alerts()[1]['title']);

        $this->mcpTokenResponder = null;
        $this->artisan('upwork:token-refresh')->assertExitCode(0);
        $this->assertSame('resolved', $this->alerts()[2]['level']);
        $this->assertNull(UpworkApiCredential::mcp()->last_error);
        foreach (['mcp-access-1', 'mcp-refresh-1', 'mcp-access-2', 'mcp-refresh-2'] as $secret) {
            $this->assertStringNotContainsString($secret, $this->everythingSent());
        }
    }

    public function test_token_refresh_with_nothing_connected_is_a_no_op(): void
    {
        Http::fake();

        $this->artisan('upwork:token-refresh')
            ->expectsOutput('Upwork API: not connected; nothing to refresh.')
            ->expectsOutput('Upwork MCP: not connected; nothing to refresh.')
            ->assertExitCode(0);

        Http::assertNothingSent();
        $this->assertSame([], $this->alerts());
    }

    // ---- Preflight --------------------------------------------------------------------------

    private function recordUpworkMigrations(): void
    {
        Schema::create('migrations', function ($t) { $t->increments('id'); $t->string('migration'); $t->integer('batch'); });
        foreach (glob(database_path('migrations/*_upwork_*.php')) as $file) {
            DB::table('migrations')->insert(['migration' => basename($file, '.php'), 'batch' => 1]);
        }
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function ($t) { $t->id(); $t->string('uuid')->unique(); $t->text('connection'); $t->text('queue'); $t->longText('payload'); $t->longText('exception'); $t->timestamp('failed_at')->useCurrent(); });
        }
    }

    public function test_preflight_with_the_mcp_source_connected_passes(): void
    {
        $this->recordUpworkMigrations();
        $this->mcpConnect();
        Http::fake();

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✓ Discovery source Upwork MCP is connected with an account')
            ->expectsOutput('Preflight passed.')
            ->assertExitCode(0);
        Http::assertNothingSent();
    }

    public function test_preflight_with_the_mcp_source_disconnected_fails(): void
    {
        $this->recordUpworkMigrations();
        Http::fake();

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✗ Discovery source Upwork MCP is connected with an account: connect it under Agent & discovery')
            ->assertExitCode(1);

        $this->mcpConnect(['tenant_id' => null]);
        $this->artisan('upwork:preflight')->assertExitCode(1); // connected but no account chosen
    }

    // ---- Preflight: alert delivery and HTTPS (production rules) ---------------------------

    /** A production-like configuration that passes every production check. */
    private function productionReady(): void
    {
        $this->recordUpworkMigrations();
        $this->mcpConnect();
        Http::fake();
        $this->app['env'] = 'production';
        config(['cache.default' => 'file', 'mail.default' => 'smtp', 'app.url' => 'https://pms.example.test']);
        \Illuminate\Support\Facades\URL::forceRootUrl('https://pms.example.test');
        \Illuminate\Support\Facades\URL::forceScheme('https');
        UpworkSetting::set('notifications.channels', ['log', 'mail']);
        UpworkSetting::set('notifications.mail_to', ['alerts@example.test']);
    }

    public function test_preflight_reports_alert_channels_and_recipients(): void
    {
        $this->recordUpworkMigrations();
        Http::fake();
        UpworkSetting::set('notifications.channels', ['log', 'mail']);
        UpworkSetting::set('notifications.mail_to', ['alerts@example.test', 'not-an-email', 'ops@example.test']);

        $this->artisan('upwork:preflight')
            ->expectsOutput('  · Alerts: channels log, mail; 2 mail recipient(s); mailer ' . config('mail.default'))
            ->assertExitCode(1); // MCP source not connected in this test
    }

    public function test_production_preflight_passes_when_alerts_and_https_are_ready(): void
    {
        $this->productionReady();

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✓ Cache store file can lock across processes (token refresh, no-overlap)')
            ->expectsOutput('  ✓ Email alerts reach a person')
            ->expectsOutput('  ✓ OAuth redirect uses HTTPS (https://pms.example.test/upwork/config/mcp/callback)')
            ->expectsOutput('Preflight passed.')
            ->assertExitCode(0);
    }

    public function alertDeliveryProblems(): array
    {
        return [
            'mail channel off' => [fn() => UpworkSetting::set('notifications.channels', ['log']), 'the mail channel is off (Agent & discovery → Notification channels)'],
            'no recipients' => [fn() => UpworkSetting::set('notifications.mail_to', ['not-an-email']), 'no valid email recipients are set (Agent & discovery → Email recipients)'],
            'mailer does not send' => [fn() => config(['mail.default' => 'log']), "the mailer 'log' does not send email (MAIL_MAILER in .env)"],
        ];
    }

    /** @dataProvider alertDeliveryProblems */
    public function test_production_preflight_fails_when_email_alerts_cannot_reach_anyone(callable $break, string $problem): void
    {
        $this->productionReady();
        $break();

        $this->artisan('upwork:preflight')
            ->expectsOutput("  ✗ Email alerts reach a person: {$problem}")
            ->assertExitCode(1);
    }

    public function test_production_preflight_fails_without_https(): void
    {
        $this->productionReady();
        config(['app.url' => 'http://pms.example.test']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://pms.example.test');
        \Illuminate\Support\Facades\URL::forceScheme('http');

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✗ OAuth redirect uses HTTPS (http://pms.example.test/upwork/config/mcp/callback): set APP_URL to the https:// address')
            ->assertExitCode(1);
    }

    public function test_production_preflight_fails_with_a_single_process_cache(): void
    {
        $this->productionReady();
        config(['cache.default' => 'array']);

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✗ Cache store array can lock across processes (token refresh, no-overlap): use file, database or redis')
            ->assertExitCode(1);
    }

    public function test_preflight_keeps_reporting_the_safe_defaults(): void
    {
        $this->productionReady();

        $this->artisan('upwork:preflight')
            ->expectsOutput('  ✓ Upwork MCP client: only find_jobs (search, get) and list_accounts')
            ->expectsOutput('  · Scheduler entries: off (UPWORK_SCHEDULER_ENABLED)')
            ->assertExitCode(0);
        $this->assertFalse((bool) UpworkSetting::get('ai.enabled'), 'AI off by default');
        $this->assertFalse((bool) UpworkSetting::get('agent.enabled'), 'scheduled discovery off by default');
        $this->assertFalse((bool) config('upwork.scheduler.enabled'));
    }

    // ---- Open alerts in the PMS -------------------------------------------------------------

    public function test_open_alerts_are_listed_in_preflight_and_shown_until_resolved(): void
    {
        $this->recordUpworkMigrations();
        Http::fake();
        $alerts = new AlertService();
        $alerts->raise(AlertService::TOKEN_REFRESH . ':mcp', 'Upwork MCP token refresh failed', ['HTTP 400 invalid_grant']);
        $alerts->raise(AlertService::TOKEN_REFRESH . ':mcp', 'Upwork MCP token refresh failed');

        $this->artisan('upwork:preflight')
            ->expectsOutput('  · Open alerts: 1')
            ->expectsOutput('    - Token refresh (Mcp): Upwork MCP token refresh failed (×2)');

        $admin = $this->superAdmin();
        $this->actingAs($admin)->get(route('upwork.config.settings.edit'))->assertOk()
            ->assertSee('Open alert')->assertSee('Token refresh (Mcp):')->assertSee('Upwork MCP token refresh failed')->assertSee('2× since');
        $this->actingAs($admin)->get(route('upwork.radar'))->assertOk()
            ->assertSee('Open alert')->assertSee('Upwork MCP token refresh failed');

        $alerts->resolve(AlertService::TOKEN_REFRESH . ':mcp', 'Upwork MCP token refresh is working again');
        $this->actingAs($admin)->get(route('upwork.config.settings.edit'))->assertOk()->assertDontSee('Open alert');
        $this->actingAs($admin)->get(route('upwork.radar'))->assertOk()->assertDontSee('Open alert');
    }

    public function test_open_alert_titles_are_scrubbed_before_display(): void
    {
        (new AlertService())->raise(AlertService::UPWORK_CONNECTION, 'Rejected Bearer mcp-access-secret-token-value refresh_token=mcp-refresh-1');

        $title = AlertService::open()[0]['title'];
        $this->assertStringNotContainsString('mcp-access-secret-token-value', $title);
        $this->assertStringNotContainsString('mcp-refresh-1', $title);
        $this->actingAs($this->superAdmin())->get(route('upwork.config.settings.edit'))->assertOk()->assertDontSee('mcp-refresh-1');
    }

    // ---- Alert scrubbing --------------------------------------------------------------------

    public function test_alert_text_is_scrubbed_of_credentials(): void
    {
        $raw = 'HTTP 401 Bearer eyJhbGciOiJSUzI1NiJ9.payload.sig access_token=abc123 "refresh_token":"r-456" client_secret: s3cr3t '
            . str_repeat('A1b2', 10) . ' job 2108471673182427072';

        $clean = AlertService::scrub($raw);

        foreach (['eyJhbGci', 'abc123', 'r-456', 's3cr3t', str_repeat('A1b2', 10)] as $secret) {
            $this->assertStringNotContainsString($secret, $clean);
        }
        $this->assertStringContainsString('2108471673182427072', $clean, 'job ids stay readable');
        $this->assertStringContainsString('HTTP 401', $clean);
    }
}
