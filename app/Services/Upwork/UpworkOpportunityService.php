<?php

namespace App\Services\Upwork;

use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Services\Upwork\Clients\ClientIntelligenceService;
use App\Services\Upwork\Notifications\NotificationService;
use App\Services\Upwork\Scoring\ScoringEngine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Stores opportunities sent by the Claude Upwork agent and handles the human
 * approve/reject decisions. Purely local: nothing here calls Upwork.
 */
class UpworkOpportunityService
{
    /**
     * Fields the agent is allowed to write. Human-owned state (status, approval,
     * proposal fields) is never touched by ingestion.
     */
    const AGENT_FIELDS = [
        'upwork_url', 'title', 'description', 'job_type',
        'hourly_min', 'hourly_max', 'fixed_budget', 'experience_level', 'duration',
        'client_country', 'client_timezone', 'client_rating', 'client_reviews_count',
        'client_total_spent', 'client_hire_rate', 'client_jobs_posted', 'client_jobs_hired',
        'payment_verified', 'proposals_count', 'proposals_tier', 'interviews_count', 'connects_required',
        'contractor_type', 'workload', 'job_activity', 'agent_signals',
        'posted_at', 'fetched_at',
        'score', 'technical_fit_score', 'experience_fit_score', 'client_quality_score',
        'commercial_value_score', 'long_term_score', 'ai_automation_score', 'competition_score',
        'score_breakdown', 'scoring_version', 'hard_filters',
        'recommendation', 'match_summary', 'relevant_experience', 'risks', 'technologies',
        'raw_data',
    ];

    /** Fields the PMS scoring engine owns when it scores a job. */
    const ENGINE_FIELDS = [
        'score', 'technical_fit_score', 'experience_fit_score', 'client_quality_score', 'commercial_value_score',
        'long_term_score', 'ai_automation_score', 'competition_score', 'scoring_version', 'ruleset_version',
        'score_explanation', 'hard_filters', 'recommendation', 'classifications', 'strategic_score',
        'win_probability', 'contract_value_estimate', 'expected_value',
    ];

    /** Payload keys about the client that are not opportunity columns. */
    const CLIENT_EXTRAS = ['upwork_client_id', 'client_work_history', 'client_open_jobs'];

    /** @var ScoringEngine|null */
    private $engine;

    /** @var ClientIntelligenceService */
    private $clients;

    public function __construct(?ClientIntelligenceService $clients = null)
    {
        $this->clients = $clients ?? new ClientIntelligenceService();
    }

    /**
     * Create or update (deduplicated by upwork_job_id).
     *
     * When the payload carries no score, the PMS scores the job with the current
     * published ruleset. A payload with its own score keeps the legacy behaviour
     * (agent-scored) unchanged.
     *
     * @return array{0: UpworkOpportunity, 1: bool} [opportunity, wasCreated]
     */
    public function ingest(array $data, ?int $userId = null): array
    {
        $jobId = self::normalizeJobId($data['upwork_job_id']);
        $pmsScored = !isset($data['score']);
        if ($pmsScored) {
            $data = array_diff_key($data, array_flip(self::ENGINE_FIELDS));
        }
        $attributes = $this->prepareAttributes($data);
        $clientExtras = Arr::only($data, self::CLIENT_EXTRAS);

        try {
            $result = $this->upsert($jobId, $attributes, $userId, $pmsScored, $clientExtras);
        } catch (QueryException $e) {
            // Lost a race with a concurrent insert of the same job: retry as an update.
            if (!UpworkOpportunity::where('upwork_job_id', $jobId)->exists()) {
                throw $e;
            }
            $result = $this->upsert($jobId, $attributes, $userId, $pmsScored, $clientExtras);
        }

        app(NotificationService::class)->maybeNotify($result[0]);
        if ($pmsScored) {
            app(\App\Services\Upwork\Ai\Qualification\QualificationService::class)->queueIfEligible($result[0]);
        }

        return $result;
    }

    /**
     * Score an existing opportunity again with the current ruleset (for example
     * after an AI analysis). Saves and logs only real changes.
     */
    public function rescore(UpworkOpportunity $opportunity, ?int $userId = null, string $note = 'Re-scored'): bool
    {
        $changed = DB::transaction(function () use ($opportunity, $userId, $note) {
            $o = UpworkOpportunity::whereKey($opportunity->id)->lockForUpdate()->first();
            if (!$o || !$o->ruleset_version) {
                return false; // agent-scored legacy rows keep their score
            }
            $this->applyEngineScore($o, false);
            $this->ignoreEquivalentJson($o);
            if (!$o->isDirty()) {
                return false;
            }
            $fields = array_keys($o->getDirty());
            $o->save();
            $o->log('rescored', $userId, null, null, $note . ': ' . implode(', ', $fields));

            return true;
        });

        if ($changed) {
            app(NotificationService::class)->maybeNotify($opportunity->fresh());
        }

        return $changed;
    }

    /** What a search row may refresh on a job that already has full details. */
    const SIGHTING_REFRESH_FIELDS = ['proposals_count', 'proposals_tier'];

    /**
     * Stage 1 of discovery: store or refresh a job from a search-result row.
     *
     * New jobs are created as 'discovered' and scored with what the row reveals.
     * An existing job never loses richer data: once enriched, a search row only
     * refreshes competition figures; before that, it may add newly known facts.
     * Human-owned state is never touched. last_seen_at / times_seen always move;
     * only real changes are logged.
     *
     * @return array{0: UpworkOpportunity, 1: bool} [opportunity, wasCreated]
     */
    public function ingestSighting(array $data, ?int $userId = null, ?string $note = null): array
    {
        $jobId = self::normalizeJobId($data['upwork_job_id']);
        $data = array_diff_key($data, array_flip(self::ENGINE_FIELDS));
        $attributes = array_filter($this->prepareAttributes($data), fn($v) => $v !== null && $v !== '' && $v !== []);

        try {
            $result = $this->upsertSighting($jobId, $attributes, $userId, $note);
        } catch (QueryException $e) {
            if (!UpworkOpportunity::where('upwork_job_id', $jobId)->exists()) {
                throw $e;
            }
            $result = $this->upsertSighting($jobId, $attributes, $userId, $note);
        }

        app(NotificationService::class)->maybeNotify($result[0]);

        return $result;
    }

    private function upsertSighting(string $jobId, array $attributes, ?int $userId, ?string $note): array
    {
        return DB::transaction(function () use ($jobId, $attributes, $userId, $note) {
            $opportunity = UpworkOpportunity::where('upwork_job_id', $jobId)->lockForUpdate()->first();
            $now = now();

            if (!$opportunity) {
                $opportunity = new UpworkOpportunity(['upwork_job_id' => $jobId]);
                $opportunity->fill($attributes);
                $opportunity->stage = UpworkOpportunity::STAGE_DISCOVERED;
                $opportunity->first_seen_at = $now;
                $opportunity->last_seen_at = $now;
                $opportunity->times_seen = 1;
                $opportunity->fetched_at = $opportunity->fetched_at ?? $now;
                $this->applyEngineScore($opportunity, true);
                $opportunity->save();
                $opportunity->log('discovered', $userId, null, $opportunity->status, $note);

                return [$opportunity, true];
            }

            $opportunity->fill($opportunity->stage === UpworkOpportunity::STAGE_DISCOVERED
                ? $attributes
                : Arr::only($attributes, self::SIGHTING_REFRESH_FIELDS));

            // PMS-scored jobs stay current; agent-scored (legacy) jobs keep the agent's score.
            if ($opportunity->score_explanation !== null) {
                $this->applyEngineScore($opportunity, false);
            }
            $this->ignoreEquivalentJson($opportunity);
            $changed = array_keys($opportunity->getDirty());

            $opportunity->first_seen_at = $opportunity->first_seen_at ?? $opportunity->created_at ?? $now;
            $opportunity->last_seen_at = $now;
            $opportunity->times_seen = (int) $opportunity->times_seen + 1;
            $opportunity->save();

            if ($changed) {
                $opportunity->log('updated', $userId, null, null, 'Rediscovered' . ($note ? " ({$note})" : '') . ': ' . implode(', ', $changed));
            }

            return [$opportunity, false];
        });
    }

    /**
     * Score attributes with the current published ruleset (no save).
     *
     * @return array|null engine result, or null when no ruleset is published
     */
    public function scoreWithCurrentRuleset(array $attributes): ?array
    {
        $ruleset = UpworkRuleset::current();
        if (!$ruleset) {
            return null;
        }

        $this->engine = $this->engine ?? new ScoringEngine();

        return $this->engine->score($attributes, $ruleset->loadMissing('rules'));
    }

    /**
     * A person approves the job. This (or reject) is the only thing that counts
     * as "reviewed" for the review SLA. When AI proposal drafts are switched on,
     * a draft is queued; it is never submitted anywhere.
     */
    public function approve(UpworkOpportunity $opportunity, int $userId): void
    {
        $opportunity->changeStatus(UpworkOpportunity::STATUS_APPROVED, $userId, [
            'approved_at' => now(),
            'approved_by' => $userId,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
            'reviewed_at' => $opportunity->reviewed_at ?? now(),
        ]);

        app(\App\Services\Upwork\Ai\Proposal\ProposalDraftService::class)->queueAfterApproval($opportunity, $userId);
    }

    public function reject(UpworkOpportunity $opportunity, int $userId, ?string $reason = null): void
    {
        $reason = $reason !== null ? trim($reason) : null;

        $opportunity->changeStatus(UpworkOpportunity::STATUS_REJECTED, $userId, [
            'rejected_at' => now(),
            'rejected_by' => $userId,
            'rejection_reason' => $reason ?: null,
            'reviewed_at' => $opportunity->reviewed_at ?? now(),
        ], $reason ?: null);
    }

    public function stats(): array
    {
        $counts = UpworkOpportunity::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [];
        foreach (array_keys(UpworkOpportunity::STATUSES) as $status) {
            $stats[$status] = (int) ($counts[$status] ?? 0);
        }
        // "New" counts only what a reviewer should look at; the rest is reported as hidden.
        $stats['new'] = UpworkOpportunity::where('status', UpworkOpportunity::STATUS_NEW)->visible()->count();
        $stats['hidden'] = UpworkOpportunity::count() - UpworkOpportunity::visible()->count();
        $applyThreshold = optional(UpworkRuleset::current())->thresholds['apply'] ?? config('upwork.thresholds.apply', 80);
        $stats['high_score'] = UpworkOpportunity::where('score', '>=', $applyThreshold)->count();

        return $stats;
    }

    /**
     * One canonical id per Upwork job. Upwork exposes the same job as a numeric
     * id ("2107561771980939922", search results) and as a ciphertext
     * ("~022107561771980939922", URLs) - "~02" + the numeric id - so both map
     * to the numeric id. Other forms (e.g. legacy "~01..." hex) only lose the "~".
     */
    public static function normalizeJobId(string $jobId): string
    {
        $id = ltrim(trim($jobId), '~');

        return preg_match('/^02(\d{18,})$/', $id, $m) ? $m[1] : $id;
    }

    private function upsert(string $jobId, array $attributes, ?int $userId, bool $pmsScored = false, array $clientExtras = []): array
    {
        return DB::transaction(function () use ($jobId, $attributes, $userId, $pmsScored, $clientExtras) {
            $opportunity = UpworkOpportunity::where('upwork_job_id', $jobId)->lockForUpdate()->first();
            $created = !$opportunity;

            if ($created) {
                $opportunity = new UpworkOpportunity(['upwork_job_id' => $jobId]);
            }
            $opportunity->fill($attributes);
            $opportunity->stage = UpworkOpportunity::STAGE_ENRICHED;

            // Full detail carries Upwork's client id: link and refresh the client first,
            // so scoring can use what the PMS already knows about this client.
            $client = $this->clients->resolve($opportunity, $clientExtras);
            if ($pmsScored) {
                $this->applyEngineScore($opportunity, !array_key_exists('technologies', $attributes), $client);
            }

            if ($created) {
                $opportunity->fetched_at = $opportunity->fetched_at ?? now();
                // Set once, when the job is first stored; the review SLA clock starts here.
                $opportunity->first_seen_at = $opportunity->first_seen_at ?? now();
                $opportunity->save();
                $opportunity->log('ingested', $userId, null, $opportunity->status);
            } else {
                $this->ignoreEquivalentJson($opportunity);
                if ($opportunity->isDirty()) {
                    $changed = array_keys($opportunity->getDirty());
                    $opportunity->save();
                    $opportunity->log('updated', $userId, null, null, 'Agent updated: ' . implode(', ', $changed));
                }
            }

            if ($client) {
                $this->clients->snapshotIfChanged($client, $opportunity->id);
                if (isset($opportunity->score_explanation['client']['score'])) {
                    $client->forceFill(['last_client_score' => $opportunity->score_explanation['client']['score']])->save();
                }
            }

            return [$opportunity, $created];
        });
    }

    /**
     * Score with the current ruleset and write the engine-owned fields. Human-owned
     * state (status, approval, proposal) is never touched. The explanation keeps
     * its original timestamp when nothing about the result changed, so re-seeing
     * an unchanged job is not recorded as an update.
     */
    private function applyEngineScore(UpworkOpportunity $opportunity, bool $fillTechnologies, ?\App\Models\UpworkClient $client = null): void
    {
        $attributes = $opportunity->attributesToArray();
        $client = $client ?? ($opportunity->upwork_client_id ? \App\Models\UpworkClient::find($opportunity->upwork_client_id) : null);
        $match = null;
        if (!$client && !$opportunity->upwork_client_id && ($client = $this->clients->matchByFingerprint($attributes))) {
            // Search row from a client we already know: use its history and cached facts
            // for scoring only. The job is linked to the client when full details arrive.
            [$attributes, $filled] = ClientIntelligenceService::fillFromClient($attributes, $client);
            $match = ['by' => 'fingerprint', 'client_id' => $client->id, 'filled' => $filled];
        }
        if ($client) {
            $attributes['client_history'] = ClientIntelligenceService::scoringFacts($this->clients->history($client, $opportunity->id));
        }

        $attributes['ai_signals'] = \App\Services\Upwork\Ai\Qualification\QualificationService::signalsFor($opportunity);

        $result = $this->scoreWithCurrentRuleset($attributes);
        if (!$result) {
            return;
        }

        $explanation = [
            'engine_version' => $result['engine_version'],
            'ruleset_version' => $result['ruleset_version'],
            'hard_filters' => $result['hard_filters'],
            'components' => $result['components'],
            'technologies' => $result['technologies'],
            'client' => $result['client'] + ['history' => $attributes['client_history'] ?? null] + ($match ? ['match' => $match] : []),
            'strategic' => $result['strategic'],
            'outlook' => $result['outlook'],
            'classifications' => $result['classification_details'],
            'scope' => $result['scope'],
            // AI judgement used by rules (null = none, or below the confidence gate).
            'ai' => $attributes['ai_signals'],
        ];

        $previous = $opportunity->score_explanation;
        if ($previous && self::canonical(self::withoutTimestamps($previous)) === self::canonical(self::withoutTimestamps($explanation))) {
            $explanation = $previous;
        } else {
            $explanation['scored_at'] = now()->toIso8601String();
        }

        foreach ($result['components'] as $component) {
            $opportunity->{$component['column']} = $component['points'];
        }
        if ($result['hard_filters']) {
            foreach (array_keys(\App\Models\UpworkRuleset::COMPONENTS) as $key) {
                $opportunity->{\App\Models\UpworkRuleset::COMPONENTS[$key][0]} = null;
            }
        }

        $opportunity->score = $result['score'];
        $opportunity->strategic_score = $result['strategic']['score'] ?? null;
        $opportunity->win_probability = $result['outlook']['win_probability']['value'] ?? null;
        $opportunity->contract_value_estimate = $result['outlook']['value']['contract_value'] ?? null;
        $opportunity->expected_value = $result['outlook']['value']['expected_value'] ?? null;
        $opportunity->recommendation = $result['recommendation'];
        $opportunity->classifications = $result['classifications'] ?: null;
        $opportunity->scoring_version = $result['scoring_version'];
        $opportunity->ruleset_version = $result['ruleset_version'];
        $opportunity->hard_filters = array_column($result['hard_filters'], 'reason') ?: [];
        $opportunity->score_explanation = $explanation;

        if ($fillTechnologies && !$opportunity->technologies) {
            $opportunity->technologies = array_values(array_column($result['technologies']['direct'], 'name')) ?: null;
        }
    }

    /**
     * MySQL's JSON type stores object keys in its own order and Laravel's array
     * cast compares strictly, so an unchanged JSON value would look dirty. Treat
     * JSON attributes whose decoded content is the same as clean.
     */
    private function ignoreEquivalentJson(UpworkOpportunity $opportunity): void
    {
        foreach (array_keys($opportunity->getDirty()) as $key) {
            if (!$opportunity->hasCast($key, ['array', 'json'])) {
                continue;
            }

            $original = $opportunity->getRawOriginal($key);
            $original = is_string($original) ? json_decode($original, true) : $original;

            if (self::canonical($original) === self::canonical($opportunity->{$key})) {
                $opportunity->syncOriginalAttribute($key);
            }
        }
    }

    /** Recursively key-sorted copy, so JSON objects compare by content. */
    public static function canonical($value)
    {
        if (!is_array($value)) {
            return is_float($value) && floor($value) == $value ? (int) $value : $value;
        }
        if (array_keys($value) !== range(0, count($value) - 1)) {
            ksort($value);
        }

        return array_map([self::class, 'canonical'], $value);
    }

    private static function withoutTimestamps(array $explanation): array
    {
        unset($explanation['scored_at']);
        foreach ($explanation['hard_filters'] ?? [] as $i => $filter) {
            unset($explanation['hard_filters'][$i]['evaluated_at']);
        }

        return $explanation;
    }

    private function prepareAttributes(array $data): array
    {
        $attributes = Arr::only($data, self::AGENT_FIELDS);

        if (isset($attributes['job_type'])) {
            $attributes['job_type'] = strtolower($attributes['job_type']);
        }

        if (array_key_exists('recommendation', $attributes) && $attributes['recommendation'] !== null) {
            $attributes['recommendation'] = strtolower($attributes['recommendation']);
        } elseif (isset($attributes['score'])) {
            $attributes['recommendation'] = UpworkOpportunity::recommendationForScore(
                (float) $attributes['score'],
                optional(UpworkRuleset::current())->thresholds
            );
        }

        // The agent sends ISO-8601 timestamps (usually UTC); store them in the app timezone
        // so they match every other datetime column in the PMS.
        foreach (['posted_at', 'fetched_at'] as $field) {
            if (!empty($attributes[$field])) {
                $attributes[$field] = Carbon::parse($attributes[$field])->setTimezone(config('app.timezone'));
            }
        }

        if (isset($attributes['technologies'])) {
            $attributes['technologies'] = array_values(array_unique(array_filter(array_map('trim', $attributes['technologies']))));
        }

        return $attributes;
    }
}
