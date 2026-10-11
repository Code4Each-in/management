<?php

namespace Code4Each\UpworkAgent;

/**
 * Runs headless Claude Code (`claude -p`) with ONLY the read-only Upwork MCP
 * tools available, a JSON schema for the answer, a cost cap and a timeout.
 * The PMS token is never passed to Claude. Claude runs in an empty working
 * directory so it cannot read local files.
 */
class ClaudeRunner
{
    /** The only tools Claude may use. */
    const ALLOWED_TOOLS = [
        'mcp__upwork__upwork__find_jobs',
        'mcp__upwork__upwork__list_accounts',
    ];

    /** Explicitly denied as well (defence in depth on top of the allowlist and user deny rules). */
    const DISALLOWED_TOOLS = [
        // Built-in tools: no files, shell, web or sub-agents.
        'Bash', 'Read', 'Write', 'Edit', 'MultiEdit', 'NotebookEdit', 'Glob', 'Grep', 'LS', 'WebFetch', 'WebSearch', 'Task', 'Agent', 'TodoWrite',
        // Every Upwork write tool.
        'mcp__upwork__upwork__manage_proposals', 'mcp__upwork__upwork__send_message', 'mcp__upwork__upwork__save_job',
        'mcp__upwork__upwork__update_profile', 'mcp__upwork__upwork__update_agency', 'mcp__upwork__upwork__boost_profile',
        'mcp__upwork__upwork__respond_to_offer', 'mcp__upwork__upwork__submit_milestones', 'mcp__upwork__upwork__manage_meetings',
        'mcp__upwork__upwork__start_attachment_upload', 'mcp__upwork__upwork__confirm_attachment_upload', 'mcp__upwork__upwork__store_uploaded_files',
        'mcp__upwork__upwork__confirm_draft', 'mcp__upwork__upwork__confirm_preview', 'mcp__upwork__upwork__set_tool_mode',
        'mcp__upwork__upwork__set_tool_permission', 'mcp__upwork__upwork__agency_rooms',
    ];

    const RATE_LIMIT_PATTERNS = ['/rate.?limit/i', '/\b429\b/', '/too many requests/i', '/overloaded/i'];

    /** @var Config */
    private $config;
    /** @var Logger */
    private $log;

    public function __construct(Config $config, Logger $log)
    {
        $this->config = $config;
        $this->log = $log;
    }

    public function command(string $prompt, array $schema): array
    {
        $cmd = [
            $this->config->get('CLAUDE_BIN', 'claude'), '-p', $prompt,
            '--output-format', 'json',
            '--json-schema', json_encode($schema, JSON_UNESCAPED_SLASHES),
            '--strict-mcp-config', '--mcp-config', $this->config->get('MCP_CONFIG', __DIR__ . '/../mcp.json'),
            '--allowedTools', implode(',', self::ALLOWED_TOOLS),
            '--disallowedTools', implode(',', self::DISALLOWED_TOOLS),
            '--no-session-persistence',
            '--max-budget-usd', (string) $this->config->get('CLAUDE_MAX_BUDGET_USD', '0.50'),
        ];
        if ($model = $this->config->get('CLAUDE_MODEL')) {
            array_push($cmd, '--model', $model);
        }

        return $cmd;
    }

    /**
     * @return array{ok: bool, data: array|null, rate_limited: bool, error: string|null, cost_usd: float|null}
     */
    public function run(string $prompt, array $schema): array
    {
        $workdir = $this->config->get('CLAUDE_WORKDIR', sys_get_temp_dir() . '/upwork-agent-work');
        if (!is_dir($workdir)) {
            @mkdir($workdir, 0700, true);
        }
        $timeout = (int) $this->config->get('CLAUDE_TIMEOUT_SECONDS', 600);

        $started = microtime(true);
        [$exit, $stdout, $stderr, $timedOut] = $this->execute($this->command($prompt, $schema), $workdir, $timeout);
        $duration = (int) round((microtime(true) - $started) * 1000);

        $result = self::parse($stdout, $stderr, $exit, $timedOut);
        $this->log->info('claude.run', ['ok' => $result['ok'], 'exit' => $exit, 'timed_out' => $timedOut, 'rate_limited' => $result['rate_limited'],
            'duration_ms' => $duration, 'cost_usd' => $result['cost_usd'], 'error' => $result['error']]);

        return $result;
    }

    /** Parse `claude -p --output-format json` output into the structured answer. */
    public static function parse(?string $stdout, ?string $stderr, int $exit, bool $timedOut): array
    {
        $out = ['ok' => false, 'data' => null, 'rate_limited' => false, 'error' => null, 'cost_usd' => null];
        $text = (string) $stdout . "\n" . (string) $stderr;

        if ($timedOut) {
            $out['error'] = 'Claude timed out';
            return $out;
        }

        $envelope = json_decode(trim((string) $stdout), true);
        if (is_array($envelope)) {
            $out['cost_usd'] = isset($envelope['total_cost_usd']) ? (float) $envelope['total_cost_usd'] : null;
            $data = $envelope['structured_output'] ?? null;
            if ($data === null && isset($envelope['result']) && is_string($envelope['result'])) {
                $data = self::extractJson($envelope['result']);
            }
            if (!empty($envelope['is_error']) || ($envelope['subtype'] ?? 'success') !== 'success') {
                $out['error'] = 'Claude returned ' . ($envelope['subtype'] ?? 'an error') . (isset($envelope['result']) && is_string($envelope['result']) ? ': ' . substr($envelope['result'], 0, 300) : '');
            } elseif (is_array($data)) {
                $out['ok'] = true;
                $out['data'] = $data;
            } else {
                $out['error'] = 'Claude answer was not the expected JSON';
            }
        } else {
            $out['error'] = 'Unreadable Claude output (exit ' . $exit . '): ' . substr(trim((string) $stderr ?: (string) $stdout), 0, 300);
        }

        foreach (self::RATE_LIMIT_PATTERNS as $pattern) {
            if (!$out['ok'] && preg_match($pattern, $text)) {
                $out['rate_limited'] = true;
            }
        }

        return $out;
    }

    /** First JSON object in a text answer (plain or ```json fenced). */
    public static function extractJson(string $text): ?array
    {
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $m)) {
            $text = $m[1];
        }
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false) {
            return null;
        }
        $data = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($data) ? $data : null;
    }

    /** @return array{0: int, 1: string, 2: string, 3: bool} [exit code, stdout, stderr, timed out] */
    protected function execute(array $cmd, string $cwd, int $timeout): array
    {
        $process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
        if (!is_resource($process)) {
            return [1, '', 'Could not start Claude', false];
        }
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = $stderr = '';
        $deadline = time() + $timeout;
        $timedOut = false;
        while (true) {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if (time() > $deadline) {
                proc_terminate($process, 15);
                $timedOut = true;
                break;
            }
            usleep(200000);
        }
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);

        return [isset($status['exitcode']) && $status['exitcode'] >= 0 ? $status['exitcode'] : $exit, $stdout, $stderr, $timedOut];
    }
}
