<?php

namespace Tests\Unit;

use Code4Each\UpworkAgent\ClaudeRunner;
use Code4Each\UpworkAgent\Config;
use Code4Each\UpworkAgent\Logger;
use Code4Each\UpworkAgent\PmsClient;
use Code4Each\UpworkAgent\Worker;
use PHPUnit\Framework\TestCase;

foreach (['Logger', 'Config', 'PmsClient', 'ClaudeRunner', 'Worker'] as $class) {
    require_once __DIR__ . "/../../agent/src/{$class}.php";
}

/** PMS client double: records requests, answers from a script. */
class FakePms extends PmsClient
{
    public $requests = [];
    private $handler;

    public function __construct(callable $handler)
    {
        parent::__construct('https://pms.test', 'secret-token', new Logger(sys_get_temp_dir() . '/upwork-agent-test-logs'), 3, function () {});
        $this->handler = $handler;
    }

    protected function send(string $method, string $path, ?array $payload): array
    {
        $this->requests[] = [$method, $path, $payload];
        [$status, $body] = ($this->handler)($method, $path, $payload, count($this->requests));

        return [$status, json_encode($body), null];
    }
}

/** Claude double: answers from a script and records prompts. */
class FakeClaude extends ClaudeRunner
{
    public $prompts = [];
    private $answers;

    public function __construct(Config $config, callable $answers)
    {
        parent::__construct($config, new Logger(sys_get_temp_dir() . '/upwork-agent-test-logs'));
        $this->answers = $answers;
    }

    public function run(string $prompt, array $schema): array
    {
        $this->prompts[] = $prompt;

        return ($this->answers)($prompt);
    }
}

/**
 * Phase 9: the Mac mini worker - read-only tool boundary, output parsing,
 * secrets, retries, run flow, rate limits and locking. No database, no Upwork.
 */
class UpworkAgentTest extends TestCase
{
    private function config(array $overrides = []): Config
    {
        return new Config(array_merge([
            'PMS_URL' => 'https://pms.test', 'PMS_TOKEN' => 'secret-token', 'ORG_UID' => 'org-1',
            'LOCK_FILE' => sys_get_temp_dir() . '/upwork-agent-test-' . uniqid() . '.lock',
            'LOG_DIR' => sys_get_temp_dir() . '/upwork-agent-test-logs', 'ENRICH_BATCH_SIZE' => 5,
        ], $overrides));
    }

    private function plan(): array
    {
        return [
            ['search_profile_id' => 1, 'slug' => 'laravel-php', 'name' => 'Laravel / PHP', 'version' => 1, 'known_job_ids' => [],
                'queries' => [['type' => 'query', 'text' => 'Laravel', 'params' => ['query' => 'Laravel'], 'pages' => 1],
                              ['type' => 'title', 'text' => 'Laravel', 'params' => ['title' => 'Laravel'], 'pages' => 1]]],
            ['search_profile_id' => 2, 'slug' => 'ai-automation', 'name' => 'AI Automation', 'version' => 1, 'known_job_ids' => [],
                'queries' => [['type' => 'query', 'text' => 'OpenAI', 'params' => ['query' => 'OpenAI'], 'pages' => 1]]],
        ];
    }

    /** A PMS that is enabled, due, grants a run and accepts everything. */
    private function pms(array $overrides = []): FakePms
    {
        $o = array_merge(['enabled' => true, 'last_run' => null, 'start_status' => 201, 'enrich' => ['111']], $overrides);

        return new FakePms(function ($method, $path, $payload) use ($o) {
            if ($path === '/api/upwork/agent/config') {
                return [200, ['data' => ['agent' => ['enabled' => $o['enabled'], 'interval_minutes' => 20, 'enrich_limit_per_run' => 15],
                    'last_run_started_at' => $o['last_run'], 'catalog' => [['slug' => 'laravel'], ['slug' => 'openai']]]]];
            }
            if ($path === '/api/upwork/agent/runs') {
                return [$o['start_status'], ['data' => ['run' => ['uuid' => 'run-1'], 'plan' => $this->plan(), 'limits' => ['enrich_limit_per_run' => 15]], 'message' => 'refused']];
            }
            if (str_ends_with($path, '/queries')) {
                return [200, ['data' => ['results' => [], 'invalid' => [], 'enrich' => $payload['query']['text'] === 'Laravel' && $payload['query']['type'] === 'query' ? $o['enrich'] : []]]];
            }
            if (str_ends_with($path, '/complete')) {
                return [200, ['data' => ['stats' => ['queries' => 3]]]];
            }
            return [in_array($path, ['/api/upwork/opportunities'], true) ? 201 : 200, ['data' => []]];
        });
    }

    private function searchAnswer(array $statuses = ['ok', 'ok']): callable
    {
        return function (string $prompt) use ($statuses) {
            if (strpos($prompt, 'MODE: enrich') !== false) {
                return ['ok' => true, 'rate_limited' => false, 'error' => null, 'cost_usd' => 0.01, 'data' => ['jobs' => [
                    ['upwork_job_id' => '111', 'ok' => true, 'payload' => ['upwork_job_id' => '111', 'title' => 'Job', 'score' => 99, 'recommendation' => 'apply']],
                ]]];
            }
            preg_match_all('/^(\d+)\. type=/m', $prompt, $m);
            $pages = [];
            foreach ($m[1] as $n => $index) {
                $pages[] = ['index' => (int) $index, 'page' => 1, 'status' => $statuses[$n] ?? 'ok', 'error' => null, 'jobs' => [['upwork_job_id' => "j{$index}", 'title' => 'x']]];
            }
            return ['ok' => true, 'rate_limited' => false, 'error' => null, 'cost_usd' => 0.02, 'data' => ['queries' => $pages]];
        };
    }

    private function paths(FakePms $pms, string $suffix): array
    {
        return array_values(array_filter($pms->requests, fn($r) => str_ends_with($r[1], $suffix)));
    }

    // ---- Read-only boundary ------------------------------------------------------------

    public function test_claude_only_gets_read_only_upwork_tools(): void
    {
        $cmd = (new ClaudeRunner($this->config(), new Logger(sys_get_temp_dir())))->command('hello', ['type' => 'object']);
        $flag = fn(string $name) => $cmd[array_search($name, $cmd, true) + 1];

        $this->assertContains('--strict-mcp-config', $cmd, 'only the Upwork MCP server is loaded');
        $this->assertSame('mcp__upwork__upwork__find_jobs,mcp__upwork__upwork__list_accounts', $flag('--allowedTools'));
        $disallowed = explode(',', $flag('--disallowedTools'));
        foreach (['Bash', 'Read', 'Write', 'Edit', 'WebFetch', 'mcp__upwork__upwork__manage_proposals', 'mcp__upwork__upwork__send_message',
                     'mcp__upwork__upwork__save_job', 'mcp__upwork__upwork__set_tool_permission'] as $tool) {
            $this->assertContains($tool, $disallowed);
        }
        $this->assertCount(17, array_filter($disallowed, fn($t) => str_starts_with($t, 'mcp__upwork__')), 'all 17 Upwork write tools');
        $this->assertSame([], array_intersect(ClaudeRunner::ALLOWED_TOOLS, ClaudeRunner::DISALLOWED_TOOLS));
        $this->assertContains('--max-budget-usd', $cmd);
        $this->assertContains('--json-schema', $cmd);
        $this->assertNotContains('secret-token', $cmd, 'the PMS token is never passed to Claude');
    }

    public function test_prompts_forbid_write_actions_and_omit_the_token(): void
    {
        foreach (['search', 'enrich'] as $name) {
            $prompt = file_get_contents(__DIR__ . "/../../agent/prompts/{$name}.md");
            $this->assertStringContainsString('Never submit, save, message, apply, or change anything on Upwork', $prompt);
            $this->assertStringNotContainsString('PMS_TOKEN', $prompt);
        }
    }

    // ---- Output parsing -------------------------------------------------------------------

    public function test_claude_output_parsing(): void
    {
        $ok = ClaudeRunner::parse(json_encode(['subtype' => 'success', 'is_error' => false, 'total_cost_usd' => 0.12, 'structured_output' => ['queries' => []]]), '', 0, false);
        $this->assertTrue($ok['ok']);
        $this->assertSame(['queries' => []], $ok['data']);
        $this->assertSame(0.12, $ok['cost_usd']);

        $fenced = ClaudeRunner::parse(json_encode(['subtype' => 'success', 'result' => "Here:\n```json\n{\"jobs\": [1]}\n```"]), '', 0, false);
        $this->assertSame(['jobs' => [1]], $fenced['data']);

        $limited = ClaudeRunner::parse(json_encode(['subtype' => 'error_during_execution', 'is_error' => true, 'result' => 'x']), 'API Error: 429 Too Many Requests', 1, false);
        $this->assertFalse($limited['ok']);
        $this->assertTrue($limited['rate_limited']);

        $budget = ClaudeRunner::parse(json_encode(['subtype' => 'error_max_budget_usd', 'is_error' => true]), '', 1, false);
        $this->assertStringContainsString('error_max_budget_usd', $budget['error']);

        $this->assertSame('Claude timed out', ClaudeRunner::parse('', '', 143, true)['error']);
        $this->assertStringContainsString('Unreadable', ClaudeRunner::parse('garbage', 'boom', 1, false)['error']);
    }

    // ---- Secrets & config --------------------------------------------------------------------

    public function test_logs_redact_secrets(): void
    {
        $redacted = Logger::redact(['pms_token' => 'abc', 'nested' => ['Authorization' => 'Bearer 2|xyz'], 'message' => 'failed with Bearer 2|xyz.123', 'run' => 'r1']);

        $this->assertSame('[redacted]', $redacted['pms_token']);
        $this->assertSame('[redacted]', $redacted['nested']['Authorization']);
        $this->assertSame('failed with Bearer [redacted]', $redacted['message']);
        $this->assertSame('r1', $redacted['run']);
    }

    public function test_config_file_environment_and_token_command(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'agentenv');
        file_put_contents($file, "# comment\nPMS_URL=https://a.test\nPMS_TOKEN_COMMAND=echo from-keychain\nORG_UID='org-9'\n");
        $config = Config::load($file);

        $this->assertSame('https://a.test', $config->get('PMS_URL'));
        $this->assertSame('org-9', $config->get('ORG_UID'));
        $this->assertSame('from-keychain', $config->pmsToken());
        unlink($file);
    }

    // ---- PMS client retries ----------------------------------------------------------------------

    public function test_pms_client_retries_5xx_but_not_4xx(): void
    {
        $flaky = new FakePms(fn($m, $p, $b, $n) => $n < 3 ? [503, []] : [200, ['ok' => true]]);
        $this->assertSame(200, $flaky->get('/x')['status']);
        $this->assertCount(3, $flaky->requests);

        $conflict = new FakePms(fn() => [409, ['message' => 'busy']]);
        $this->assertSame(409, $conflict->post('/x')['status']);
        $this->assertCount(1, $conflict->requests);
    }

    // ---- Run flow -------------------------------------------------------------------------------------

    public function test_full_cycle_reports_every_search_and_ingests_enrichment_without_scores(): void
    {
        $pms = $this->pms();
        $claude = new FakeClaude($this->config(), $this->searchAnswer());
        $exit = (new Worker($this->config(), $pms, $claude, new Logger(sys_get_temp_dir() . '/upwork-agent-test-logs')))->run();

        $this->assertSame(0, $exit);
        $this->assertCount(3, $this->paths($pms, '/queries'), 'one report per search');
        $ingest = $this->paths($pms, '/api/upwork/opportunities');
        $this->assertCount(1, $ingest);
        $this->assertArrayNotHasKey('score', $ingest[0][2], 'the agent never sends scores');
        $this->assertArrayNotHasKey('recommendation', $ingest[0][2]);
        $complete = $this->paths($pms, '/complete')[0][2];
        $this->assertSame(['status' => 'completed', 'enriched' => 1, 'enrich_errors' => 0, 'error' => null], $complete);
        $this->assertStringContainsString('org-1', $claude->prompts[0]);
        $this->assertStringNotContainsString('secret-token', implode("\n", $claude->prompts));
    }

    public function test_disabled_or_not_due_agent_does_nothing(): void
    {
        foreach ([['enabled' => false], ['last_run' => gmdate('c', time() - 300)]] as $state) {
            $pms = $this->pms($state);
            $claude = new FakeClaude($this->config(), $this->searchAnswer());
            $this->assertSame(0, (new Worker($this->config(), $pms, $claude, new Logger(sys_get_temp_dir())))->run());
            $this->assertCount(1, $pms->requests, 'only the config was read');
            $this->assertSame([], $claude->prompts, 'Claude (and Upwork) never called');
        }

        // A manual run ignores the schedule (for testing) but still needs a PMS lease.
        $pms = $this->pms(['enabled' => false]);
        (new Worker($this->config(), $pms, new FakeClaude($this->config(), $this->searchAnswer()), new Logger(sys_get_temp_dir())))->run('manual');
        $this->assertSame('manual', $this->paths($pms, '/api/upwork/agent/runs')[0][2]['trigger']);
    }

    public function test_refused_run_is_not_an_error(): void
    {
        foreach ([409, 423] as $status) {
            $pms = $this->pms(['start_status' => $status]);
            $claude = new FakeClaude($this->config(), $this->searchAnswer());
            $this->assertSame(0, (new Worker($this->config(), $pms, $claude, new Logger(sys_get_temp_dir())))->run());
            $this->assertSame([], $claude->prompts);
        }
    }

    public function test_claude_failure_is_reported_so_the_pms_backs_off(): void
    {
        $pms = $this->pms();
        $claude = new FakeClaude($this->config(), fn() => ['ok' => false, 'rate_limited' => false, 'error' => 'MCP server unavailable', 'data' => null, 'cost_usd' => null]);
        (new Worker($this->config(), $pms, $claude, new Logger(sys_get_temp_dir())))->run();

        $reports = $this->paths($pms, '/queries');
        $this->assertCount(3, $reports);
        foreach ($reports as $report) {
            $this->assertSame('error', $report[2]['query']['status']);
            $this->assertSame('MCP server unavailable', $report[2]['query']['error']);
        }
        $this->assertSame('completed', $this->paths($pms, '/complete')[0][2]['status']);
    }

    public function test_rate_limit_stops_the_run_and_leaves_later_profiles_due(): void
    {
        $pms = $this->pms();
        $claude = new FakeClaude($this->config(), $this->searchAnswer(['rate_limited']));
        (new Worker($this->config(), $pms, $claude, new Logger(sys_get_temp_dir())))->run();

        $this->assertCount(1, $claude->prompts, 'second profile never searched');
        $this->assertSame('rate_limited', $this->paths($pms, '/queries')[0][2]['query']['status']);
        $this->assertCount(0, $this->paths($pms, '/api/upwork/opportunities'), 'no enrichment after a rate limit');
        $this->assertSame('Stopped early: Upwork rate limit', $this->paths($pms, '/complete')[0][2]['error']);
    }

    public function test_local_lock_prevents_overlapping_workers(): void
    {
        $config = $this->config();
        $lock = fopen($config->get('LOCK_FILE'), 'c');
        flock($lock, LOCK_EX);

        $pms = $this->pms();
        $this->assertSame(0, (new Worker($config, $pms, new FakeClaude($config, $this->searchAnswer()), new Logger(sys_get_temp_dir())))->run());
        $this->assertSame([], $pms->requests, 'second process exits without touching the PMS');

        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
