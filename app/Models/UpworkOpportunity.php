<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpworkOpportunity extends Model
{
    use HasFactory;

    const STATUS_NEW = 'new';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PROPOSAL_DRAFT = 'proposal_draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_CLIENT_RESPONSE = 'client_response';
    const STATUS_WON = 'won';
    const STATUS_LOST = 'lost';
    const STATUS_ARCHIVED = 'archived';

    /** Statuses a job may be archived from (review work, not proposals already sent). */
    const ARCHIVABLE = [self::STATUS_NEW, self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_PROPOSAL_DRAFT];

    const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_APPROVED => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_PROPOSAL_DRAFT => 'Proposal Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_CLIENT_RESPONSE => 'Client Response',
        self::STATUS_WON => 'Won',
        self::STATUS_LOST => 'Lost',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    /**
     * Allowed status transitions. Every status change goes through changeStatus().
     * An archived job leaves only through UpworkOpportunityService::restore(),
     * which returns it to the status it was archived from.
     */
    const TRANSITIONS = [
        self::STATUS_NEW => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_ARCHIVED],
        self::STATUS_APPROVED => [self::STATUS_PROPOSAL_DRAFT, self::STATUS_REJECTED, self::STATUS_ARCHIVED],
        self::STATUS_REJECTED => [self::STATUS_APPROVED, self::STATUS_ARCHIVED],
        self::STATUS_PROPOSAL_DRAFT => [self::STATUS_SUBMITTED, self::STATUS_REJECTED, self::STATUS_ARCHIVED],
        self::STATUS_SUBMITTED => [self::STATUS_CLIENT_RESPONSE, self::STATUS_WON, self::STATUS_LOST],
        self::STATUS_CLIENT_RESPONSE => [self::STATUS_WON, self::STATUS_LOST],
        self::STATUS_WON => [],
        self::STATUS_LOST => [],
        self::STATUS_ARCHIVED => [],
    ];

    const RECOMMENDATION_APPLY = 'apply';
    const RECOMMENDATION_REVIEW = 'review';
    const RECOMMENDATION_SKIP = 'skip';

    const RECOMMENDATIONS = [
        self::RECOMMENDATION_APPLY => 'Apply',
        self::RECOMMENDATION_REVIEW => 'Review',
        self::RECOMMENDATION_SKIP => 'Skip',
    ];

    protected $fillable = [
        'upwork_job_id', 'upwork_url', 'title', 'description', 'job_type',
        'hourly_min', 'hourly_max', 'fixed_budget', 'experience_level', 'duration',
        'client_country', 'client_timezone', 'client_rating', 'client_reviews_count',
        'client_total_spent', 'client_hire_rate', 'client_jobs_posted', 'client_jobs_hired',
        'payment_verified', 'proposals_count', 'proposals_tier', 'interviews_count', 'connects_required',
        'contractor_type', 'workload', 'job_activity', 'agent_signals',
        'posted_at', 'fetched_at',
        'score', 'technical_fit_score', 'experience_fit_score', 'client_quality_score',
        'commercial_value_score', 'long_term_score', 'ai_automation_score', 'competition_score',
        'score_breakdown', 'score_explanation', 'scoring_version', 'ruleset_version', 'hard_filters',
        'recommendation', 'classifications', 'match_summary', 'relevant_experience', 'risks', 'technologies',
        'proposal_draft', 'proposed_rate', 'estimated_hours', 'screening_answers',
        'raw_data',
    ];

    protected $casts = [
        'hourly_min' => 'float',
        'hourly_max' => 'float',
        'fixed_budget' => 'float',
        'client_rating' => 'float',
        'client_total_spent' => 'float',
        'client_hire_rate' => 'float',
        'payment_verified' => 'boolean',
        'score' => 'float',
        'strategic_score' => 'float',
        'win_probability' => 'float',
        'contract_value_estimate' => 'float',
        'expected_value' => 'float',
        'technical_fit_score' => 'float',
        'experience_fit_score' => 'float',
        'client_quality_score' => 'float',
        'commercial_value_score' => 'float',
        'long_term_score' => 'float',
        'ai_automation_score' => 'float',
        'competition_score' => 'float',
        'proposed_rate' => 'float',
        'estimated_hours' => 'float',
        'score_breakdown' => 'array',
        'score_explanation' => 'array',
        'ruleset_version' => 'integer',
        'job_activity' => 'array',
        'agent_signals' => 'array',
        'classifications' => 'array',
        'hard_filters' => 'array',
        'technologies' => 'array',
        'screening_answers' => 'array',
        'raw_data' => 'array',
        'posted_at' => 'datetime',
        'fetched_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'proposal_generated_at' => 'datetime',
        'proposal_edited_at' => 'datetime',
        'proposal_approved_at' => 'datetime',
        'submitted_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'discovery_ignored' => 'boolean',
        'notified_at' => 'datetime',
        'ai_confidence' => 'float',
        'ai_analyzed_at' => 'datetime',
        'review_due_at' => 'datetime',
        'review_overdue_at' => 'datetime',
        'review_reminder_stage' => 'integer',
        'review_reminded_at' => 'datetime',
        'review_snoozed_until' => 'datetime',
        'reviewed_at' => 'datetime',
        'agency_fit_score' => 'float',
        'individual_fit_score' => 'float',
        'profile_selected_at' => 'datetime',
        'archived_at' => 'datetime',
        'location_details' => 'array',
        'location_rules_version' => 'integer',
        'location_classified_at' => 'datetime',
        'location_override_at' => 'datetime',
    ];

    const PROPOSAL_AI_QUEUED = 'queued';
    const PROPOSAL_AI_READY = 'ready';
    const PROPOSAL_AI_FAILED = 'failed';
    const PROPOSAL_AI_BLOCKED = 'blocked';  // AI off, not configured, or a limit reached
    const PROPOSAL_AI_SKIPPED = 'skipped';  // a person already edited the draft, or the job moved on

    const AI_QUEUED = 'queued';
    const AI_ANALYZED = 'analyzed';
    const AI_SKIPPED = 'skipped';
    const AI_FAILED = 'failed';
    const AI_BLOCKED = 'blocked';   // budget or limits reached; local scoring only

    const STAGE_DISCOVERED = 'discovered'; // stored from a search row only
    const STAGE_ENRICHED = 'enriched';     // full job detail received

    protected $attributes = [
        'status' => self::STATUS_NEW,
    ];

    public function approver() { return $this->belongsTo(Users::class, 'approved_by'); }
    public function rejecter() { return $this->belongsTo(Users::class, 'rejected_by'); }
    public function proposalEditor() { return $this->belongsTo(Users::class, 'proposal_edited_by'); }
    public function proposalApprover() { return $this->belongsTo(Users::class, 'proposal_approved_by'); }
    public function reviewSnoozer() { return $this->belongsTo(Users::class, 'review_snoozed_by'); }
    public function profileSelector() { return $this->belongsTo(Users::class, 'profile_selected_by'); }
    public function archiver() { return $this->belongsTo(Users::class, 'archived_by'); }
    public function locationOverrider() { return $this->belongsTo(Users::class, 'location_override_by'); }

    /** India-based applicants are excluded (by the rules or a person). Such a job never enters review, reminders or proposals. */
    public function isLocationRestricted(): bool
    {
        return $this->location_eligibility === \App\Services\Upwork\Eligibility\LocationEligibility::RESTRICTED;
    }

    /** Eligible, unclear (kept for manual review) or not classified yet. */
    public function scopeLocationNotRestricted($query)
    {
        return $query->where(fn($q) => $q->whereNull('location_eligibility')
            ->orWhere('location_eligibility', '!=', \App\Services\Upwork\Eligibility\LocationEligibility::RESTRICTED));
    }

    /** Everything except archived jobs: the active workflow. */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_ARCHIVED);
    }

    /**
     * Jobs awaiting review that the reminder schedule covers: the SQL form of
     * ReviewSlaService::isEligible (new, enriched, APPLY/REVIEW, not excluded,
     * not hard-rejected). Archived jobs are never in it.
     */
    public function scopeReviewEligible($query)
    {
        return $query->where('status', self::STATUS_NEW)
            ->where('stage', self::STAGE_ENRICHED)
            ->whereIn('recommendation', [self::RECOMMENDATION_APPLY, self::RECOMMENDATION_REVIEW])
            ->where('discovery_ignored', false)
            ->locationNotRestricted()
            // An empty or missing list; a non-empty JSON list starts with ["  (MySQL JSON, MariaDB and SQLite).
            ->where(fn($q) => $q->whereNull('hard_filters')->orWhere('hard_filters', 'not like', '["%'));
    }

    /** Review-eligible jobs past their deadline (snoozed ones included: snooze silences, it does not extend). */
    public function scopeReviewOverdue($query)
    {
        return $query->reviewEligible()->whereNotNull('review_due_at')->where('review_due_at', '<=', now());
    }

    /** New jobs whose reminders are snoozed right now. */
    public function scopeReviewSnoozed($query)
    {
        return $query->where('status', self::STATUS_NEW)->where('review_snoozed_until', '>', now());
    }
    public function logs() { return $this->hasMany(UpworkOpportunityLog::class)->latest('id'); }
    public function sightings() { return $this->hasMany(UpworkOpportunitySighting::class, 'opportunity_id'); }
    public function client() { return $this->belongsTo(UpworkClient::class, 'upwork_client_id'); }
    public function aiAnalysis() { return $this->belongsTo(UpworkAiAnalysis::class, 'ai_analysis_id'); }
    public function aiAnalyses() { return $this->hasMany(UpworkAiAnalysis::class, 'opportunity_id')->latest('id'); }

    /**
     * Default list view: hide untouched noise (still 'new' AND skipped, hard-rejected
     * or excluded by every profile that found it). Anything a human acted on stays,
     * and so do jobs carrying a "surface" archetype such as QUICK_WIN.
     */
    public function scopeVisible($query)
    {
        $surfaced = UpworkClassification::surfacedCodes();

        return $query->where('status', '!=', self::STATUS_ARCHIVED)->where(function ($q) use ($surfaced) {
            $q->where('status', '!=', self::STATUS_NEW)
              ->orWhere(function ($qq) use ($surfaced) {
                  // Location-restricted jobs have their own tab, even when an archetype would surface them.
                  $qq->where('discovery_ignored', false)->locationNotRestricted()
                     ->where(function ($r) use ($surfaced) {
                         $r->whereNull('recommendation')->orWhere('recommendation', '!=', self::RECOMMENDATION_SKIP);
                         // Archetypes flagged "surface" (e.g. QUICK_WIN) stay visible even below the REVIEW threshold.
                         foreach ($surfaced as $code) {
                             $r->orWhere('classifications', 'like', '%"' . $code . '"%');
                         }
                     });
              });
        });
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * The ONLY way status should ever be changed. Updates the row and writes one log entry.
     * Purely local: never calls Upwork.
     */
    public function changeStatus(string $newStatus, ?int $userId, array $attributes = [], ?string $note = null): void
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new \DomainException("Cannot move opportunity from '{$this->status}' to '{$newStatus}'.");
        }

        $old = $this->status;

        $this->forceFill($attributes);
        $this->status = $newStatus;
        $this->save();

        $this->log($newStatus, $userId, $old, $newStatus, $note);
    }

    public function log(string $action, ?int $userId, ?string $oldStatus = null, ?string $newStatus = null, ?string $note = null): void
    {
        $this->logs()->create([
            'user_id' => $userId,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
        ]);
    }

    /**
     * @param array|null $thresholds ['apply' => .., 'review' => ..] from a ruleset; falls back to config/upwork.php
     */
    public static function recommendationForScore(?float $score, ?array $thresholds = null): ?string
    {
        if ($score === null) {
            return null;
        }
        if ($score >= ($thresholds['apply'] ?? config('upwork.thresholds.apply', 80))) {
            return self::RECOMMENDATION_APPLY;
        }
        if ($score >= ($thresholds['review'] ?? config('upwork.thresholds.review', 70))) {
            return self::RECOMMENDATION_REVIEW;
        }
        return self::RECOMMENDATION_SKIP;
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('title', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%")
                       ->orWhere('client_country', 'like', "%{$search}%")
                       // Client: Upwork exposes no client name, so match its country (above) or its Upwork id.
                       ->orWhereIn('upwork_client_id', UpworkClient::select('id')->where('external_id', 'like', "%{$search}%"))
                       ->orWhere('upwork_job_id', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['recommendation'] ?? null, fn($q, $rec) => $q->where('recommendation', $rec))
            // Best-fit track from scoring; "both" jobs also suit each single track, so they are included.
            ->when($filters['target'] ?? null, fn($q, $target) => $target === 'both'
                ? $q->where('target_profile', 'both')
                : $q->whereIn('target_profile', [$target, 'both']))
            // The profile a reviewer chose ("agency" or any "individual..." profile).
            ->when($filters['bid_as'] ?? null, fn($q, $track) => $q->where('selected_profile', 'like', $track . '%'))
            // Location eligibility; "unclassified" = not looked at yet (before the backfill).
            ->when($filters['location'] ?? null, fn($q, $location) => $location === 'unclassified'
                ? $q->whereNull('location_eligibility')
                : $q->where('location_eligibility', $location))
            ->when(isset($filters['min_score']) && $filters['min_score'] !== '', function ($q) use ($filters) {
                $q->where('score', '>=', (float) $filters['min_score']);
            });
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getRecommendationLabelAttribute(): string
    {
        return self::RECOMMENDATIONS[$this->recommendation] ?? '—';
    }

    public static function money(?float $value): string
    {
        if ($value === null) {
            return '—';
        }
        if ($value >= 1000000) {
            return '$' . round($value / 1000000, 1) . 'M';
        }
        if ($value >= 1000) {
            return '$' . round($value / 1000, 1) . 'K';
        }
        return '$' . number_format($value);
    }

    public function getBudgetLabelAttribute(): string
    {
        if ($this->job_type === 'fixed' || ($this->fixed_budget !== null && $this->hourly_min === null)) {
            return $this->fixed_budget !== null ? '$' . number_format($this->fixed_budget) . ' fixed' : 'Fixed';
        }
        if ($this->hourly_min !== null || $this->hourly_max !== null) {
            $min = $this->hourly_min !== null ? '$' . rtrim(rtrim(number_format($this->hourly_min, 2), '0'), '.') : '?';
            $max = $this->hourly_max !== null ? '$' . rtrim(rtrim(number_format($this->hourly_max, 2), '0'), '.') : '?';
            return "{$min}–{$max}/hr";
        }
        return $this->job_type === 'hourly' ? 'Hourly' : '—';
    }

    /**
     * Invites the client sent on this posting, from Upwork's job activity
     * (full job details only). Null = not available; never guessed.
     */
    public function getInvitesSentAttribute(): ?int
    {
        foreach ((array) $this->job_activity as $key => $value) {
            if (in_array($key, ['invites_sent', 'invitesSent'], true)) {
                return is_numeric($value) ? (int) $value : null;
            }
        }

        return null;
    }

    /** Invite penalty applied by the last PMS scoring (null = not PMS-scored with this step yet). */
    public function getInvitePenaltyAttribute(): ?float
    {
        return isset($this->score_explanation['invites']['penalty']) ? (float) $this->score_explanation['invites']['penalty'] : null;
    }

    /** Exact proposal count, or Upwork's tier ("50+") when only that is known. */
    public function getProposalsLabelAttribute(): string
    {
        if ($this->proposals_count !== null) {
            return (string) $this->proposals_count;
        }

        return $this->proposals_tier ?: ($this->raw_data['proposals_tier'] ?? '—');
    }

    /**
     * Score components for display: [label, points, max, explanation entry|null].
     * PMS-scored rows use the weights frozen in their own explanation; agent-scored
     * rows fall back to the labels/maximums in config/upwork.php.
     */
    public function scoreComponents(): array
    {
        $explained = $this->score_explanation['components'] ?? null;
        $rows = [];

        if ($explained) {
            foreach ($explained as $component) {
                $rows[] = [$component['label'], $this->{$component['column']}, $component['max'], $component];
            }
            return $rows;
        }

        foreach (config('upwork.score_components', []) as $column => [$label, $max]) {
            $rows[] = [$label, $this->{$column}, $max, null];
        }

        return $rows;
    }
}
