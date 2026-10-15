<?php

namespace App\Services\Upwork\Scoring;

/**
 * Turns opportunity attributes (from an agent payload or a stored row) into the
 * normalized context that rules and the engine read: job, client, competition
 * and signals. Only facts present in the data are set; nothing is inferred.
 */
class ScoringContext
{
    const PROPOSAL_TIERS = [
        'fewer than 5' => [0, 4],
        'less than 5' => [0, 4],
        '5 to 10' => [5, 10],
        '10 to 15' => [10, 15],
        '15 to 20' => [15, 20],
        '20 to 50' => [20, 50],
        '50+' => [50, null],
    ];

    public static function fromAttributes(array $a): array
    {
        $raw = (array) ($a['raw_data'] ?? []);
        $activity = self::normalizeKeys((array) ($a['job_activity'] ?? ($raw['job_activity'] ?? [])));
        $signals = (array) ($a['agent_signals'] ?? []);

        $skills = is_array($raw['skills'] ?? null) ? implode(', ', $raw['skills']) : '';
        $text = trim(implode("\n", array_filter([(string) ($a['title'] ?? ''), (string) ($a['description'] ?? ''), $skills])));

        $tier = self::proposalTierRange($a['proposals_tier'] ?? ($raw['proposals_tier'] ?? null));
        $proposalsCount = self::num($a['proposals_count'] ?? null);

        return [
            'job' => [
                'title' => $a['title'] ?? null,
                'description' => $a['description'] ?? null,
                'text' => $text,
                'job_type' => isset($a['job_type']) ? strtolower($a['job_type']) : null,
                'hourly_min' => self::num($a['hourly_min'] ?? null),
                'hourly_max' => self::num($a['hourly_max'] ?? null),
                'fixed_budget' => self::num($a['fixed_budget'] ?? null),
                'experience_level' => $a['experience_level'] ?? null,
                'duration' => $a['duration'] ?? null,
                'duration_weeks' => self::durationWeeks($a['duration'] ?? null),
                'age_hours' => self::ageHours($a['posted_at'] ?? null),
                'workload' => $a['workload'] ?? ($raw['engagement'] ?? null),
                'contractor_type' => $a['contractor_type'] ?? ($raw['preferred_qualifications']['contractor_type'] ?? null),
                'category' => $raw['category'] ?? null,
                'connects_required' => self::num($a['connects_required'] ?? null),
                'freelancers_to_hire' => self::num($a['freelancers_to_hire'] ?? ($raw['freelancers_to_hire'] ?? null)),
            ],
            'client' => [
                'payment_verified' => isset($a['payment_verified']) ? (bool) $a['payment_verified'] : null,
                'rating' => self::num($a['client_rating'] ?? null),
                'reviews_count' => self::num($a['client_reviews_count'] ?? null),
                'total_spent' => self::num($a['client_total_spent'] ?? null),
                'hire_rate' => self::num($a['client_hire_rate'] ?? null),
                'jobs_posted' => self::num($a['client_jobs_posted'] ?? null),
                'jobs_hired' => self::num($a['client_jobs_hired'] ?? null),
                'country' => $a['client_country'] ?? null,
                // From stored history of this Upwork client (null when the client is not identified).
                'seen_before' => self::num($a['client_history']['seen_before'] ?? null),
                'previous_wins' => self::num($a['client_history']['previous_wins'] ?? null),
                'previous_approvals' => self::num($a['client_history']['previous_approvals'] ?? null),
                'previous_rejections' => self::num($a['client_history']['previous_rejections'] ?? null),
            ],
            'competition' => [
                'proposals_count' => $proposalsCount,
                'proposals_min' => $proposalsCount ?? $tier[0],
                'proposals_max' => $proposalsCount ?? $tier[1],
                'interviews' => self::num($a['interviews_count'] ?? ($activity['total_invited_to_interview'] ?? null)),
                'invites_sent' => self::num($activity['invites_sent'] ?? null),
                'total_hired' => self::num($activity['total_hired'] ?? null),
                'total_offered' => self::num($activity['total_offered'] ?? null),
            ],
            'signals' => [
                'project_clarity' => self::num($signals['project_clarity'] ?? null),
                'scope_size' => isset($signals['scope_size']) ? strtolower($signals['scope_size']) : self::inferScope($a),
                'scope_source' => isset($signals['scope_size']) ? 'agent' : (self::inferScope($a) ? 'inferred' : null),
                'is_individual_only' => isset($signals['is_individual_only']) ? (bool) $signals['is_individual_only'] : null,
                // Upwork's own "previous client" flag (Code4Each worked with this client before).
                'previous_client' => isset($signals['previous_client']) ? (bool) $signals['previous_client'] : null,
                'technologies' => array_values(array_filter((array) ($signals['technologies'] ?? []), 'is_string')),
            ],
            // AI qualification, only when an analysis passed the confidence gate
            // (see QualificationService::signalsFor). Otherwise every ai.* field is null.
            'ai' => self::aiFacts($a['ai_signals'] ?? null),
        ];
    }

    /** @param array|null $ai signals of the analysis in use */
    public static function aiFacts(?array $ai): array
    {
        $ai = $ai ?: [];

        return [
            'available' => (bool) $ai,
            'fit_score' => self::num($ai['fit_score'] ?? null),
            'confidence' => self::num($ai['confidence'] ?? null),
            'recommendation' => $ai['recommendation'] ?? null,
            'long_term_potential' => $ai['long_term_potential'] ?? null,
            'ai_component' => $ai['ai_component'] ?? null,
            'budget_realistic' => $ai['budget_realistic'] ?? null,
            'urgency' => $ai['urgency'] ?? null,
            'agency_friendly' => $ai['agency_friendly'] ?? null,
            'complexity' => $ai['complexity'] ?? null,
            'high_risk_count' => isset($ai['high_risk_count']) ? (int) $ai['high_risk_count'] : null,
            'estimated_hours_min' => self::num($ai['estimated_hours_min'] ?? null),
            'estimated_hours_max' => self::num($ai['estimated_hours_max'] ?? null),
            'hours_basis' => $ai['hours_basis'] ?? null,
        ];
    }

    /**
     * Scope size when the agent gave none, from duration and budget only:
     * small = under ~1 month and not full-time, or a small fixed budget;
     * large = 4+ months, or full-time for 2+ months; otherwise medium.
     */
    public static function inferScope(array $a): ?string
    {
        $weeks = self::durationWeeks($a['duration'] ?? null);
        $workload = strtolower((string) ($a['workload'] ?? ''));
        $fixed = strtolower((string) ($a['job_type'] ?? '')) === 'fixed' ? self::num($a['fixed_budget'] ?? null) : null;

        if (($weeks !== null && $weeks <= 4 && $workload !== 'full_time') || ($fixed !== null && $fixed <= 3000 && ($weeks === null || $weeks <= 8))) {
            return 'small';
        }
        if ($weeks !== null && ($weeks >= 18 || ($workload === 'full_time' && $weeks >= 8))) {
            return 'large';
        }

        return $weeks !== null ? 'medium' : null;
    }

    /** Hours since the job was posted (null when unknown). */
    public static function ageHours($postedAt): ?float
    {
        if (empty($postedAt)) {
            return null;
        }
        try {
            return round(max(0, now()->diffInMinutes(\Illuminate\Support\Carbon::parse($postedAt), false) * -1) / 60, 1);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Upwork duration labels to an approximate number of weeks (midpoint of the band). */
    public static function durationWeeks(?string $duration): ?float
    {
        if ($duration === null || trim($duration) === '') {
            return null;
        }
        $d = strtolower($duration);

        if (str_contains($d, 'less than 1 month') || str_contains($d, 'less than a month')) {
            return 2.0;
        }
        if (str_contains($d, '1 to 3 months')) {
            return 8.0;
        }
        if (str_contains($d, '3 to 6 months')) {
            return 18.0;
        }
        if (str_contains($d, 'more than 6 months') || str_contains($d, '6+ months')) {
            return 30.0;
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*week/', $d, $m)) {
            return (float) $m[1];
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*month/', $d, $m)) {
            return round((float) $m[1] * 4.33, 1);
        }

        return null;
    }

    /** @return array{0: ?int, 1: ?int} [min, max] proposals for an Upwork tier label */
    public static function proposalTierRange(?string $tier): array
    {
        $key = strtolower(trim((string) $tier));

        return self::PROPOSAL_TIERS[$key] ?? [null, null];
    }

    /** Accept both Upwork's camelCase (totalHired) and snake_case keys. */
    private static function normalizeKeys(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            $out[strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', (string) $key))] = $value;
        }

        return $out;
    }

    private static function num($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
