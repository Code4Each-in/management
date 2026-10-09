<?php

namespace App\Services\Upwork\Scoring;

/**
 * Commercial outlook ESTIMATES for a scored job:
 *
 *  - Win probability, "heuristic-v1": a transparent additive model (base rate
 *    plus labelled contributions, clamped). It is NOT a trained model; once
 *    enough real Code4Each outcomes exist it can be replaced.
 *  - Contract value: hourly = midpoint rate x assumed hours/week x weeks;
 *    fixed = the budget. Unknown rate or duration gives no number.
 *  - Expected value = contract value x win probability.
 *
 * When an AI analysis passed the confidence gate, two more ESTIMATES appear:
 *  - the win probability is blended with an AI-implied probability (from the
 *    AI fit score), weighted by AI confidence and capped at `ai_weight`;
 *  - AI hour estimates give an AI contract value (and, for fixed price, an
 *    effective hourly rate). They replace the posting-based value only when
 *    the posting alone gives no number.
 *
 * Every coefficient and assumption can be overridden in the ruleset settings
 * (`win_probability`, `ev_hours_per_week`), and every input is recorded.
 */
class OutlookEstimator
{
    const MODEL = 'heuristic-v1';

    const DEFAULT_COEFFICIENTS = [
        'base' => 0.03,
        'technical_fit' => 0.06,  // x fraction of the component
        'experience_fit' => 0.08,
        'client_quality' => 0.04,
        'competition' => [[5, 0.10], [10, 0.06], [15, 0.04], [20, 0.02], [50, 0.0]], // proposals below N
        'competition_crowded' => -0.02, // 50+ proposals
        'competition_unknown' => 0.02,
        'interviewing' => -0.04,        // client already interviewing 3+
        'clarity' => 0.10,              // x (clarity - 0.5), only when the agent rated it
        'budget_fit' => 0.03,           // +/- vs the min_hourly setting
        'previous_win' => 0.10,
        'previous_client' => 0.08,      // Upwork's previous-client flag
        'returning_client' => 0.02,
        'min' => 0.01,
        'max' => 0.50,
        // AI blend: implied probability = ai_min + fit/100 x (ai_max - ai_min);
        // weight = ai_weight x AI confidence.
        'ai_weight' => 0.30,
        'ai_min' => 0.02,
        'ai_max' => 0.35,
    ];

    const DEFAULT_HOURS_PER_WEEK = ['full_time' => 35, 'part_time' => 15, 'as_needed' => 8, 'unknown' => 15];

    public function estimate(array $context, array $components, array $settings): array
    {
        $c = array_replace_recursive(self::DEFAULT_COEFFICIENTS, (array) ($settings['win_probability'] ?? []));
        $pct = fn($key) => ($components[$key]['pct'] ?? 0) / 100;
        $contrib = [];
        $add = function (string $label, float $value) use (&$contrib) {
            if (abs($value) >= 0.0005) {
                $contrib[] = ['label' => $label, 'value' => round($value * 100, 2)];
            }
        };

        $add('Base rate', $c['base']);
        $add('Technical fit', $c['technical_fit'] * $pct('technical_fit'));
        $add('Proven experience', $c['experience_fit'] * $pct('experience_fit'));
        $add('Client quality', $c['client_quality'] * $pct('client_quality'));

        $comp = $context['competition'];
        $proposals = $comp['proposals_count'] ?? $comp['proposals_min'];
        if ($proposals === null) {
            $add('Competition unknown', $c['competition_unknown']);
        } else {
            $value = $c['competition_crowded'];
            foreach ($c['competition'] as [$below, $v]) {
                if ($proposals < $below) {
                    $value = $v;
                    break;
                }
            }
            $add($proposals >= 50 ? '50+ proposals' : "~{$proposals} proposals", $value);
        }
        if (($comp['interviews'] ?? 0) >= 3) {
            $add("Client already interviewing {$comp['interviews']}", $c['interviewing']);
        }

        $clarity = $context['signals']['project_clarity'] ?? null;
        if ($clarity !== null) {
            $add('Project clarity (agent)', $c['clarity'] * ($clarity - 0.5));
        }

        $job = $context['job'];
        $minHourly = $settings['min_hourly'] ?? null;
        if ($minHourly !== null && $job['job_type'] !== 'fixed' && $job['hourly_max'] !== null) {
            $add($job['hourly_max'] >= $minHourly ? 'Rate meets our minimum' : 'Rate below our minimum', $job['hourly_max'] >= $minHourly ? $c['budget_fit'] : -$c['budget_fit']);
        }

        $client = $context['client'];
        if (($client['previous_wins'] ?? 0) >= 1) {
            $add('Won with this client before', $c['previous_win']);
        } elseif (($client['seen_before'] ?? 0) >= 1) {
            $add('Client seen before', $c['returning_client']);
        }
        if (($context['signals']['previous_client'] ?? null) === true) {
            $add('Upwork: previous client', $c['previous_client']);
        }

        $raw = array_sum(array_column($contrib, 'value')) / 100;
        $heuristic = max($c['min'], min($c['max'], $raw));
        $clamped = $heuristic !== $raw;

        // Blend with the AI's view only when the analysis passed the confidence gate.
        $ai = $context['ai'] ?? [];
        $blend = null;
        $probability = $heuristic;
        if (!empty($ai['available']) && $ai['fit_score'] !== null && $ai['confidence'] !== null) {
            $implied = $c['ai_min'] + ($ai['fit_score'] / 100) * ($c['ai_max'] - $c['ai_min']);
            $weight = max(0.0, min(1.0, $c['ai_weight'] * $ai['confidence']));
            $probability = max($c['min'], min($c['max'], (1 - $weight) * $heuristic + $weight * $implied));
            $blend = [
                'heuristic' => round($heuristic * 100, 2),
                'ai_implied' => round($implied * 100, 2),
                'ai_weight' => round($weight, 3),
                'ai_fit_score' => $ai['fit_score'],
                'ai_confidence' => $ai['confidence'],
            ];
        }

        return [
            'win_probability' => [
                'value' => round($probability * 100, 2),
                'model' => $blend ? self::MODEL . '+ai' : self::MODEL,
                'note' => $blend ? 'Estimate: heuristic blended with the AI fit score; not a trained model.' : 'Heuristic estimate, not a trained model.',
                'contributions' => $contrib,
                'clamped' => $clamped ? sprintf('clamped to %d–%d%%', $c['min'] * 100, $c['max'] * 100) : null,
                'blend' => $blend,
            ],
            'value' => $this->value($job, $probability, $settings, $ai),
        ];
    }

    /** Hourly rate the job states (midpoint of a range), or null. */
    private static function rate(array $job): ?float
    {
        $rates = array_filter([$job['hourly_min'], $job['hourly_max']], fn($v) => $v !== null);

        return $rates ? array_sum($rates) / count($rates) : null;
    }

    private static function isFixed(array $job): bool
    {
        return $job['job_type'] === 'fixed' || ($job['fixed_budget'] !== null && $job['hourly_min'] === null && $job['hourly_max'] === null);
    }

    /** AI hour estimate -> AI contract value (hourly) or effective hourly rate (fixed). Null without a usable estimate. */
    private function aiEstimate(array $job, float $probability, array $ai): ?array
    {
        $min = $ai['estimated_hours_min'] ?? null;
        $max = $ai['estimated_hours_max'] ?? null;
        if (empty($ai['available']) || $min === null || $max === null || $max <= 0 || $min > $max) {
            return null;
        }
        $hours = ($min + $max) / 2;
        $out = ['hours_min' => (int) $min, 'hours_max' => (int) $max, 'basis' => $ai['hours_basis'] ?? null,
            'note' => 'AI estimate (assumption), not a quote.', 'contract_value' => null, 'expected_value' => null, 'effective_hourly' => null];

        if (self::isFixed($job)) {
            if ($job['fixed_budget'] !== null) {
                $out['effective_hourly'] = round((float) $job['fixed_budget'] / $hours, 2);
                $out['contract_value'] = round((float) $job['fixed_budget'], 2);
                $out['expected_value'] = round($out['contract_value'] * $probability, 2);
            }
        } elseif (($rate = self::rate($job)) !== null) {
            $out['contract_value'] = round($rate * $hours, 2);
            $out['expected_value'] = round($out['contract_value'] * $probability, 2);
        }

        return $out;
    }

    private function value(array $job, float $probability, array $settings, array $ai = []): array
    {
        $value = $this->postingValue($job, $probability, $settings);
        $aiEstimate = $this->aiEstimate($job, $probability, $ai);
        $value['ai_estimate'] = $aiEstimate;
        $value['source'] = $value['contract_value'] !== null ? 'posting' : null;

        // Only when the posting gives no number does the AI hour estimate stand in.
        if ($value['contract_value'] === null && $aiEstimate && $aiEstimate['contract_value'] !== null) {
            $value['contract_value'] = $aiEstimate['contract_value'];
            $value['expected_value'] = $aiEstimate['expected_value'];
            $value['source'] = 'ai_estimate';
            $value['assumptions'][] = sprintf('AI estimate of %d–%d hours (assumption)', $aiEstimate['hours_min'], $aiEstimate['hours_max']);
            if (($rate = self::rate($job)) !== null) {
                $value['assumptions'][] = sprintf('Rate $%s/hr (posted)', rtrim(rtrim(number_format($rate, 2), '0'), '.'));
            }
            $value['reason'] = null;
        }

        return $value;
    }

    private function postingValue(array $job, float $probability, array $settings): array
    {
        $hoursMap = array_merge(self::DEFAULT_HOURS_PER_WEEK, (array) ($settings['ev_hours_per_week'] ?? []));
        $assumptions = [];

        if ($job['job_type'] === 'fixed' || ($job['fixed_budget'] !== null && $job['hourly_min'] === null && $job['hourly_max'] === null)) {
            if ($job['fixed_budget'] === null) {
                return ['contract_value' => null, 'expected_value' => null, 'reason' => 'Fixed price without a stated budget.', 'assumptions' => []];
            }
            $contract = (float) $job['fixed_budget'];
            $assumptions[] = 'Fixed budget as posted: $' . number_format($contract);
        } else {
            $rates = array_filter([$job['hourly_min'], $job['hourly_max']], fn($v) => $v !== null);
            if (!$rates) {
                return ['contract_value' => null, 'expected_value' => null, 'reason' => 'Hourly rate not stated.', 'assumptions' => []];
            }
            if ($job['duration_weeks'] === null) {
                return ['contract_value' => null, 'expected_value' => null, 'reason' => 'Duration not stated.', 'assumptions' => []];
            }
            $rate = array_sum($rates) / count($rates);
            $workload = strtolower((string) $job['workload']) ?: 'unknown';
            $hours = (float) ($hoursMap[$workload] ?? $hoursMap['unknown']);
            $contract = $rate * $hours * $job['duration_weeks'];
            $assumptions[] = sprintf('Rate $%s/hr (%s)', rtrim(rtrim(number_format($rate, 2), '0'), '.'), count($rates) > 1 ? 'midpoint of posted range' : 'only posted rate');
            $assumptions[] = sprintf('%s hrs/week (assumed for %s)', $hours, str_replace('_', '-', $workload));
            $assumptions[] = sprintf('~%s weeks (midpoint of "%s")', $job['duration_weeks'], $job['duration']);
        }

        return [
            'contract_value' => round($contract, 2),
            'expected_value' => round($contract * $probability, 2),
            'reason' => null,
            'assumptions' => $assumptions,
        ];
    }
}
