<?php

namespace App\Services\Upwork\Discovery;

use App\Models\UpworkSearchProfile;
use App\Models\UpworkSetting;

/**
 * Turns a search profile into the read-only Upwork find_jobs searches the
 * agent should run. Keywords become broad (whole-posting) queries, exact
 * phrases become title searches, and the profile's related technologies add
 * extra queries. Each run takes the next few terms in rotation, so the full
 * list is covered over time without flooding Upwork every cycle.
 */
class QueryPlanner
{
    /** Search parameters passed through to find_jobs (business config, never write operations). */
    const PASS_THROUGH = [
        'job_type', 'experience_level', 'verified_payment_only', 'rate_min', 'rate_max', 'budget_min', 'budget_max',
        'workload', 'duration', 'proposals_max', 'client_hires_min', 'category', 'subcategory', 'location',
    ];

    const RESULTS_PER_PAGE = 10;
    const MAX_PAGES = 3;

    /**
     * Every term the profile can search for, in rotation order.
     *
     * @return array<int, array{type: string, text: string}>
     */
    public function terms(UpworkSearchProfile $profile): array
    {
        $terms = [];
        $seen = [];
        $add = function (string $type, string $text) use (&$terms, &$seen) {
            $key = $type . ':' . mb_strtolower($text);
            if (trim($text) !== '' && !isset($seen[$key])) {
                $seen[$key] = true;
                $terms[] = ['type' => $type, 'text' => $text];
            }
        };

        foreach ($profile->keywords ?? [] as $keyword) {
            $add('query', $keyword);
        }
        foreach ($profile->exact_phrases ?? [] as $phrase) {
            $add('title', $phrase);
        }
        // Related technologies are searched only when the profile opts in: their names as
        // broad searches proved noisy in the first real run. They still inform scoring.
        if ($profile->search_related) {
            foreach ($profile->technologies()->where('enabled', true)->where('tier', '!=', 'excluded')->orderByDesc('search_weight')->orderBy('name')->get() as $technology) {
                $add('query', $technology->name);
            }
        }

        return $terms;
    }

    /**
     * The next slice of searches for this run, plus the cursor to store afterwards.
     *
     * @return array{queries: array, next_cursor: int, total_terms: int}
     */
    public function plan(UpworkSearchProfile $profile, int $limit): array
    {
        $terms = $this->terms($profile);
        $count = count($terms);
        if ($count === 0 || $limit <= 0) {
            return ['queries' => [], 'next_cursor' => 0, 'total_terms' => $count];
        }

        $take = min($limit, $count);
        $start = $profile->query_cursor % $count;
        $filters = $profile->upwork_filters ?? [];
        $params = array_intersect_key($filters, array_flip(self::PASS_THROUGH));
        // max_results decides how many results a search fetches (page size x pages).
        $maxResults = max(1, (int) ($profile->max_results ?: self::RESULTS_PER_PAGE));
        $perPage = min(self::RESULTS_PER_PAGE, $maxResults);
        $pages = max(1, min(self::MAX_PAGES, (int) ceil($maxResults / $perPage)));

        $queries = [];
        for ($i = 0; $i < $take; $i++) {
            $term = $terms[($start + $i) % $count];
            // include_full_details: the full description comes back in the same search response,
            // so stage-1 scoring sees the whole posting without one detail call per job.
            $search = $params + ['limit' => $perPage, 'sort' => $filters['sort'] ?? 'recency', 'include_full_details' => true];
            $search[$term['type']] = $term['text'];
            $queries[] = ['type' => $term['type'], 'text' => $term['text'], 'params' => $search, 'pages' => $pages];
        }

        return ['queries' => $queries, 'next_cursor' => ($start + $take) % $count, 'total_terms' => $count];
    }

    public static function maxPerProfile(): int
    {
        return (int) UpworkSetting::get('discovery.max_queries_per_profile');
    }
}
