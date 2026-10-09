<?php

namespace App\Console\Commands;

use App\Models\UpworkApiCredential;
use App\Models\UpworkRuleset;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\Ai\AiPricing;
use App\Services\Upwork\Api\GraphQlUpworkClient;
use App\Services\Upwork\Api\ReadOnlyGraphQl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only deployment check for the Upwork module. Makes no Upwork or AI
 * calls, changes nothing, and never prints secrets (only whether they are set).
 */
class UpworkPreflight extends Command
{
    protected $signature = 'upwork:preflight';
    protected $description = 'Check the Upwork module deployment (read-only; no external calls, no secrets shown)';

    /** @var int */
    private $failures = 0;

    public function handle()
    {
        $this->line('Upwork module preflight (' . app()->environment() . ', database ' . DB::connection()->getDatabaseName() . ')');

        // Schema
        $ran = Schema::hasTable('migrations') ? DB::table('migrations')->pluck('migration') : collect();
        $pending = collect(glob(database_path('migrations/*_upwork_*.php')))->map(fn($f) => basename($f, '.php'))
            ->diff($ran)->values();
        $this->check($pending->isEmpty(), 'All Upwork migrations applied', $pending->count() . ' pending: ' . $pending->implode(', '));
        foreach (['upwork_queue_jobs', 'failed_jobs', 'upwork_ai_usage', 'upwork_ai_analyses', 'upwork_api_credentials'] as $table) {
            $this->check(Schema::hasTable($table), "Table {$table}", 'missing');
        }
        $crossProcess = !in_array(config('cache.stores.' . config('cache.default') . '.driver'), ['array', 'null'], true);
        if (app()->environment('production')) {
            $this->check($crossProcess, 'Cache store ' . config('cache.default') . ' can lock across processes (token refresh, no-overlap)', 'use file, database or redis');
        } elseif (!$crossProcess) {
            $this->info('  · Cache store ' . config('cache.default') . ' locks within one process only (fine for tests, not for production)');
        }
        $this->check(config('queue.connections.upwork.table') === 'upwork_queue_jobs', "Queue 'upwork' uses upwork_queue_jobs (not the PMS 'jobs' table)", 'misconfigured');
        $this->check((bool) UpworkRuleset::current(), 'A ruleset is published', 'run: php artisan db:seed --class=UpworkIntelligenceSeeder');

        // Secrets: presence only
        $this->check((bool) config('app.key'), 'APP_KEY set (encrypts Upwork tokens)', 'missing');
        $this->info('  · OPENAI_API_KEY: ' . (config('upwork.ai.providers.openai.api_key') ? 'set' : 'not set'));
        $this->info('  · UPWORK_CLIENT_ID / SECRET: ' . (config('upwork.api.client_id') && config('upwork.api.client_secret') ? 'set' : 'not set'));
        $cred = UpworkApiCredential::current();
        $this->info('  · Upwork API: ' . ($cred && $cred->isConnected() ? 'connected, refresh window until ' . optional($cred->refresh_expires_at)->toDateString() : 'not connected'));

        $mcp = UpworkApiCredential::mcp();
        $this->info('  · Upwork MCP: ' . ($mcp && $mcp->isConnected() ? 'connected' . ($mcp->tenant_id ? ', account chosen' : ', NO account chosen') . ', refresh window until ' . optional($mcp->refresh_expires_at)->toDateString() : 'not connected'));

        // Read-only guarantee
        $allowed = array_keys(GraphQlUpworkClient::OPERATIONS);
        $ok = collect(GraphQlUpworkClient::OPERATIONS)->every(fn($doc, $name) => ReadOnlyGraphQl::assert($doc, $allowed) === $name);
        $this->check($ok && $allowed === ['JobSearch', 'JobDetail'], 'Upwork client: only read-only queries JobSearch, JobDetail', 'unexpected operations');
        $this->check(\App\Services\Upwork\Api\McpUpworkClient::TOOLS === ['find_jobs' => ['search', 'get'], 'list_accounts' => null],
            'Upwork MCP client: only find_jobs (search, get) and list_accounts', 'unexpected tools');

        // AI cost controls
        $s = UpworkSetting::allWithDefaults();
        $this->check(!app()->environment('production') || $s['ai.provider'] !== 'fake', 'AI provider is not the fake one in production', 'ai.provider=fake');
        $this->check(AiPricing::rates((string) $s['ai.provider'], (string) $s['ai.model']) !== null, "Model {$s['ai.model']} has a configured price", 'unpriced model: AI calls will be refused');
        $this->check((float) $s['ai.monthly_budget_usd'] <= 20, 'Monthly AI budget $' . $s['ai.monthly_budget_usd'], 'above $20, check this is intended');
        $this->check((float) $s['ai.daily_budget_usd'] <= (float) $s['ai.monthly_budget_usd'], 'Daily AI budget $' . $s['ai.daily_budget_usd'] . ' within the monthly budget', 'daily above monthly');
        $status = AiGateway::fromSettings()->status();
        $this->info(sprintf('  · AI %s; month to date $%s of $%s', $s['ai.enabled'] ? 'ENABLED' : 'disabled', number_format($status['month']['cost'], 4), $status['monthly_budget_usd']));

        // Alerts reach a person
        $delivery = \App\Services\Upwork\Notifications\AlertService::delivery();
        $this->info('  · Alerts: channels ' . (implode(', ', $delivery['channels']) ?: 'none') . "; {$delivery['recipients']} mail recipient(s); mailer {$delivery['mailer']}");
        if (app()->environment('production')) {
            $this->check($delivery['problem'] === null, 'Email alerts reach a person', (string) $delivery['problem']);
        } elseif ($delivery['problem'] !== null) {
            $this->info("  · Email alerts would not reach anyone: {$delivery['problem']}");
        }
        $open = \App\Services\Upwork\Notifications\AlertService::open();
        $this->info('  · Open alerts: ' . (count($open) ?: 'none'));
        foreach ($open as $alert) {
            $this->warn("    - {$alert['label']}: {$alert['title']} (×{$alert['count']})");
        }

        // HTTPS for the OAuth redirect (Upwork sends the login result there)
        $callback = route('upwork.config.mcp.callback');
        if (app()->environment('production')) {
            $this->check(strpos((string) config('app.url'), 'https://') === 0 && strpos($callback, 'https://') === 0, "OAuth redirect uses HTTPS ({$callback})", 'set APP_URL to the https:// address');
        } else {
            $this->info("  · OAuth redirect: {$callback}");
        }

        // Switches
        $this->info('  · Scheduler entries: ' . (config('upwork.scheduler.enabled') ? 'ON' : 'off (UPWORK_SCHEDULER_ENABLED)'));
        $this->info('  · Scheduled discovery: ' . ($s['agent.enabled'] ? 'ON' : 'off') . ", every {$s['agent.interval_minutes']} min, source {$s['discovery.client']}, ≤{$s['discovery.max_searches_per_day']} searches/day");
        $this->check(!app()->environment('production') || in_array($s['discovery.client'], ['graphql', 'mcp'], true), 'Discovery source is a real Upwork source in production', 'fixture client selected');
        // The selected source must be usable, whichever it is.
        if ($s['discovery.client'] === 'mcp') {
            $this->check($mcp && $mcp->isConnected() && $mcp->tenant_id, 'Discovery source Upwork MCP is connected with an account', 'connect it under Agent & discovery');
        } elseif ($s['discovery.client'] === 'graphql') {
            $api = UpworkApiCredential::current();
            $apiReady = (new \App\Services\Upwork\Api\UpworkOAuth())->isConfigured() && $api && $api->isConnected();
            if (app()->environment('production')) {
                $this->check($apiReady, 'Discovery source Upwork API is connected', 'select "Upwork MCP, direct" as the source, or connect the Upwork API, under Agent & discovery');
            } elseif (!$apiReady) {
                $this->info('  · Discovery source Upwork API is not connected (fine outside production)');
            }
        }

        $this->line($this->failures ? "{$this->failures} problem(s) found." : 'Preflight passed.');

        return $this->failures ? 1 : 0;
    }

    private function check(bool $ok, string $label, string $problem): void
    {
        if ($ok) {
            $this->info("  ✓ {$label}");
        } else {
            $this->failures++;
            $this->error("  ✗ {$label}: {$problem}");
        }
    }
}
