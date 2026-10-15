<?php

namespace App\Services\Upwork\Discovery;

use App\Models\UpworkDiscoveryQuery;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkOpportunitySighting;
use App\Models\UpworkRuleset;
use App\Models\UpworkSearchProfile;
use App\Models\UpworkSetting;
use App\Models\UpworkTechnology;
use App\Services\Upwork\Scoring\RuleEvaluator;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Orchestrates discovery for the Mac mini agent. The PMS owns all business
 * configuration; the agent only executes the read-only searches it is given
 * and reports what came back.
 *
 *   config -> start run (lease + plan) -> report each query's rows
 *          -> agent enriches the jobs the PMS flags -> complete run
 */
class DiscoveryService
{
    /** Upwork MCP tools the worker may call. Everything else stays denied. */
    const ALLOWED_UPWORK_TOOLS = ['find_jobs', 'list_accounts'];
    const ALLOWED_FIND_JOBS_ACTIONS = ['search', 'get'];

    /** @var UpworkOpportunityService */
    private $opportunities;
    /** @var QueryPlanner */
    private $planner;

    public function __construct(UpworkOpportunityService $opportunities, QueryPlanner $planner)
    {
        $this->opportunities = $opportunities;
        $this->planner = $planner;
    }

    /** Everything the agent needs to know, read fresh from the PMS each cycle. */
    public function config(): array
    {
        $settings = UpworkSetting::allWithDefaults();
        $ruleset = UpworkRuleset::current();

        return [
            'agent' => [
                'enabled' => (bool) $settings['agent.enabled'],
                'interval_minutes' => (int) $settings['agent.interval_minutes'],
                'lease_minutes' => (int) $settings['agent.lease_minutes'],
                'max_backoff_minutes' => (int) $settings['agent.max_backoff_minutes'],
                'enrich_limit_per_run' => (int) $settings['agent.enrich_limit_per_run'],
            ],
            'discovery' => [
                'max_queries_per_profile' => (int) $settings['discovery.max_queries_per_profile'],
                'max_queries_per_run' => (int) $settings['discovery.max_queries_per_run'],
                'enrich_min_score' => (float) $settings['discovery.enrich_min_score'],
            ],
            // Lets a frequently-started worker exit early when the next cycle is not due yet.
            'last_run_started_at' => optional(optional(UpworkDiscoveryRun::latest('id')->first())->started_at)->toIso8601String(),
            'ruleset' => $ruleset ? ['version' => $ruleset->version, 'thresholds' => $ruleset->thresholds] : null,
            'upwork' => [
                'mode' => 'read_only',
                'allowed_tools' => self::ALLOWED_UPWORK_TOOLS,
                'allowed_find_jobs_actions' => self::ALLOWED_FIND_JOBS_ACTIONS,
            ],
            // Vocabulary for the agent's semantic analysis: it must answer with these slugs only.
            'catalog' => UpworkTechnology::enabled()->with('aliases')->orderBy('name')->get()->map(fn($t) => [
                'slug' => $t->slug, 'name' => $t->name, 'tier' => $t->tier, 'category' => $t->category,
                'aliases' => $t->aliases->pluck('alias')->values()->all(),
            ])->all(),
            'profiles' => UpworkSearchProfile::ordered()->get()->map(fn($p) => [
                'id' => $p->id, 'slug' => $p->slug, 'name' => $p->name, 'version' => $p->version,
                'enabled' => $p->enabled, 'priority' => $p->priority, 'frequency_minutes' => $p->frequency_minutes,
                'due' => $this->isDue($p), 'backoff_until' => optional($p->backoff_until)->toIso8601String(),
                'last_run_at' => optional($p->last_run_at)->toIso8601String(),
            ])->all(),
        ];
    }

    public function isDue(UpworkSearchProfile $profile, ?Carbon $now = null): bool
    {
        $now = $now ?? now();

        if (!$profile->enabled || ($profile->backoff_until && $profile->backoff_until->isAfter($now))) {
            return false;
        }

        // One minute of slack so a profile with the same frequency as the agent interval is not skipped.
        return !$profile->last_run_at || $profile->last_run_at->copy()->addMinutes(max(1, $profile->frequency_minutes - 1))->lte($now);
    }

    /**
     * Acquire the run lease and hand out the query plan.
     *
     * @throws DiscoveryException 423 when the agent is disabled (scheduled runs), 409 when another run holds the lease
     */
    public function startRun(?int $userId, ?string $agentHost, string $trigger = 'scheduled'): UpworkDiscoveryRun
    {
        if ($trigger === 'scheduled' && !UpworkSetting::get('agent.enabled')) {
            throw new DiscoveryException('The Upwork agent is disabled in the PMS.', 423);
        }

        UpworkSetting::firstOrCreate(['key' => 'discovery.lock'], ['value' => null]);

        return DB::transaction(function () use ($userId, $agentHost, $trigger) {
            // Serialize run starts: only one transaction at a time gets past this row lock.
            UpworkSetting::where('key', 'discovery.lock')->lockForUpdate()->first();

            foreach (UpworkDiscoveryRun::where('status', UpworkDiscoveryRun::STATUS_RUNNING)->get() as $running) {
                if ($running->isActive()) {
                    throw new DiscoveryException("Discovery run {$running->uuid} is still running (lease until {$running->lease_expires_at->toIso8601String()}).", 409);
                }
                $running->forceFill([
                    'status' => UpworkDiscoveryRun::STATUS_ABANDONED,
                    'finished_at' => now(),
                    'error' => 'Lease expired without completion (agent crashed or timed out).',
                ])->save();
            }

            $plan = $this->buildPlan();

            return UpworkDiscoveryRun::create([
                'uuid' => (string) Str::uuid(),
                'status' => UpworkDiscoveryRun::STATUS_RUNNING,
                'trigger' => $trigger,
                'agent_host' => $agentHost ? Str::limit($agentHost, 100, '') : null,
                'ruleset_version' => optional(UpworkRuleset::current())->version,
                'started_at' => now(),
                'heartbeat_at' => now(),
                'lease_expires_at' => now()->addMinutes((int) UpworkSetting::get('agent.lease_minutes')),
                'plan' => $plan,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Tier-based, rotating plan: each tier has a search allowance per run; within a
     * tier the profile that ran longest ago goes first. Every profile has a daily
     * call budget, and there are global per-run and per-day caps.
     */
    private function buildPlan(): array
    {
        $runBudget = (int) UpworkSetting::get('discovery.max_queries_per_run');
        $dayLeft = max(0, (int) UpworkSetting::get('discovery.max_searches_per_day') - $this->searchesSince(now()->startOfDay()));
        $tierAllowance = (array) UpworkSetting::get('discovery.tier_searches_per_run');
        $perProfileCap = (int) UpworkSetting::get('discovery.max_queries_per_profile');
        $knownIds = (int) UpworkSetting::get('discovery.known_ids_per_profile');
        $plan = [];

        foreach (array_keys(UpworkSearchProfile::TIERS) as $tier) {
            $due = UpworkSearchProfile::enabled()->where('tier', $tier)
                ->orderByRaw('last_run_at IS NOT NULL')->orderBy('last_run_at')->orderBy('priority')->get()
                ->filter(fn($p) => $this->isDue($p))->values();
            $allowance = (int) ($tierAllowance[$tier] ?? 0);
            if ($due->isEmpty() || $allowance <= 0) {
                continue;
            }
            $share = max(1, (int) ceil($allowance / $due->count()));

            foreach ($due as $profile) {
                $profileLeft = max(0, (int) $profile->daily_call_budget - $this->searchesSince(now()->startOfDay(), $profile->id));
                $take = min($share, $allowance, $perProfileCap, $profileLeft, $runBudget, $dayLeft);
                if ($take <= 0) {
                    continue;
                }

                $slice = $this->planner->plan($profile, $take);
                if (!$slice['queries']) {
                    continue;
                }
                $used = count($slice['queries']);
                $allowance -= $used;
                $runBudget -= $used;
                $dayLeft -= $used;

                $plan[] = [
                    'search_profile_id' => $profile->id,
                    'slug' => $profile->slug,
                    'name' => $profile->name,
                    'tier' => $tier,
                    'version' => $profile->version,
                    'queries' => $slice['queries'],
                    'next_cursor' => $slice['next_cursor'], // applied on completion, only if the profile actually ran
                    'known_job_ids' => UpworkOpportunity::whereIn('id', UpworkOpportunitySighting::where('search_profile_id', $profile->id)
                            ->orderByDesc('first_seen_at')->limit($knownIds)->pluck('opportunity_id'))
                        ->pluck('upwork_job_id')->values()->all(),
                ];
            }
        }

        return $plan;
    }

    /**
     * What the next run would do, without doing it: the same plan "Run
     * discovery now" would build at this moment, read from the database only.
     * No run is created, no cursor moves, Upwork is not called. How many jobs
     * are new cannot be known without searching, so the expected count is an
     * ESTIMATE from the last 7 days' new jobs per search.
     */
    public function preview(): array
    {
        $plan = $this->buildPlan();
        $searches = array_sum(array_map(fn($p) => count($p['queries']), $plan));

        $recent = UpworkDiscoveryQuery::where('created_at', '>=', now()->subDays(7))->where('status', 'ok');
        $pastSearches = (clone $recent)->count();
        $pastNew = (int) (clone $recent)->sum('new_count');
        $perSearch = $pastSearches ? $pastNew / $pastSearches : null;

        return [
            'searches' => $searches,
            'profiles' => array_map(fn($p) => ['name' => $p['name'], 'tier' => $p['tier'], 'searches' => count($p['queries'])], $plan),
            'searches_today' => $this->searchesSince(now()->startOfDay()),
            'max_per_day' => (int) UpworkSetting::get('discovery.max_searches_per_day'),
            'history' => ['searches' => $pastSearches, 'new' => $pastNew, 'per_search' => $perSearch !== null ? round($perSearch, 2) : null],
            'estimated_new' => $perSearch !== null ? (int) round($perSearch * $searches) : null,
            'archived' => UpworkOpportunity::where('status', UpworkOpportunity::STATUS_ARCHIVED)->count(),
        ];
    }

    /** Upwork searches recorded since $from (optionally for one profile). */
    public function searchesSince(Carbon $from, ?int $profileId = null): int
    {
        return UpworkDiscoveryQuery::where('created_at', '>=', $from)
            ->when($profileId, fn($q) => $q->where('search_profile_id', $profileId))
            ->count();
    }

    public function heartbeat(UpworkDiscoveryRun $run): UpworkDiscoveryRun
    {
        $this->assertActive($run);
        $run->forceFill([
            'heartbeat_at' => now(),
            'lease_expires_at' => now()->addMinutes((int) UpworkSetting::get('agent.lease_minutes')),
        ])->save();

        return $run;
    }

    /**
     * Record one executed query and its result rows (search rows, stage 1).
     *
     * @param array $query   search_profile_id, type, text, params, page, status, error, duration_ms
     * @param array $jobs    rows that already passed request validation
     * @param array $invalid [index => [errors]] rows rejected by validation (audited, not stored)
     * @return array per-job outcome for the agent, including whether to enrich it
     */
    public function recordQuery(UpworkDiscoveryRun $run, array $query, array $jobs, array $invalid, ?int $userId): array
    {
        $this->assertActive($run);

        $profile = UpworkSearchProfile::find($query['search_profile_id'] ?? null);
        $seenThisRun = UpworkOpportunitySighting::where('last_run_id', $run->id)->pluck('opportunity_id')->flip();
        $enrichMinScore = (float) UpworkSetting::get('discovery.enrich_min_score');

        $counts = ['new' => 0, 'existing' => 0, 'duplicate' => 0, 'ignored' => 0, 'hard_rejected' => 0];
        $results = [];

        foreach ($jobs as $row) {
            $ignoreReason = $profile ? $this->profileExclusion($profile, $row) : null;
            $note = ($profile ? "profile {$profile->slug} v{$profile->version}, " : '') . "run {$run->uuid}";

            [$opportunity, $created] = $this->opportunities->ingestSighting($row, $userId, $note);

            if ($created) {
                $counts['new']++;
            } elseif (isset($seenThisRun[$opportunity->id])) {
                $counts['duplicate']++;
            } else {
                $counts['existing']++;
            }
            $seenThisRun[$opportunity->id] = true;

            if ($profile) {
                $this->recordSighting($opportunity, $profile, $run, $ignoreReason);
            }
            if ($ignoreReason) {
                $counts['ignored']++;
            }
            $hardRejected = (bool) $opportunity->hard_filters;
            if ($hardRejected) {
                $counts['hard_rejected']++;
            }

            $opportunity->refresh();
            $results[] = [
                'upwork_job_id' => $opportunity->upwork_job_id,
                'opportunity_id' => $opportunity->id,
                'created' => $created,
                'status' => $opportunity->status,
                'stage' => $opportunity->stage,
                'score' => $opportunity->score,
                'recommendation' => $opportunity->recommendation,
                'hard_filters' => array_column($opportunity->score_explanation['hard_filters'] ?? [], 'key'),
                'ignored' => $opportunity->discovery_ignored,
                'enrich' => $opportunity->stage === UpworkOpportunity::STAGE_DISCOVERED
                    && !$hardRejected && !$opportunity->discovery_ignored
                    && $opportunity->status === UpworkOpportunity::STATUS_NEW
                    && $opportunity->score !== null && $opportunity->score >= $enrichMinScore,
            ];
        }

        UpworkDiscoveryQuery::create([
            'run_id' => $run->id,
            'search_profile_id' => $profile->id ?? null,
            'profile_version' => $profile->version ?? null,
            'query_type' => in_array($query['type'] ?? null, ['query', 'title'], true) ? $query['type'] : 'query',
            'query_text' => Str::limit((string) ($query['text'] ?? ''), 255, ''),
            'params' => $query['params'] ?? null,
            'page' => (int) ($query['page'] ?? 1),
            'status' => $query['status'] ?? 'ok',
            'results_count' => count($jobs) + count($invalid),
            'new_count' => $counts['new'],
            'existing_count' => $counts['existing'],
            'duplicate_count' => $counts['duplicate'],
            'ignored_count' => $counts['ignored'],
            'hard_rejected_count' => $counts['hard_rejected'],
            'error' => $query['error'] ?? ($invalid ? count($invalid) . ' invalid row(s): ' . Str::limit(json_encode($invalid), 900) : null),
            'duration_ms' => $query['duration_ms'] ?? null,
        ]);

        $this->heartbeat($run);

        return $results;
    }

    /**
     * Close the run, update each planned profile's run state (backoff on failure)
     * and store the aggregate stats.
     */
    public function completeRun(UpworkDiscoveryRun $run, string $status, array $report = []): UpworkDiscoveryRun
    {
        if ($run->status !== UpworkDiscoveryRun::STATUS_RUNNING) {
            throw new DiscoveryException("Run {$run->uuid} is already {$run->status}.", 409);
        }

        $interval = (int) UpworkSetting::get('agent.interval_minutes');
        $maxBackoff = (int) UpworkSetting::get('agent.max_backoff_minutes');
        $queries = $run->queries()->get();

        return DB::transaction(function () use ($run, $status, $report, $queries, $interval, $maxBackoff) {
            foreach ($run->plan ?? [] as $planned) {
                $profile = UpworkSearchProfile::find($planned['search_profile_id']);
                $ran = $queries->where('search_profile_id', $planned['search_profile_id']);
                if (!$profile || $ran->isEmpty()) {
                    continue; // never executed: stays due for the next run
                }

                $failed = $ran->whereIn('status', ['error', 'rate_limited']);
                $state = ['last_run_at' => now(), 'query_cursor' => $planned['next_cursor'] ?? $profile->query_cursor];
                if ($failed->isEmpty()) {
                    $state += ['last_success_at' => now(), 'consecutive_failures' => 0, 'backoff_until' => null, 'last_error' => null];
                } else {
                    $failures = $profile->consecutive_failures + 1;
                    $rateLimited = $failed->where('status', 'rate_limited')->isNotEmpty();
                    $minutes = min($maxBackoff, $interval * (2 ** ($failures - 1)) * ($rateLimited ? 2 : 1));
                    $state += [
                        'consecutive_failures' => $failures,
                        'backoff_until' => now()->addMinutes($minutes),
                        'last_error' => Str::limit(($rateLimited ? '[rate limited] ' : '') . (string) $failed->first()->error, 1000),
                    ];
                }
                $profile->forceFill($state)->saveQuietly();
            }

            $stats = [
                'queries' => $queries->count(),
                'failed_queries' => $queries->whereIn('status', ['error', 'rate_limited'])->count(),
                'found' => (int) $queries->sum('results_count'),
                'new' => (int) $queries->sum('new_count'),
                'existing' => (int) $queries->sum('existing_count'),
                'duplicates' => (int) $queries->sum('duplicate_count'),
                'ignored' => (int) $queries->sum('ignored_count'),
                'hard_rejected' => (int) $queries->sum('hard_rejected_count'),
                'enriched' => (int) ($report['enriched'] ?? 0),
                'enrich_errors' => (int) ($report['enrich_errors'] ?? 0),
            ];

            $run->forceFill([
                'status' => $status === 'failed' ? UpworkDiscoveryRun::STATUS_FAILED : UpworkDiscoveryRun::STATUS_COMPLETED,
                'finished_at' => now(),
                'lease_expires_at' => now(),
                'stats' => $stats,
                'error' => isset($report['error']) ? Str::limit((string) $report['error'], 2000) : null,
            ])->save();

            return $run;
        });
    }

    /** Why this profile would ignore a row (excluded / required keywords), or null. */
    public function profileExclusion(UpworkSearchProfile $profile, array $row): ?string
    {
        $text = trim(($row['title'] ?? '') . "\n" . ($row['description'] ?? '') . "\n" . implode(', ', (array) ($row['raw_data']['skills'] ?? [])));
        $evaluator = new RuleEvaluator();
        $ctx = ['job' => ['text' => $text]];

        foreach (['excluded_keywords' => 'excluded keyword', 'excluded_phrases' => 'excluded phrase'] as $field => $label) {
            foreach ($profile->{$field} ?? [] as $term) {
                if ($evaluator->matches(['field' => 'job.text', 'op' => 'contains_any', 'value' => [$term]], $ctx)) {
                    return "{$profile->name}: {$label} \"{$term}\"";
                }
            }
        }

        $required = $profile->required_keywords ?? [];
        if ($required && !$evaluator->matches(['field' => 'job.text', 'op' => 'contains_any', 'value' => $required], $ctx)) {
            return "{$profile->name}: none of the required keywords";
        }

        return null;
    }

    private function recordSighting(UpworkOpportunity $opportunity, UpworkSearchProfile $profile, UpworkDiscoveryRun $run, ?string $ignoreReason): void
    {
        $sighting = UpworkOpportunitySighting::firstOrNew(['opportunity_id' => $opportunity->id, 'search_profile_id' => $profile->id]);
        $now = now();

        if (!$sighting->exists) {
            $sighting->first_seen_at = $now;
            $sighting->times_seen = 0;
        }
        if ($sighting->last_run_id !== $run->id) {
            $sighting->times_seen++;
        }
        $sighting->fill([
            'profile_version' => $profile->version,
            'last_run_id' => $run->id,
            'last_seen_at' => $now,
            'ignored' => $ignoreReason !== null,
            'ignore_reason' => $ignoreReason,
        ])->save();

        // Ignored overall only when every profile that found the job excluded it.
        $ignoredEverywhere = !UpworkOpportunitySighting::where('opportunity_id', $opportunity->id)->where('ignored', false)->exists();
        if ($opportunity->discovery_ignored !== $ignoredEverywhere) {
            $opportunity->forceFill(['discovery_ignored' => $ignoredEverywhere])->saveQuietly();
        }
    }

    private function assertActive(UpworkDiscoveryRun $run): void
    {
        if (!$run->isActive()) {
            throw new DiscoveryException("Run {$run->uuid} is not active ({$run->status}" . ($run->status === 'running' ? ', lease expired' : '') . '). Start a new run.', 409);
        }
    }
}
