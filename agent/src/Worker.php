<?php

namespace Code4Each\UpworkAgent;

/**
 * One discovery cycle:
 *   lock -> PMS config -> enabled & due? -> start run (lease + plan)
 *   -> per profile: Claude runs the read-only searches -> report each page
 *   -> enrich the jobs the PMS flags (Claude find_jobs get) -> ingest
 *   -> complete run.
 * The worker holds no business rules; the PMS decides what to search and how to score.
 */
class Worker
{
    /** @var Config */
    private $config;
    /** @var PmsClient */
    private $pms;
    /** @var ClaudeRunner */
    private $claude;
    /** @var Logger */
    private $log;
    /** @var string */
    private $promptDir;

    public function __construct(Config $config, PmsClient $pms, ClaudeRunner $claude, Logger $log, ?string $promptDir = null)
    {
        $this->config = $config;
        $this->pms = $pms;
        $this->claude = $claude;
        $this->log = $log;
        $this->promptDir = $promptDir ?? __DIR__ . '/..';
    }

    /** @return int process exit code (0 = fine, including "nothing to do") */
    public function run(string $trigger = 'scheduled'): int
    {
        $lock = fopen($this->config->get('LOCK_FILE', sys_get_temp_dir() . '/code4each-upwork-agent.lock'), 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            $this->log->info('cycle.skipped', ['reason' => 'another worker process is running']);
            return 0;
        }

        try {
            return $this->cycle($trigger);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function cycle(string $trigger): int
    {
        $config = $this->pms->get('/api/upwork/agent/config');
        if ($config['status'] !== 200 || empty($config['body']['data'])) {
            $this->log->error('config.unavailable', ['status' => $config['status']]);
            return 1;
        }
        $settings = $config['body']['data'];
        $catalog = array_column($settings['catalog'] ?? [], 'slug');

        if ($trigger === 'scheduled') {
            if (empty($settings['agent']['enabled'])) {
                $this->log->info('cycle.skipped', ['reason' => 'agent disabled in the PMS']);
                return 0;
            }
            $last = $settings['last_run_started_at'] ?? null;
            $interval = (int) ($settings['agent']['interval_minutes'] ?? 20);
            if ($last && (time() - strtotime($last)) < ($interval * 60 - 30)) {
                $this->log->info('cycle.skipped', ['reason' => 'not due', 'last_run_started_at' => $last, 'interval_minutes' => $interval]);
                return 0;
            }
        }

        $start = $this->pms->post('/api/upwork/agent/runs', ['trigger' => $trigger, 'agent_host' => $this->config->get('AGENT_HOST', gethostname())]);
        if ($start['status'] !== 201) {
            $this->log->info('run.refused', ['status' => $start['status'], 'message' => $start['body']['message'] ?? null]);
            return in_array($start['status'], [409, 423], true) ? 0 : 1;
        }
        $run = $start['body']['data']['run']['uuid'];
        $plan = $start['body']['data']['plan'] ?? [];
        $limits = $start['body']['data']['limits'] ?? [];
        $this->log->info('run.started', ['run' => $run, 'profiles' => count($plan), 'queries' => array_sum(array_map(fn($p) => count($p['queries']), $plan))]);

        $enrich = [];
        $rateLimited = false;
        $fatal = null;

        try {
            foreach ($plan as $profile) {
                if ($rateLimited) {
                    break; // remaining profiles stay due for the next run
                }
                $this->pms->post("/api/upwork/agent/runs/{$run}/heartbeat");
                $rateLimited = $this->searchProfile($run, $profile, $enrich);
            }

            $enrichLimit = (int) ($limits['enrich_limit_per_run'] ?? 15);
            [$enriched, $enrichErrors] = $rateLimited ? [0, 0] : $this->enrich($run, array_slice(array_values(array_unique($enrich)), 0, $enrichLimit), $catalog);
        } catch (\Throwable $e) {
            $fatal = $e->getMessage();
            $enriched = $enrichErrors = 0;
            $this->log->error('run.error', ['run' => $run, 'error' => $fatal]);
        }

        $complete = $this->pms->post("/api/upwork/agent/runs/{$run}/complete", [
            'status' => $fatal ? 'failed' : 'completed',
            'enriched' => $enriched,
            'enrich_errors' => $enrichErrors,
            'error' => $fatal ?? ($rateLimited ? 'Stopped early: Upwork rate limit' : null),
        ]);
        $this->log->info('run.completed', ['run' => $run, 'status' => $complete['status'], 'stats' => $complete['body']['data']['stats'] ?? null]);

        return $fatal ? 1 : 0;
    }

    /** @return bool true when Upwork rate-limited us (stop the run) */
    private function searchProfile(string $run, array $profile, array &$enrich): bool
    {
        $queries = $profile['queries'];
        $prompt = $this->render('search', [
            'ORG_UID' => $this->config->require('ORG_UID'),
            'PROFILE_NAME' => $profile['name'],
            'QUERIES' => implode("\n", array_map(fn($q, $i) => sprintf('%d. type=%s pages=%d params=%s', $i, $q['type'], $q['pages'], json_encode($q['params'], JSON_UNESCAPED_SLASHES)), $queries, array_keys($queries))),
            'KNOWN_IDS' => json_encode($profile['known_job_ids'] ?? []),
        ]);

        $result = $this->claude->run($prompt, $this->schema('search'));
        $reported = [];

        if ($result['ok']) {
            foreach ($result['data']['queries'] ?? [] as $page) {
                $index = (int) ($page['index'] ?? -1);
                if (!isset($queries[$index])) {
                    continue;
                }
                $reported[$index] = true;
                $response = $this->report($run, $profile, $queries[$index], (int) ($page['page'] ?? 1), $page['status'] ?? 'ok', $page['error'] ?? null, $page['jobs'] ?? []);
                foreach ($response['enrich'] ?? [] as $id) {
                    $enrich[] = $id;
                }
                if (($page['status'] ?? 'ok') === 'rate_limited') {
                    return true;
                }
            }
        }

        // Searches Claude did not report on are recorded as failed so the PMS backs the profile off.
        foreach ($queries as $index => $query) {
            if (!isset($reported[$index])) {
                $status = $result['rate_limited'] ? 'rate_limited' : 'error';
                $this->report($run, $profile, $query, 1, $status, $result['error'] ?? 'Not reported by Claude', []);
            }
        }

        return $result['rate_limited'];
    }

    private function report(string $run, array $profile, array $query, int $page, string $status, ?string $error, array $jobs): array
    {
        $response = $this->pms->post("/api/upwork/agent/runs/{$run}/queries", [
            'query' => [
                'search_profile_id' => $profile['search_profile_id'], 'type' => $query['type'], 'text' => $query['text'],
                'params' => $query['params'], 'page' => $page, 'status' => $status, 'error' => $error,
            ],
            'jobs' => array_slice($jobs, 0, 50),
        ]);
        if ($response['status'] !== 200) {
            $this->log->warning('query.report_failed', ['run' => $run, 'status' => $response['status'], 'query' => $query['text']]);
            return [];
        }
        $data = $response['body']['data'] ?? [];
        $this->log->info('query.reported', ['run' => $run, 'profile' => $profile['slug'], 'query' => $query['text'], 'page' => $page, 'status' => $status,
            'rows' => count($jobs), 'invalid' => count($data['invalid'] ?? []), 'enrich' => count($data['enrich'] ?? [])]);

        return $data;
    }

    /** @return array{0: int, 1: int} [enriched, errors] */
    private function enrich(string $run, array $jobIds, array $catalog): array
    {
        $enriched = $errors = 0;
        foreach (array_chunk($jobIds, max(1, (int) $this->config->get('ENRICH_BATCH_SIZE', 5))) as $batch) {
            $this->pms->post("/api/upwork/agent/runs/{$run}/heartbeat");
            $result = $this->claude->run($this->render('enrich', [
                'ORG_UID' => $this->config->require('ORG_UID'),
                'JOB_IDS' => json_encode($batch),
                'CATALOG' => implode(', ', $catalog),
            ]), $this->schema('enrich'));

            if (!$result['ok']) {
                $errors += count($batch);
                if ($result['rate_limited']) {
                    break;
                }
                continue;
            }

            foreach ($result['data']['jobs'] ?? [] as $job) {
                if (empty($job['ok']) || empty($job['payload'])) {
                    $errors++;
                    if (($job['error'] ?? null) === 'rate_limited') {
                        return [$enriched, $errors];
                    }
                    continue;
                }
                $payload = $job['payload'];
                unset($payload['score'], $payload['recommendation']); // the PMS scores; never the agent
                $response = $this->pms->post('/api/upwork/opportunities', $payload);
                if (in_array($response['status'], [200, 201], true)) {
                    $enriched++;
                } else {
                    $errors++;
                    $this->log->warning('enrich.ingest_failed', ['run' => $run, 'job' => $job['upwork_job_id'], 'status' => $response['status'],
                        'errors' => $response['body']['errors'] ?? null]);
                }
            }
        }

        return [$enriched, $errors];
    }

    private function render(string $name, array $vars): string
    {
        $template = (string) file_get_contents("{$this->promptDir}/prompts/{$name}.md");

        return strtr($template, array_combine(array_map(fn($k) => '{{' . $k . '}}', array_keys($vars)), array_values($vars)));
    }

    private function schema(string $name): array
    {
        return json_decode((string) file_get_contents("{$this->promptDir}/schemas/{$name}.json"), true);
    }
}
