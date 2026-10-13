<?php

namespace App\Services\Upwork\Discovery;

use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkSetting;
use App\Services\Upwork\Api\Contracts\UpworkClientInterface;
use App\Services\Upwork\Api\UpworkClientFactory;
use App\Services\Upwork\Notifications\AlertService;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Support\Facades\Log;

/**
 * One discovery cycle on the Laravel server (cron -> scheduler -> upwork:discover),
 * with the same lease, plan, sightings and completion as the agent API:
 *
 *   start run (lease + tiered plan) -> read-only searches, page by page ->
 *   stage-1 sightings -> read-only detail lookups for promising jobs ->
 *   full ingestion (local scoring, AI queue) -> complete run (stats, backoff)
 *
 * Stops cleanly on Upwork rate limits and before the time budget runs out.
 * Alerts (AlertService) when scheduled discovery cannot reach Upwork, when
 * Upwork rejects the connection, or when a whole run fails; clears them after
 * a run with at least one successful search.
 */
class ServerDiscoveryRunner
{
    /** @var DiscoveryService */
    private $discovery;
    /** @var UpworkOpportunityService */
    private $opportunities;
    /** @var callable */
    private $pause;
    /** @var AlertService */
    private $alerts;

    public function __construct(DiscoveryService $discovery, UpworkOpportunityService $opportunities, ?callable $pause = null, ?AlertService $alerts = null)
    {
        $this->discovery = $discovery;
        $this->opportunities = $opportunities;
        $this->pause = $pause ?? function (int $ms) { usleep($ms * 1000); };
        $this->alerts = $alerts ?? new AlertService();
    }

    /** @return array{status: string, message: string, run: ?string, stats: array} */
    public function run(string $trigger = 'scheduled', ?UpworkClientInterface $client = null): array
    {
        $out = ['status' => 'skipped', 'message' => '', 'run' => null, 'stats' => []];

        if ($trigger === 'scheduled') {
            if (!UpworkSetting::get('agent.enabled')) {
                return ['message' => 'Discovery is switched off (Upwork → Configuration → Agent & discovery).'] + $out;
            }
            $last = UpworkDiscoveryRun::where('trigger', 'scheduled')->latest('id')->first();
            $interval = (int) UpworkSetting::get('agent.interval_minutes');
            $wait = self::minutesBetweenScheduledRuns();
            if ($last && $last->started_at && $last->started_at->gt(now()->subMinutes(max(1, $wait - 1)))) {
                $why = $wait < $interval ? "retry after failure in {$wait} min" : "interval {$interval} min";
                return ['message' => "Not due: last scheduled run started {$last->started_at->diffForHumans()} ({$why})."] + $out;
            }
        }

        $client = $client ?? UpworkClientFactory::make();
        if (!$client->isConfigured()) {
            $message = "Upwork source '{$client->name()}' is not connected (Upwork → Configuration → Agent & discovery).";
            if ($trigger === 'scheduled') {
                // Discovery is switched on but cannot run: someone needs to reconnect.
                $this->alerts->raise(AlertService::UPWORK_CONNECTION, 'Scheduled Upwork discovery cannot run: the Upwork connection is missing or expired', [
                    $message, 'No run was started. Reconnect, then the next scheduled run continues automatically.',
                ]);
            }
            return ['message' => $message] + $out; // no run record
        }

        if (!\App\Models\UpworkSearchProfile::enabled()->get()->contains(fn($p) => $this->discovery->isDue($p))) {
            return ['message' => 'No search profile is due yet.'] + $out; // no empty run records
        }

        try {
            $run = $this->discovery->startRun(null, 'server:' . $client->name(), $trigger);
        } catch (DiscoveryException $e) {
            return ['message' => $e->getMessage()] + $out;
        }

        $deadline = microtime(true) + max(30, (int) UpworkSetting::get('discovery.max_seconds_per_run'));
        $pauseMs = max(0, (int) UpworkSetting::get('discovery.api_pause_ms'));
        $stats = ['searches' => 0, 'failed_searches' => 0, 'rows' => 0, 'enrich_candidates' => 0, 'enriched' => 0, 'enrich_errors' => 0, 'stopped' => null];
        $lastError = null;
        $enrich = [];
        $searchRows = [];

        try {
            foreach ($run->plan ?? [] as $profile) {
                $known = array_flip($profile['known_job_ids'] ?? []);
                foreach ($profile['queries'] as $query) {
                    $cursor = null;
                    for ($page = 1; $page <= max(1, (int) ($query['pages'] ?? 1)); $page++) {
                        if (microtime(true) >= $deadline) {
                            $stats['stopped'] = 'time_budget';
                            break 3;
                        }
                        if ($stats['searches'] > 0) {
                            ($this->pause)($pauseMs);
                        }

                        $result = $client->search($query['params'], $cursor);
                        $stats['searches']++;
                        [$valid, $invalid] = $result->ok ? RowValidator::split($result->rows) : [[], []];
                        $status = $result->ok ? 'ok' : ($result->rateLimited() ? 'rate_limited' : 'error');

                        $results = $this->discovery->recordQuery($run, [
                            'search_profile_id' => $profile['search_profile_id'], 'type' => $query['type'], 'text' => $query['text'],
                            'params' => $query['params'], 'page' => $page, 'status' => $status,
                            'error' => $result->ok ? null : "{$result->errorCode}: {$result->error}", 'duration_ms' => $result->durationMs,
                        ], $valid, $invalid, null);

                        if ($result->rateLimited()) {
                            $stats['stopped'] = 'rate_limited';
                            break 3;
                        }
                        if (!$result->ok) {
                            $stats['failed_searches']++;
                            $lastError = "{$result->errorCode}: {$result->error}";
                            if ($result->errorCode === 'auth' || $result->errorCode === 'not_configured') {
                                $stats['stopped'] = 'auth'; // every further call would fail the same way
                                break 3;
                            }
                            break; // next query; the profile backs off on completion
                        }

                        $stats['rows'] += count($result->rows);
                        foreach ($valid as $row) {
                            $searchRows[UpworkOpportunityService::normalizeJobId($row['upwork_job_id'])] = $row;
                        }
                        foreach ($results as $r) {
                            if ($r['enrich']) {
                                $enrich[] = $r['upwork_job_id'];
                            }
                        }

                        // Stop paging once we reach jobs this profile already reported.
                        $reachedKnown = (bool) array_filter($result->rows, fn($row) => isset($known[UpworkOpportunityService::normalizeJobId((string) ($row['upwork_job_id'] ?? ''))]));
                        if (!$result->nextCursor || $reachedKnown) {
                            break;
                        }
                        $cursor = $result->nextCursor;
                    }
                }
            }

            if (!in_array($stats['stopped'], ['rate_limited', 'auth'], true)) {
                $enrich = array_slice(array_values(array_unique($enrich)), 0, (int) UpworkSetting::get('agent.enrich_limit_per_run'));
                $stats['enrich_candidates'] = count($enrich);
                foreach ($enrich as $jobId) {
                    if (microtime(true) >= $deadline) {
                        $stats['stopped'] = 'time_budget';
                        break;
                    }
                    ($this->pause)($pauseMs);

                    $detail = $client->job($jobId);
                    if ($detail->rateLimited()) {
                        $stats['stopped'] = 'rate_limited';
                        break;
                    }
                    if (!$detail->ok) {
                        $stats['enrich_errors']++;
                        if ($detail->errorCode === 'auth') {
                            $stats['stopped'] = 'auth';
                            $lastError = "{$detail->errorCode}: {$detail->error}";
                            break;
                        }
                        continue;
                    }

                    // Details win; search-only facts (client stats) fill the gaps.
                    $id = UpworkOpportunityService::normalizeJobId($jobId);
                    $row = array_merge($searchRows[$id] ?? [], $detail->rows[0]);
                    $row['raw_data'] = array_merge((array) ($searchRows[$id]['raw_data'] ?? []), (array) ($detail->rows[0]['raw_data'] ?? []));
                    [$valid, $invalid] = RowValidator::split([$row]);
                    if (!$valid) {
                        $stats['enrich_errors']++;
                        Log::warning('upwork.discovery.invalid_detail', ['job' => $jobId, 'errors' => $invalid[0]['errors'] ?? []]);
                        continue;
                    }
                    $this->opportunities->ingest($valid[0]);
                    $stats['enriched']++;
                }
            }

            $run = $this->discovery->completeRun($run, 'completed', [
                'enriched' => $stats['enriched'], 'enrich_errors' => $stats['enrich_errors'],
                'error' => $stats['stopped'] ? "Stopped early: {$stats['stopped']}." : null,
            ]);
        } catch (\Throwable $e) {
            Log::error('upwork.discovery.failed', ['run' => $run->uuid, 'error' => AlertService::scrub($e->getMessage())]);
            $run->refresh();
            if ($run->status === UpworkDiscoveryRun::STATUS_RUNNING) {
                $run = $this->discovery->completeRun($run, 'failed', ['error' => mb_substr(AlertService::scrub($e->getMessage()), 0, 2000)]);
            }
            $this->alerts->raise(AlertService::DISCOVERY_FAILED, 'Upwork discovery run failed', [
                "Run {$run->uuid} ({$trigger}) stopped with an error: " . $e->getMessage(), 'Local data is unchanged; the next run starts normally.',
            ]);

            return ['status' => 'failed', 'message' => AlertService::scrub($e->getMessage()), 'run' => $run->uuid, 'stats' => $stats];
        }

        $this->alertOutcome($run->uuid, $trigger, $stats, $lastError);

        return ['status' => 'completed', 'message' => $stats['stopped'] ? "Stopped early: {$stats['stopped']}." : 'OK', 'run' => $run->uuid, 'stats' => $stats];
    }

    /**
     * Minutes a scheduled run waits after the previous one: the interval, or a
     * shorter, doubling retry delay after consecutive failed/abandoned runs
     * (retry_after_failure_minutes, x2 per failure, never more than the interval).
     */
    public static function minutesBetweenScheduledRuns(): int
    {
        $interval = max(1, (int) UpworkSetting::get('agent.interval_minutes'));
        $failures = self::consecutiveScheduledFailures();
        if ($failures === 0) {
            return $interval;
        }
        $base = max(1, (int) UpworkSetting::get('discovery.retry_after_failure_minutes'));

        return (int) min($interval, $base * 2 ** min(10, $failures - 1));
    }

    /** Failed or abandoned scheduled runs since the last one that completed. */
    public static function consecutiveScheduledFailures(): int
    {
        $failures = 0;
        foreach (UpworkDiscoveryRun::where('trigger', 'scheduled')->where('status', '!=', UpworkDiscoveryRun::STATUS_RUNNING)
                     ->latest('id')->limit(12)->pluck('status') as $status) {
            if (!in_array($status, [UpworkDiscoveryRun::STATUS_FAILED, UpworkDiscoveryRun::STATUS_ABANDONED], true)) {
                break;
            }
            $failures++;
        }

        return $failures;
    }

    /**
     * Discovery health for the PMS: switches, last success, last failure
     * (error text scrubbed), consecutive failures and when the next scheduled
     * run is due.
     */
    public static function status(): array
    {
        $lastScheduled = UpworkDiscoveryRun::where('trigger', 'scheduled')->latest('id')->first();
        $lastFailure = UpworkDiscoveryRun::whereIn('status', [UpworkDiscoveryRun::STATUS_FAILED, UpworkDiscoveryRun::STATUS_ABANDONED])->latest('id')->first();
        $enabled = (bool) UpworkSetting::get('agent.enabled');
        $schedulerOn = (bool) config('upwork.scheduler.enabled');
        $wait = self::minutesBetweenScheduledRuns();

        return [
            'scheduler_enabled' => $schedulerOn,
            'enabled' => $enabled,
            'interval' => (int) UpworkSetting::get('agent.interval_minutes'),
            'last_run' => UpworkDiscoveryRun::latest('id')->first(),
            'last_success' => UpworkDiscoveryRun::where('status', UpworkDiscoveryRun::STATUS_COMPLETED)->latest('id')->first(),
            'last_failure' => $lastFailure,
            'last_failure_error' => $lastFailure && $lastFailure->error ? AlertService::scrub((string) $lastFailure->error) : null,
            'consecutive_failures' => self::consecutiveScheduledFailures(),
            'next_due_at' => $enabled && $schedulerOn
                ? (($lastScheduled && $lastScheduled->started_at) ? $lastScheduled->started_at->copy()->addMinutes($wait) : now())
                : null,
        ];
    }

    /** Alert on an auth stop or a run where every search failed; clear alerts after a healthy run. */
    private function alertOutcome(string $uuid, string $trigger, array $stats, ?string $lastError): void
    {
        if ($stats['stopped'] === 'auth') {
            $this->alerts->raise(AlertService::UPWORK_CONNECTION, 'Upwork rejected the discovery connection', [
                "Run {$uuid} ({$trigger}) stopped: {$lastError}", 'Reconnect under Upwork → Configuration → Agent & discovery.',
            ]);
            return;
        }
        $succeeded = $stats['searches'] - $stats['failed_searches'] - ($stats['stopped'] === 'rate_limited' ? 1 : 0);
        if ($stats['searches'] > 0 && $succeeded <= 0 && $stats['stopped'] !== 'rate_limited') {
            $this->alerts->raise(AlertService::DISCOVERY_FAILED, 'Every Upwork search in a discovery run failed', [
                "Run {$uuid} ({$trigger}): {$stats['failed_searches']} of {$stats['searches']} searches failed. Last error: {$lastError}",
            ]);
            return;
        }
        if ($succeeded > 0) {
            $this->alerts->resolve(AlertService::DISCOVERY_FAILED, 'Upwork discovery is running normally again');
            $this->alerts->resolve(AlertService::UPWORK_CONNECTION, 'The Upwork connection is working again');
        }
    }
}
