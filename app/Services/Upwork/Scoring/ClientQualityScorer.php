<?php

namespace App\Services\Upwork\Scoring;

/**
 * Client Quality Score (0-100) from the client facts Upwork reports for a job.
 * Missing facts get neutral credit and are flagged as unknown, never guessed.
 *
 *   Payment reliability 20, Hiring behaviour 20, Historical satisfaction 20,
 *   Budget quality 20, Repeat hiring 10, Project consistency 10.
 */
class ClientQualityScorer
{
    const PARTS = [
        'payment' => ['Payment reliability', 20],
        'hiring' => ['Hiring behaviour', 20],
        'satisfaction' => ['Historical satisfaction', 20],
        'budget' => ['Budget quality', 20],
        'repeat' => ['Repeat hiring', 10],
        'consistency' => ['Project consistency', 10],
    ];

    /**
     * @param array $client payment_verified, hire_rate, rating, reviews_count, total_spent, jobs_posted, jobs_hired
     * @return array{score: float, parts: array, unknown: string[]}
     */
    public function score(array $client): array
    {
        $parts = [];
        $unknown = [];
        $known = fn($key) => isset($client[$key]) && $client[$key] !== '';

        // Payment reliability
        if (!$known('payment_verified')) {
            $parts['payment'] = 10;
            $unknown[] = 'payment verification';
        } else {
            $parts['payment'] = $client['payment_verified'] ? 20 : 0;
        }

        // Hiring behaviour: share of postings that led to a hire.
        if ($known('hire_rate')) {
            $parts['hiring'] = 20 * $this->clamp($client['hire_rate'] / 100);
        } elseif ($known('jobs_posted') && $known('jobs_hired') && $client['jobs_posted'] > 0) {
            $parts['hiring'] = 20 * $this->clamp($client['jobs_hired'] / $client['jobs_posted']);
        } else {
            $parts['hiring'] = 10;
            $unknown[] = 'hire rate';
        }

        // Historical satisfaction: freelancer rating, trusted in proportion to review count.
        if ($known('rating') && $known('reviews_count') && $client['reviews_count'] > 0) {
            $ratingScore = 20 * $this->clamp(($client['rating'] - 3) / 2);
            $confidence = $this->clamp($client['reviews_count'] / 10);
            $parts['satisfaction'] = 10 + ($ratingScore - 10) * $confidence;
        } else {
            $parts['satisfaction'] = 10;
            $unknown[] = 'rating';
        }

        // Budget quality: lifetime spend ($100 -> 0, $100k+ -> full), blended 70/30 with the
        // typical spend per hire ($100 -> 0, $5k+ -> full) when the hire count is known.
        if ($known('total_spent')) {
            $spent = (float) $client['total_spent'];
            $lifetime = $spent <= 100 ? 0.0 : $this->clamp((log10($spent) - 2) / 3);
            if ($known('jobs_hired') && $client['jobs_hired'] > 0) {
                $perHire = $spent / $client['jobs_hired'];
                $typical = $perHire <= 100 ? 0.0 : $this->clamp((log10($perHire) - 2) / (log10(5000) - 2));
                $parts['budget'] = 20 * (0.7 * $lifetime + 0.3 * $typical);
            } else {
                $parts['budget'] = 20 * $lifetime;
            }
        } else {
            $parts['budget'] = 10;
            $unknown[] = 'total spend';
        }

        // Repeat hiring: how many hires the client has made overall.
        if ($known('jobs_hired')) {
            $parts['repeat'] = 10 * $this->clamp($client['jobs_hired'] / 10);
        } else {
            $parts['repeat'] = 5;
            $unknown[] = 'hire count';
        }

        // Project consistency: hires that ended with a review (finished, rated work).
        if ($known('jobs_hired') && $known('reviews_count') && $client['jobs_hired'] > 0) {
            $parts['consistency'] = 10 * $this->clamp($client['reviews_count'] / $client['jobs_hired']);
        } else {
            $parts['consistency'] = 5;
            $unknown[] = 'contract history';
        }

        $parts = array_map(fn($v) => round($v, 2), $parts);

        return ['score' => round(array_sum($parts), 2), 'parts' => $parts, 'unknown' => $unknown];
    }

    private function clamp(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }
}
