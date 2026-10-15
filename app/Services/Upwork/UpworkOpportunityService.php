<?php

namespace App\Services\Upwork;

use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Services\Upwork\Clients\ClientIntelligenceService;
use App\Services\Upwork\Eligibility\LocationEligibility;
use App\Services\Upwork\Notifications\NotificationService;
use App\Services\Upwork\Scoring\ScoringEngine;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        'target_profile', 'agency_fit_score', 'individual_fit_score',
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
    public function rescore(UpworkOpportunity $opportunity, ?int $userId = null, string $note = 'Re-scored', bool $notify = true): bool
    {
        $changed = DB::transaction(function () use ($opportunity, $userId, $note) {
            $o = UpworkOpportunity::whereKey($opportunity->id)->lockForUpdate()->first();
            if (!$o || !$o->ruleset_version) {
                return false; // agent-scored legacy rows keep their score
            }
            $location = $this->applyLocation($o, $this->applyEngineScore($o, false));
            $this->ignoreEquivalentJson($o);
            if (!$o->isDirty()) {
                return false;
            }
            $fields = array_keys($o->getDirty());
            $o->save();
            $o->log('rescored', $userId, null, null, $note . ': ' . implode(', ', $fields));
            $this->recordLocation($o, $location, $userId);

            return true;
        });

        if ($changed && $notify) {
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
                $location = $this->applyLocation($opportunity, $this->applyEngineScore($opportunity, true));
                $opportunity->save();
                $opportunity->log('discovered', $userId, null, $opportunity->status, $note);
                $this->recordLocation($opportunity, $location, $userId);

                return [$opportunity, true];
            }

            $opportunity->fill($opportunity->stage === UpworkOpportunity::STAGE_DISCOVERED
                ? $attributes
                : Arr::only($attributes, self::SIGHTING_REFRESH_FIELDS));

            // PMS-scored jobs stay current; agent-scored (legacy) jobs keep the agent's score.
            $scored = $opportunity->score_explanation !== null && $this->applyEngineScore($opportunity, false);
            $location = $this->applyLocation($opportunity, $scored);
            $this->ignoreEquivalentJson($opportunity);
            $changed = array_keys($opportunity->getDirty());

            $opportunity->first_seen_at = $opportunity->first_seen_at ?? $opportunity->created_at ?? $now;
            $opportunity->last_seen_at = $now;
            $opportunity->times_seen = (int) $opportunity->times_seen + 1;
            $opportunity->save();

            if ($changed) {
                $opportunity->log('updated', $userId, null, null, 'Rediscovered' . ($note ? " ({$note})" : '') . ': ' . implode(', ', $changed));
            }
            $this->recordLocation($opportunity, $location, $userId);

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
    public function approve(UpworkOpportunity $opportunity, int $userId, array $targeting = []): void
    {
        if ($opportunity->isLocationRestricted()) {
            throw new \DomainException(LocationEligibility::SKIP_REASON . '. If the location was misread, correct it under "Location eligibility" first.');
        }
        // Validate the choice before anything changes.
        $selection = $this->resolveTargeting($opportunity, $targeting);

        $opportunity->changeStatus(UpworkOpportunity::STATUS_APPROVED, $userId, [
            'approved_at' => now(),
            'approved_by' => $userId,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
            'reviewed_at' => $opportunity->reviewed_at ?? now(),
        ]);
        if ($selection) {
            $this->storeTargeting($opportunity, $selection, $userId);
        }

        app(\App\Services\Upwork\Ai\Proposal\ProposalDraftService::class)->queueAfterApproval($opportunity, $userId);
    }

    /**
     * Change the profile (agency / individual) and proposal angle the draft is
     * written for. Allowed until a person has edited the draft. Nothing here
     * touches Upwork: the profile is a PMS writing persona, never an Upwork
     * account, and nothing is selected, switched or submitted on Upwork.
     */
    public function selectTargeting(UpworkOpportunity $opportunity, array $targeting, int $userId): void
    {
        if (!in_array($opportunity->status, [UpworkOpportunity::STATUS_APPROVED, UpworkOpportunity::STATUS_PROPOSAL_DRAFT], true)) {
            throw new \DomainException('Choose the profile and angle when approving the job, or while its proposal is being drafted.');
        }
        if ($opportunity->proposal_edited_at !== null) {
            throw new \DomainException('The proposal draft has been edited by a person; edit the text directly instead.');
        }
        if (empty($targeting['selected_profile'])) {
            throw new \DomainException('Choose a profile.');
        }

        $this->storeTargeting($opportunity, $this->resolveTargeting($opportunity, $targeting), $userId);
    }

    /**
     * The reviewer's choice, or the engine's suggestion when none was given
     * (approvals from the list or the API). Null when there is neither.
     *
     * @return array|null [selected_profile, proposal_angle, proposal_angle_note, source]
     */
    private function resolveTargeting(UpworkOpportunity $opportunity, array $targeting): ?array
    {
        $suggestion = $opportunity->score_explanation['target'] ?? null;
        $profile = $targeting['selected_profile'] ?? null;
        $angle = $targeting['proposal_angle'] ?? null;
        $note = isset($targeting['proposal_angle_note']) ? trim((string) $targeting['proposal_angle_note']) : null;

        if (!$profile && !$suggestion) {
            return null;
        }
        $source = $profile ? 'reviewer' : 'suggested';
        $profile = $profile ?: $suggestion['suggested_profile'];
        $angle = $angle ?: ($suggestion['suggested_angle'] ?? null);

        if (!\App\Services\Upwork\Targeting\TargetProfiles::find($profile)) {
            throw new \DomainException('Unknown profile. Choose the agency or a configured individual profile.');
        }
        if ($angle !== null && !isset(\App\Services\Upwork\Scoring\TargetProfileScorer::ANGLES[$angle])) {
            throw new \DomainException('Unknown proposal angle.');
        }

        return ['selected_profile' => $profile, 'proposal_angle' => $angle, 'proposal_angle_note' => $note !== '' ? mb_substr((string) $note, 0, 500) : null, 'source' => $source];
    }

    private function storeTargeting(UpworkOpportunity $opportunity, array $selection, int $userId): void
    {
        $opportunity->forceFill(\Illuminate\Support\Arr::only($selection, ['selected_profile', 'proposal_angle', 'proposal_angle_note']));
        if (!$opportunity->isDirty()) {
            return;
        }
        $opportunity->forceFill(['profile_selected_by' => $userId, 'profile_selected_at' => now()])->save();

        $target = $opportunity->score_explanation['target'] ?? null;
        $track = \App\Services\Upwork\Targeting\TargetProfiles::trackOf($selection['selected_profile']);
        $note = 'Bid as ' . \App\Services\Upwork\Targeting\TargetProfiles::label($selection['selected_profile'])
            . ($selection['proposal_angle'] ? '; angle: ' . \App\Services\Upwork\Scoring\TargetProfileScorer::ANGLES[$selection['proposal_angle']][0] : '')
            . ($selection['source'] === 'suggested' ? ' (engine suggestion, not chosen by the reviewer)' : '')
            . ($target && $target['target'] !== 'both' && $track && $track !== $target['target'] ? "; differs from the best fit ({$target['target']})" : '')
            . (($target['restriction']['track'] ?? null) && $track && $track !== $target['restriction']['track'] ? '; WARNING: the posting only accepts ' . ($target['restriction']['track'] === 'agency' ? 'agencies' : 'individuals') : '');
        $opportunity->log('target_selected', $userId, null, null, $note);
    }

    /**
     * Move a job out of the active workflow. Not an approval or a rejection:
     * reviewed_at, approval and rejection fields are left as they are. The job,
     * its score, discovery timestamps and history are kept. Every reminder and
     * notification path requires status "new", so an archived job can no longer
     * trigger any of them, however often the scheduler runs.
     */
    public function archive(UpworkOpportunity $opportunity, int $userId): void
    {
        DB::transaction(function () use ($opportunity, $userId) {
            $o = UpworkOpportunity::whereKey($opportunity->id)->lockForUpdate()->first();
            if (!$o) {
                throw new \DomainException('This job no longer exists.');
            }
            if ($o->status === UpworkOpportunity::STATUS_ARCHIVED) {
                throw new \DomainException('Already archived.');
            }
            if (!in_array($o->status, UpworkOpportunity::ARCHIVABLE, true)) {
                throw new \DomainException("A job that is {$o->status_label} cannot be archived; its proposal is already with the client.");
            }

            $from = $o->status;
            $o->changeStatus(UpworkOpportunity::STATUS_ARCHIVED, $userId, [
                'archived_at' => now(),
                'archived_by' => $userId,
                'archived_from_status' => $from,
            ], 'Archived (was ' . UpworkOpportunity::STATUSES[$from] . '). Not an approval or rejection. ' . self::slaSnapshot($o));
            $opportunity->setRawAttributes($o->getAttributes(), true);
        });
    }

    /**
     * Return an archived job to the status it was archived from, on the same
     * record. A job that goes back to review gets a fresh, logged deadline
     * (ReviewSlaService::restartAfterRestore), so restoring never releases a
     * burst of old reminders.
     */
    public function restore(UpworkOpportunity $opportunity, int $userId): string
    {
        $status = DB::transaction(function () use ($opportunity, $userId) {
            $o = UpworkOpportunity::whereKey($opportunity->id)->lockForUpdate()->first();
            if (!$o || $o->status !== UpworkOpportunity::STATUS_ARCHIVED) {
                throw new \DomainException('This job is not archived.');
            }

            $to = in_array($o->archived_from_status, UpworkOpportunity::ARCHIVABLE, true) ? $o->archived_from_status : UpworkOpportunity::STATUS_NEW;
            $archivedAt = $o->archived_at;
            $o->forceFill(['status' => $to, 'archived_at' => null, 'archived_by' => null, 'archived_from_status' => null])->save();
            $o->log('restored', $userId, UpworkOpportunity::STATUS_ARCHIVED, $to,
                'Restored to ' . UpworkOpportunity::STATUSES[$to] . ($archivedAt ? ' (archived ' . \App\Services\Upwork\Review\ReviewSlaService::format($archivedAt) . ')' : ''));

            if ($to === UpworkOpportunity::STATUS_NEW) {
                app(\App\Services\Upwork\Review\ReviewSlaService::class)->restartAfterRestore($o, $userId);
            }
            $opportunity->setRawAttributes($o->getAttributes(), true);

            return $to;
        });

        return $status;
    }

    /**
     * Apply archive or restore to many jobs, one locked transaction per job,
     * so one failure never undoes or blocks the others.
     *
     * @param iterable<int> $ids
     * @return array{done: int, failed: array<int, string>} failed: id => reason
     */
    public function bulk(string $action, iterable $ids, int $userId): array
    {
        if (!in_array($action, ['archive', 'restore'], true)) {
            throw new \InvalidArgumentException("Unknown bulk action '{$action}'.");
        }
        $summary = ['done' => 0, 'failed' => []];
        foreach ($ids as $id) {
            $o = UpworkOpportunity::find($id);
            try {
                if (!$o) {
                    throw new \DomainException('Not found.');
                }
                $this->{$action}($o, $userId);
                $summary['done']++;
            } catch (\DomainException $e) {
                $summary['failed'][$id] = $e->getMessage();
            }
        }

        return $summary;
    }

    /** The review-SLA state at a moment, for the audit log (the columns may change later). */
    private static function slaSnapshot(UpworkOpportunity $o): string
    {
        if (!$o->review_due_at) {
            return 'No review deadline was running.';
        }
        $f = [\App\Services\Upwork\Review\ReviewSlaService::class, 'format'];

        return 'Review SLA at archive: due ' . $f($o->review_due_at)
            . ($o->review_overdue_at ? ', overdue since ' . $f($o->review_overdue_at) : '')
            . ($o->review_reminder_stage ? ", last reminder #{$o->review_reminder_stage}" : ', no reminder sent')
            . ($o->review_snoozed_until ? ', snoozed until ' . $f($o->review_snoozed_until) : '') . '.';
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
        // Hidden = active noise only; archived jobs are counted separately.
        $stats['hidden'] = UpworkOpportunity::active()->count() - UpworkOpportunity::visible()->count();
        // Review queue: what the reminders cover, what is past due, what is snoozed.
        $stats['awaiting_review'] = UpworkOpportunity::reviewEligible()->count();
        $stats['overdue'] = UpworkOpportunity::reviewOverdue()->count();
        $stats['snoozed'] = UpworkOpportunity::reviewSnoozed()->count();
        // Location eligibility of active (not archived) jobs.
        $stats['all'] = UpworkOpportunity::count();
        $locations = UpworkOpportunity::active()->select('location_eligibility', DB::raw('count(*) as total'))
            ->groupBy('location_eligibility')->pluck('total', 'location_eligibility');
        foreach ([LocationEligibility::ELIGIBLE, LocationEligibility::RESTRICTED, LocationEligibility::UNKNOWN] as $class) {
            $stats['location_' . $class] = (int) ($locations[$class] ?? 0);
        }
        $stats['location_unclassified'] = (int) ($locations[''] ?? 0);
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
            $scored = $pmsScored
                ? $this->applyEngineScore($opportunity, !array_key_exists('technologies', $attributes), $client)
                : array_key_exists('recommendation', $attributes); // agent-scored: the payload's own recommendation
            // After scoring, before saving: a restriction holds the job at SKIP whatever its score.
            $location = $this->applyLocation($opportunity, $scored);

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
            $this->recordLocation($opportunity, $location, $userId);

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
     * Engine input for a stored job: its attributes plus client history and the
     * AI signals that passed the confidence gate.
     *
     * @return array{0: array, 1: ?array} [attributes, fingerprint match]
     */
    private function scoringAttributes(UpworkOpportunity $opportunity, ?\App\Models\UpworkClient $client = null): array
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

        return [$attributes, $match];
    }

    /**
     * Backfill for jobs scored before targeting existed: computes the best-fit
     * track and writes ONLY the target fields (target_profile, the two track
     * fits, score_explanation.target). Score, recommendation, hard filters,
     * review deadline, status and every other field stay exactly as they are.
     *
     * @return array|null the target result, or null for agent-scored rows / no published ruleset
     */
    public function classifyTarget(UpworkOpportunity $opportunity, bool $save, ?int $userId = null): ?array
    {
        if (!$opportunity->ruleset_version || $opportunity->score_explanation === null) {
            return null; // agent-scored legacy rows keep their own data
        }
        [$attributes] = $this->scoringAttributes($opportunity);
        $target = ($this->scoreWithCurrentRuleset($attributes) ?? [])['target'] ?? null;
        if (!$target || !$save) {
            return $target;
        }

        $explanation = $opportunity->score_explanation;
        $explanation['target'] = $target + ['classified_with_ruleset' => optional(UpworkRuleset::current())->version];
        $opportunity->forceFill([
            'target_profile' => $target['target'],
            'agency_fit_score' => $target['tracks']['agency']['score'],
            'individual_fit_score' => $target['tracks']['individual']['score'],
            'score_explanation' => $explanation,
        ]);
        $this->ignoreEquivalentJson($opportunity);
        if ($opportunity->isDirty()) {
            // saveQuietly: no model events, so nothing else (notifications, SLA) reacts to a backfill.
            $opportunity->saveQuietly();
            $opportunity->log('target_classified', $userId, null, null, 'Best fit: ' . $target['target'] . ' (backfill; score unchanged)');
        }

        return $target;
    }

    /**
     * Score with the current ruleset and write the engine-owned fields. Human-owned
     * state (status, approval, proposal) is never touched. The explanation keeps
     * its original timestamp when nothing about the result changed, so re-seeing
     * an unchanged job is not recorded as an update.
     */
    private function applyEngineScore(UpworkOpportunity $opportunity, bool $fillTechnologies, ?\App\Models\UpworkClient $client = null): bool
    {
        [$attributes, $match] = $this->scoringAttributes($opportunity, $client);

        $result = $this->scoreWithCurrentRuleset($attributes);
        if (!$result) {
            return false;
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
            'target' => $result['target'],
            // Base score, invite penalty and final score, kept apart.
            'invites' => $result['invites'],
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
        $opportunity->target_profile = $result['target']['target'] ?? null;
        $opportunity->agency_fit_score = $result['target']['tracks']['agency']['score'] ?? null;
        $opportunity->individual_fit_score = $result['target']['tracks']['individual']['score'] ?? null;
        $opportunity->score_explanation = $explanation;

        if ($fillTechnologies && !$opportunity->technologies) {
            $opportunity->technologies = array_values(array_column($result['technologies']['direct'], 'name')) ?: null;
        }

        return true;
    }

    // ---- Location eligibility --------------------------------------------------------------------

    /**
     * Location eligibility (rules: LocationEligibility). Runs after scoring on
     * every path that stores a job, so no score can outweigh a restriction.
     *
     * While a job is restricted it is held at SKIP with the reason in
     * hard_filters. Every automatic path already refuses such jobs: review
     * deadline, reminders, notifications, AI analysis (and approval / proposal
     * drafting check the restriction explicitly). Status is never changed, for
     * any status. Scoring's own recommendation is kept in
     * location_prior_recommendation and comes back when the restriction is
     * lifted. "Unknown" changes nothing in the workflow; it is only flagged.
     * No save; the caller saves.
     *
     * @param bool $rescored the recommendation was just computed (it is scoring's own, not a held SKIP)
     * @return string|null an activity-log note when the classification changed
     */
    private function applyLocation(UpworkOpportunity $o, bool $rescored): ?string
    {
        $wasEffective = $o->exists ? $o->getOriginal('location_eligibility') : null;
        $base = !$rescored && $wasEffective === LocationEligibility::RESTRICTED
            ? ($o->location_prior_recommendation ?? UpworkOpportunity::recommendationForScore($o->score, optional(UpworkRuleset::current())->thresholds))
            : $o->recommendation;

        $auto = LocationEligibility::evaluate($o->raw_data);
        [$text, $source] = [$auto['text'], $auto['source']];
        $reclassified = $o->location_classified_at === null
            || $o->location_text !== $text
            || $o->location_auto_eligibility !== $auto['eligibility']
            || $o->location_rule !== $auto['rule']
            || (int) $o->location_rules_version !== LocationEligibility::VERSION;
        if ($reclassified) {
            // classified_at only moves when the answer (or the rules) change, so a re-seen job is not "updated".
            $o->forceFill([
                'location_text' => $text,
                'location_source' => $source,
                'location_auto_eligibility' => $auto['eligibility'],
                'location_rule' => $auto['rule'],
                'location_details' => $auto['details'],
                'location_rules_version' => LocationEligibility::VERSION,
                'location_classified_at' => now(),
            ]);
        }

        $effective = $o->location_override ?: $auto['eligibility'];
        $o->location_eligibility = $effective;
        $o->location_reason = !$o->location_override ? $auto['reason']
            : ($effective === LocationEligibility::RESTRICTED ? LocationEligibility::SKIP_REASON : LocationEligibility::OVERRIDE_ELIGIBLE_REASON);

        $filters = array_values(array_diff((array) $o->hard_filters, [LocationEligibility::SKIP_REASON]));
        if ($effective === LocationEligibility::RESTRICTED) {
            $filters[] = LocationEligibility::SKIP_REASON;
            $o->location_prior_recommendation = $base;
            $o->recommendation = UpworkOpportunity::RECOMMENDATION_SKIP;
        } else {
            $o->location_prior_recommendation = null;
            $o->recommendation = $base;
        }
        $o->hard_filters = $filters ?: ($o->hard_filters === null ? null : []);

        if (!$reclassified && $wasEffective === $effective) {
            return null;
        }
        if ($wasEffective === null && $auto['rule'] === 'blank' && !$o->location_override) {
            return null; // first look, no location given: stored, but not worth an activity entry
        }

        return 'Location: ' . LocationEligibility::describe($o) . ($text !== null ? " [from {$source}]" : '') . '. ' . $o->location_reason
            . ($wasEffective && $wasEffective !== $effective ? ' (was ' . LocationEligibility::LABELS[$wasEffective] . ')' : '');
    }

    /** Activity-log entry for a changed classification; unclear ones also go to the app log, to improve the rules. */
    private function recordLocation(UpworkOpportunity $o, ?string $note, ?int $userId): void
    {
        if ($note === null) {
            return;
        }
        $o->log('location_classified', $userId, null, null, $note);
        if ($o->location_auto_eligibility === LocationEligibility::UNKNOWN) {
            Log::info('[upwork] location restriction unclear', [
                'opportunity_id' => $o->id, 'upwork_job_id' => $o->upwork_job_id, 'location_text' => $o->location_text,
                'source' => $o->location_source, 'rule' => $o->location_rule, 'details' => $o->location_details,
            ]);
        }
    }

    /**
     * Backfill: classify one stored job with the current location rules. Dry
     * run unless $save. Writes only the location fields, plus recommendation /
     * hard_filters when the job is held at or released from SKIP. Status,
     * score, review deadline, snooze, approval and history never change;
     * nothing is sent and no new record is created. Idempotent: a second run
     * finds nothing to change.
     *
     * @return array{eligibility: string, auto: string, rule: string, reason: string, text: ?string, action: string, changes: string[]}
     */
    public function classifyLocation(UpworkOpportunity $opportunity, bool $save, ?int $userId = null): array
    {
        $run = function () use ($opportunity, $save, $userId) {
            $o = $save ? UpworkOpportunity::whereKey($opportunity->id)->lockForUpdate()->first() : clone $opportunity;
            $before = ['eligibility' => $o->location_eligibility, 'recommendation' => $o->recommendation];
            $note = $this->applyLocation($o, false);
            $this->ignoreEquivalentJson($o);
            $changes = array_keys($o->getDirty());

            if ($o->location_eligibility === LocationEligibility::RESTRICTED && $before['recommendation'] !== UpworkOpportunity::RECOMMENDATION_SKIP) {
                $action = 'skip: ' . ($before['recommendation'] ?? 'none') . ' -> skip (status ' . $o->status . ' unchanged)';
            } elseif ($before['eligibility'] === LocationEligibility::RESTRICTED && $o->location_eligibility !== LocationEligibility::RESTRICTED) {
                $action = 'release: skip -> ' . ($o->recommendation ?? 'none');
            } elseif (!$changes) {
                $action = 'none';
            } elseif ($o->location_eligibility === LocationEligibility::UNKNOWN) {
                $action = 'flag for manual review';
            } else {
                $action = 'record classification';
            }

            if ($save && $changes) {
                // saveQuietly: no model events, so nothing (notifications, SLA) reacts to a backfill.
                $o->saveQuietly();
                $this->recordLocation($o, ($note ?? 'Location re-checked') . ' (backfill)', $userId);
            }

            return [
                'eligibility' => $o->location_eligibility, 'auto' => $o->location_auto_eligibility, 'rule' => $o->location_rule,
                'reason' => $o->location_reason, 'text' => $o->location_text, 'action' => $action, 'changes' => $changes,
            ];
        };

        return $save ? DB::transaction($run) : $run();
    }

    /** Facts a refresh copies from fresh job details: Upwork's location list and the posting's activity counts. */
    const REFRESH_RAW_KEYS = [LocationEligibility::LIST_SOURCE, 'preferred_qualifications'];

    /**
     * Re-read facts for a stored job from fresh job details
     * (UpworkClientInterface::job): Upwork's location list and the posting's
     * activity (invites sent, interviews, hires, offers). Then re-score (the
     * invite penalty and location eligibility follow) and classify. Status,
     * deadlines, approval and proposal fields are untouched, and nothing is
     * sent: no notification, no review deadline.
     *
     * @return array{invites_before: ?int, invites: ?int, score_before: ?float, score: ?float, location: array}
     */
    public function refreshFromDetail(UpworkOpportunity $opportunity, array $detail, ?int $userId = null): array
    {
        $before = ['invites' => $opportunity->invites_sent, 'score' => $opportunity->score];
        $raw = (array) $opportunity->raw_data;
        foreach (self::REFRESH_RAW_KEYS as $key) {
            if (array_key_exists($key, (array) ($detail['raw_data'] ?? []))) {
                $raw[$key] = $detail['raw_data'][$key];
            }
        }
        $opportunity->forceFill(['raw_data' => $raw]);
        // Only counts Upwork returned replace the stored ones; nothing is guessed or cleared.
        if (!empty($detail['job_activity'])) {
            $opportunity->forceFill(['job_activity' => $detail['job_activity']]);
        }
        if (array_key_exists('interviews_count', $detail)) {
            $opportunity->forceFill(['interviews_count' => $detail['interviews_count']]);
        }
        $this->ignoreEquivalentJson($opportunity);
        if ($opportunity->isDirty()) {
            $changed = array_keys($opportunity->getDirty());
            $opportunity->saveQuietly();
            $opportunity->log('updated', $userId, null, null, 'Re-read from Upwork: ' . implode(', ', $changed)
                . ($before['invites'] !== $opportunity->invites_sent ? '; invites sent ' . ($before['invites'] ?? 'n/a') . ' -> ' . ($opportunity->invites_sent ?? 'n/a') : ''));
        }

        // PMS-scored jobs: new invite count -> new score (once, from scratch). Agent-scored rows keep their score.
        $this->rescore($opportunity, $userId, 'Re-scored after re-reading from Upwork', false);
        $location = $this->classifyLocation($opportunity->fresh(), true, $userId);
        $o = $opportunity->fresh();

        return ['invites_before' => $before['invites'], 'invites' => $o->invites_sent, 'score_before' => $before['score'], 'score' => $o->score, 'location' => $location];
    }

    /**
     * A person corrects the classification: 'eligible', 'restricted', or null
     * to go back to the rules. The original location text, rule and automatic
     * classification are kept; the change is in the activity log. A job
     * released into review starts its review clock (nothing is sent from here).
     */
    public function overrideLocation(UpworkOpportunity $opportunity, ?string $classification, int $userId, ?string $note = null): void
    {
        if ($classification !== null && !in_array($classification, [LocationEligibility::ELIGIBLE, LocationEligibility::RESTRICTED], true)) {
            throw new \DomainException('Choose eligible or restricted.');
        }
        $note = trim((string) $note);
        if ($classification !== null && $note === '') {
            throw new \DomainException('Say why the location classification is wrong; it is kept in the activity log.');
        }

        DB::transaction(function () use ($opportunity, $classification, $userId, $note) {
            $o = UpworkOpportunity::whereKey($opportunity->id)->lockForUpdate()->first();
            if (!$o) {
                throw new \DomainException('This job no longer exists.');
            }
            if ($o->location_override === $classification) {
                throw new \DomainException($classification ? 'The job is already marked ' . LocationEligibility::LABELS[$classification] . '.' : 'There is no manual override to remove.');
            }
            if ($o->location_classified_at === null) {
                // Record what the rules say first, so the override never hides the original classification.
                $this->recordLocation($o, $this->applyLocation($o, false), null);
            }
            $before = LocationEligibility::describe($o);

            $o->forceFill([
                'location_override' => $classification,
                'location_override_by' => $classification ? $userId : null,
                'location_override_at' => $classification ? now() : null,
                'location_override_note' => $classification ? mb_substr($note, 0, 500) : null,
            ]);
            $this->applyLocation($o, false);
            $o->save();
            $o->log('location_override', $userId, null, null, ($classification
                    ? 'Location marked ' . LocationEligibility::LABELS[$classification] . " by a reviewer: {$note}"
                    : 'Location override removed; the rules apply again' . ($note !== '' ? ": {$note}" : ''))
                . ". Before: {$before}. Now: " . LocationEligibility::describe($o) . '. Recommendation: ' . ($o->recommendation ?? 'none') . '.');
            $opportunity->setRawAttributes($o->getAttributes(), true);
        });

        app(\App\Services\Upwork\Review\ReviewSlaService::class)->startIfEligible($opportunity);
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
