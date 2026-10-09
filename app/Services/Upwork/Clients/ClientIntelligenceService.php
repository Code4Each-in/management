<?php

namespace App\Services\Upwork\Clients;

use App\Models\UpworkClient;
use App\Models\UpworkClientSnapshot;
use App\Models\UpworkOpportunity;

/**
 * Client-level intelligence built only from stored facts:
 *  - identity: Upwork's own client id (never guessed from similar-looking stats)
 *  - latest Upwork-reported figures, with a snapshot whenever they change
 *  - history across the client's opportunities: budgets, technologies,
 *    Code4Each decisions and outcomes
 */
class ClientIntelligenceService
{
    /** Opportunity column => client column for the figures Upwork reports. */
    const FACT_MAP = [
        'client_country' => 'country',
        'client_timezone' => 'timezone',
        'client_rating' => 'rating',
        'client_reviews_count' => 'reviews_count',
        'client_total_spent' => 'total_spent',
        'client_hire_rate' => 'hire_rate',
        'client_jobs_posted' => 'jobs_posted',
        'client_jobs_hired' => 'jobs_hired',
        'payment_verified' => 'payment_verified',
    ];

    const WORK_HISTORY_FIELDS = ['title', 'type', 'status', 'started', 'ended', 'feedback_score', 'feedback_to_client_score'];
    const WORK_HISTORY_LIMIT = 10;

    /**
     * Find or create the client by Upwork's id and refresh its latest figures
     * from a fully-detailed (enriched) opportunity. Does not save the opportunity.
     *
     * @param array $extras upwork_client_id, client_work_history, client_open_jobs from the payload
     */
    public function resolve(UpworkOpportunity $opportunity, array $extras): ?UpworkClient
    {
        $externalId = isset($extras['upwork_client_id']) ? trim((string) $extras['upwork_client_id']) : '';
        if ($externalId === '') {
            return $opportunity->upwork_client_id ? UpworkClient::find($opportunity->upwork_client_id) : null;
        }

        $client = UpworkClient::firstOrNew(['external_id' => $externalId]);
        $now = now();
        if (!$client->exists) {
            $client->first_seen_at = $now;
        }
        $client->last_seen_at = $now;

        foreach (self::FACT_MAP as $from => $to) {
            if ($opportunity->{$from} !== null && $opportunity->{$from} !== '') {
                $client->{$to} = $opportunity->{$from};
            }
        }
        if (isset($extras['client_open_jobs'])) {
            $client->open_jobs = (int) $extras['client_open_jobs'];
        }
        if (!empty($extras['client_work_history']) && is_array($extras['client_work_history'])) {
            $client->work_history = array_slice(array_values(array_map(
                fn($c) => array_intersect_key((array) $c, array_flip(self::WORK_HISTORY_FIELDS)),
                $extras['client_work_history']
            )), 0, self::WORK_HISTORY_LIMIT);
        }

        $client->fingerprint = self::fingerprint([
            'client_country' => $client->country, 'client_jobs_posted' => $client->jobs_posted,
            'client_jobs_hired' => $client->jobs_hired, 'client_total_spent' => $client->total_spent,
        ]) ?? $client->fingerprint;

        $client->save();
        $opportunity->upwork_client_id = $client->id;

        return $client;
    }

    /** Write a snapshot when the tracked figures differ from the latest one. */
    public function snapshotIfChanged(UpworkClient $client, ?int $opportunityId = null): ?UpworkClientSnapshot
    {
        $current = [];
        foreach (UpworkClient::TRACKED as $field) {
            $current[$field] = $client->{$field} !== null ? round((float) $client->{$field}, 2) : null;
        }
        if (!array_filter($current, fn($v) => $v !== null)) {
            return null;
        }

        $latest = $client->snapshots()->reorder()->orderByDesc('recorded_at')->orderByDesc('id')->first();
        if ($latest) {
            $previous = [];
            foreach (UpworkClient::TRACKED as $field) {
                $previous[$field] = $latest->{$field} !== null ? round((float) $latest->{$field}, 2) : null;
            }
            if ($previous === $current) {
                return null;
            }
        }

        return UpworkClientSnapshot::create(['upwork_client_id' => $client->id, 'opportunity_id' => $opportunityId, 'recorded_at' => now()] + $current);
    }

    /**
     * What the PMS knows about this client from its other opportunities.
     * Every number is derived from stored rows; nothing is estimated.
     */
    public function history(UpworkClient $client, ?int $excludeOpportunityId = null): array
    {
        $others = $client->opportunities()
            ->when($excludeOpportunityId, fn($q) => $q->where('id', '!=', $excludeOpportunityId))
            ->orderByDesc('posted_at')->orderByDesc('id')
            ->get();

        $hourlyMin = $others->pluck('hourly_min')->filter(fn($v) => $v !== null)->values()->all();
        $hourlyMax = $others->pluck('hourly_max')->filter(fn($v) => $v !== null)->values()->all();
        $fixed = $others->where('job_type', 'fixed')->pluck('fixed_budget')->filter(fn($v) => $v !== null)->values()->all();

        $technologies = [];
        foreach ($others as $o) {
            foreach ((array) $o->technologies as $t) {
                $technologies[$t] = ($technologies[$t] ?? 0) + 1;
            }
        }
        arsort($technologies);

        $snapshots = $client->snapshots()->get();

        return [
            'client_id' => $client->id,
            'external_id' => $client->external_id,
            'previous_jobs' => $others->count(),
            'jobs' => $others->take(10)->map(fn(UpworkOpportunity $o) => [
                'id' => $o->id,
                'title' => $o->title,
                'posted_at' => optional($o->posted_at)->toIso8601String(),
                'budget' => $o->budget_label,
                'status' => $o->status,
                'recommendation' => $o->recommendation,
                'score' => $o->score,
            ])->values()->all(),
            'typical_hourly' => $hourlyMax ? [
                'min' => $hourlyMin ? self::median($hourlyMin) : null,
                'max' => self::median($hourlyMax),
                'samples' => count($hourlyMax),
            ] : null,
            'typical_fixed' => $fixed ? ['median' => self::median($fixed), 'samples' => count($fixed)] : null,
            'technologies' => array_slice($technologies, 0, 8, true),
            'decisions' => [
                'approved' => $others->whereNotNull('approved_at')->count(),
                'rejected' => $others->where('status', UpworkOpportunity::STATUS_REJECTED)->count(),
                'submitted' => $others->whereNotNull('submitted_at')->count(),
                'client_responses' => $others->where('status', UpworkOpportunity::STATUS_CLIENT_RESPONSE)->count(),
                'won' => $others->where('status', UpworkOpportunity::STATUS_WON)->count(),
                'lost' => $others->where('status', UpworkOpportunity::STATUS_LOST)->count(),
            ],
            'rejection_reasons' => $others->where('status', UpworkOpportunity::STATUS_REJECTED)->pluck('rejection_reason')->filter()->take(3)->values()->all(),
            'hire_rate_trend' => self::trend($snapshots, 'hire_rate'),
            'rating_trend' => self::trend($snapshots, 'rating'),
            'first_seen_at' => optional($client->first_seen_at)->toIso8601String(),
            'last_seen_at' => optional($client->last_seen_at)->toIso8601String(),
        ];
    }

    /** A fingerprint only matches while the client's figures are this recent. */
    const FINGERPRINT_MAX_AGE_DAYS = 30;

    /** Facts a matched client may fill in when a search row lacks them (scoring input only). */
    const FILLABLE_FACTS = ['client_rating', 'client_reviews_count', 'client_hire_rate', 'payment_verified', 'client_timezone'];

    /**
     * Identity of a client from public figures that search rows carry: country,
     * jobs posted, hires and total spent (to the dollar). Null unless all are known
     * and the client has a real history (a new client's figures are not distinctive).
     */
    public static function fingerprint(array $a): ?string
    {
        $country = trim(mb_strtolower((string) ($a['client_country'] ?? '')));
        $posted = $a['client_jobs_posted'] ?? null;
        $hired = $a['client_jobs_hired'] ?? null;
        $spent = $a['client_total_spent'] ?? null;
        if ($country === '' || !is_numeric($posted) || !is_numeric($hired) || !is_numeric($spent) || (float) $spent < 1000 || (int) $posted < 3) {
            return null;
        }

        return sha1(implode('|', [$country, (int) $posted, (int) $hired, (int) round((float) $spent)]));
    }

    /**
     * The known client behind a search row, when exactly one recently seen client
     * has the same fingerprint. Never written to the opportunity: only full job
     * details link a job to a client.
     */
    public function matchByFingerprint(array $attributes): ?UpworkClient
    {
        $fingerprint = self::fingerprint($attributes);
        if (!$fingerprint) {
            return null;
        }

        $matches = UpworkClient::where('fingerprint', $fingerprint)
            ->where('last_seen_at', '>=', now()->subDays(self::FINGERPRINT_MAX_AGE_DAYS))
            ->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /** @return array{0: array, 1: string[]} attributes with missing client facts filled from the client, and which were filled */
    public static function fillFromClient(array $attributes, UpworkClient $client): array
    {
        $filled = [];
        foreach (self::FILLABLE_FACTS as $fact) {
            $column = self::FACT_MAP[$fact];
            if ((!isset($attributes[$fact]) || $attributes[$fact] === '') && $client->{$column} !== null) {
                $attributes[$fact] = $client->{$column};
                $filled[] = $fact;
            }
        }

        return [$attributes, $filled];
    }

    /** History fields the scoring engine reads (as client.* rule fields). */
    public static function scoringFacts(array $history): array
    {
        return [
            'seen_before' => $history['previous_jobs'],
            'previous_wins' => $history['decisions']['won'],
            'previous_approvals' => $history['decisions']['approved'],
            'previous_rejections' => $history['decisions']['rejected'],
        ];
    }

    private static function trend($snapshots, string $field): ?array
    {
        $values = $snapshots->pluck($field)->filter(fn($v) => $v !== null)->values();
        if ($values->isEmpty()) {
            return null;
        }

        return ['first' => $values->first(), 'latest' => $values->last(), 'snapshots' => $values->count()];
    }

    public static function median(array $values): float
    {
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 ? (float) $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }
}
