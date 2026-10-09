<?php

namespace App\Services\Upwork\Scoring;

/**
 * Strategic Value (0-100): how much a job could matter to Code4Each beyond this
 * one engagement. Deliberately separate from the Opportunity Score, which asks
 * "should we bid on this?". Weights are configurable via the ruleset setting
 * `strategic_weights`.
 */
class StrategicScorer
{
    const DEFAULT_WEIGHTS = [
        'engagement' => 30, // length and weekly commitment
        'client' => 25,     // client quality score
        'repeat' => 15,     // client's hiring volume and history with Code4Each
        'platform' => 20,   // SaaS / ERP / CRM / integration / AI platform work
        'fit' => 10,        // proven Code4Each experience
    ];

    const LABELS = [
        'engagement' => 'Engagement size',
        'client' => 'Client quality',
        'repeat' => 'Repeat-hire potential',
        'platform' => 'Platform / integration work',
        'fit' => 'Proven Code4Each fit',
    ];

    const DEFAULT_PLATFORM_TECHS = [
        'saas', 'erp', 'crm', 'api-integration', 'dashboard', 'booking-engine', 'ai-agents', 'workflow-automation',
        'legacy-modernization', 'ecommerce',
    ];

    /**
     * @param array $context engine context (job, client, tech)
     * @param float $experiencePct proven-experience component as % of its max
     */
    public function score(array $context, float $experiencePct, array $settings): array
    {
        $weights = array_merge(self::DEFAULT_WEIGHTS, (array) ($settings['strategic_weights'] ?? []));
        $sum = array_sum(array_map(fn($w) => max(0, (float) $w), $weights)) ?: 1;
        $weights = array_map(fn($w) => max(0, (float) $w) * 100 / $sum, $weights);

        $job = $context['job'];
        $client = $context['client'];
        $notes = [];

        // Engagement: length (75%) and weekly commitment (25%).
        $weeks = $job['duration_weeks'];
        $workload = strtolower((string) $job['workload']);
        $length = $weeks !== null ? min(1.0, $weeks / 30) : 0.2;
        $commitment = $workload === 'full_time' ? 1.0 : ($workload === 'part_time' ? 0.5 : 0.0);
        $parts['engagement'] = 0.75 * $length + 0.25 * $commitment;
        $notes['engagement'] = ($weeks !== null ? "~{$weeks} weeks" : 'duration unknown') . ($workload ? ", {$workload}" : '');

        // Client quality.
        $parts['client'] = ($client['score'] ?? 50) / 100;
        $notes['client'] = 'client score ' . round($client['score'] ?? 50);

        // Repeat-hire potential: overall hiring volume plus history with Code4Each.
        $hires = $client['jobs_hired'];
        $parts['repeat'] = min(1.0, ($hires ?? 0) / 10) * (2 / 3)
            + (($client['seen_before'] ?? 0) >= 1 ? 1 / 6 : 0)
            + (($client['previous_wins'] ?? 0) >= 1 ? 1 / 6 : 0);
        $notes['repeat'] = ($hires !== null ? "{$hires} hires on Upwork" : 'hire count unknown')
            . (($client['seen_before'] ?? 0) >= 1 ? ", seen {$client['seen_before']}× before" : '')
            . (($client['previous_wins'] ?? 0) >= 1 ? ', won before' : '');

        // Platform work: proven platform technology counts fully, others partly.
        $platformSlugs = (array) ($settings['strategic_platform_techs'] ?? self::DEFAULT_PLATFORM_TECHS);
        $platform = array_intersect_key($context['tech'], array_flip($platformSlugs));
        $tiers = array_column($platform, 'tier');
        $parts['platform'] = in_array('proven', $tiers, true) ? 1.0 : ($platform ? 0.6 : 0.0);
        $notes['platform'] = $platform ? implode(', ', array_column($platform, 'name')) : 'no platform/integration work detected';

        // Proven fit.
        $parts['fit'] = max(0.0, min(1.0, $experiencePct / 100));
        $notes['fit'] = round($experiencePct) . '% proven-experience fit';

        $breakdown = [];
        $total = 0.0;
        foreach (self::DEFAULT_WEIGHTS as $key => $_) {
            $points = round($parts[$key] * $weights[$key], 2);
            $total += $points;
            $breakdown[$key] = ['label' => self::LABELS[$key], 'points' => $points, 'max' => round($weights[$key], 2), 'detail' => $notes[$key]];
        }

        return ['score' => round($total, 2), 'parts' => $breakdown];
    }
}
