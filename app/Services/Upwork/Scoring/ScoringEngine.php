<?php

namespace App\Services\Upwork\Scoring;

use App\Models\UpworkOpportunity;
use App\Models\UpworkRule;
use App\Models\UpworkRuleset;

/**
 * Deterministic, explainable Upwork job scoring driven by a published ruleset.
 *
 *  1. Detect catalog technologies (aliases + agent slugs + relationship graph).
 *  2. Hard filters run first. A rejected job is recorded (rule, reason, time,
 *     ruleset version) with recommendation SKIP and no score - never dropped.
 *  3. Each component gets a baseline (0..1 of its weight) from job facts, then
 *     score-rule points are added and the result is clamped to [0, weight].
 *     Weights are normalized to 100, so the base score is always 0-100.
 *     Invite penalty: minus INVITE_PENALTY once when the client has sent any
 *     invites, 0 when none or unknown. It is applied to the freshly computed
 *     base on every (re)score, never to a stored score, so it cannot stack.
 *     Ruleset score rules on competition.invites_sent are superseded by it.
 *  4. Recommendation from the ruleset's thresholds; classification rules run
 *     last and may read the computed scores.
 *  5. Dual-track targeting (agency / individual / both), see TargetProfileScorer.
 *     It only adds information: score, recommendation and hard filters are unchanged.
 *
 * Baseline curves have defaults below; a ruleset setting with the same key
 * overrides them, so they can be tuned without code changes.
 */
class ScoringEngine
{
    const VERSION = 'engine-1';

    const DEFAULT_SETTINGS = [
        // Technical fit baseline by best directly-detected tier.
        'tech_baseline_proven' => 0.5,
        'tech_baseline_adjacent' => 0.3,
        'tech_baseline_emerging' => 0.25,
        'tech_baseline_related_only' => 0.2,
        // Experience fit credit per detected technology tier.
        'experience_credit_proven' => 1.0,
        'experience_credit_adjacent' => 0.5,
        'experience_credit_emerging' => 0.25,
        'experience_credit_excluded' => 0.0,
        // Commercial value.
        'rate_floor' => 10,      // $/hr giving 0 rate credit
        'rate_full' => 50,       // $/hr giving full rate credit
        'fixed_floor' => 200,    // $ giving 0 budget credit
        'fixed_full' => 25000,   // $ giving full budget credit
        'size_full_weeks' => 24, // engagement length giving full size credit
        'full_time_bonus' => 0.15,
        'unknown_budget' => 0.4,
        // Long-term potential.
        'long_term_full_weeks' => 30,
        'long_term_max_baseline' => 0.8,
        'unknown_duration' => 0.2,
        // AI / automation baseline when any AI or automation technology is present.
        'ai_baseline' => 0.3,
        // Competition baseline by proposals; halved when the client is already interviewing.
        'competition_unknown' => 0.5,
        'interviewing_threshold' => 3,
    ];

    const COMPETITION_CURVE = [[5, 1.0], [10, 0.8], [15, 0.6], [20, 0.45], [50, 0.25]];
    const COMPETITION_FLOOR = 0.1;

    const AI_CATEGORIES = ['ai', 'automation'];

    /** Points subtracted once when the client has already sent invites on the posting. */
    const INVITE_PENALTY = 5.0;
    const INVITE_FIELD = 'competition.invites_sent';

    /** @var TechnologyMatcher */
    private $matcher;
    /** @var RuleEvaluator */
    private $evaluator;
    /** @var ClientQualityScorer */
    private $clientScorer;

    public function __construct(?TechnologyMatcher $matcher = null, ?RuleEvaluator $evaluator = null, ?ClientQualityScorer $clientScorer = null)
    {
        $this->matcher = $matcher ?? new TechnologyMatcher();
        $this->evaluator = $evaluator ?? new RuleEvaluator();
        $this->clientScorer = $clientScorer ?? new ClientQualityScorer();
    }

    public function score(array $attributes, UpworkRuleset $ruleset): array
    {
        $context = ScoringContext::fromAttributes($attributes);
        $settings = array_merge(self::DEFAULT_SETTINGS, $ruleset->settings ?? []);
        $rules = $ruleset->relationLoaded('rules') ? $ruleset->rules : $ruleset->rules()->get();
        $rules = $rules->where('enabled', true);

        $tech = $this->matcher->detect($context['job']['text'], $context['signals']['technologies']);
        $client = $this->clientScorer->score($context['client']);

        $context['settings'] = $settings;
        $context['tech'] = $tech['direct'];
        $context['job']['technology_count'] = count($tech['direct']);
        $context['client']['score'] = $client['score'];

        $result = [
            'engine_version' => self::VERSION,
            'ruleset_version' => $ruleset->version,
            'scoring_version' => self::VERSION . '/v' . ($ruleset->version ?? 'draft'),
            'technologies' => $tech,
            'client' => $client,
            'hard_filters' => [],
            'components' => [],
            'score' => null,
            'recommendation' => UpworkOpportunity::RECOMMENDATION_SKIP,
            'classifications' => [],
            'classification_details' => [],
            'strategic' => null,
            'outlook' => null,
            'scope' => null,
            'target' => null,
            'invites' => null,
        ];
        $weights = $ruleset->normalizedWeights();
        $targeting = new TargetProfileScorer();

        // 1. Hard filters, before any scoring.
        foreach ($rules->where('type', UpworkRule::TYPE_HARD_FILTER) as $rule) {
            if ($this->evaluator->matches($rule->conditions, $context)) {
                $result['hard_filters'][] = [
                    'key' => $rule->key,
                    'name' => $rule->name,
                    'reason' => $rule->reason,
                    'ruleset_version' => $ruleset->version,
                    'evaluated_at' => now()->toIso8601String(),
                ];
            }
        }
        if ($result['hard_filters']) {
            // Who the job suits is still recorded (from signals only); it never lifts a hard filter.
            $result['target'] = $targeting->score($context, [], $settings, $weights);

            return $result;
        }

        // 2. Components: baseline + rule points, clamped to the normalized weight.
        $baselines = $this->baselines($context, $tech, $client, $settings);
        // The fixed invite penalty replaces any score rule on invites, so invites count exactly once.
        [$inviteRules, $scoreRules] = $rules->where('type', UpworkRule::TYPE_SCORE)
            ->partition(fn($rule) => self::readsInvites($rule->conditions));

        $total = 0.0;
        foreach (UpworkRuleset::COMPONENTS as $key => [$column, $label]) {
            $max = $weights[$key];
            [$fraction, $detail] = $baselines[$key];
            $positive = $fraction * $max;
            $negative = 0.0;
            $fired = [];

            foreach ($scoreRules->where('component', $key) as $rule) {
                if ($this->evaluator->matches($rule->conditions, $context)) {
                    $rule->points >= 0 ? $positive += $rule->points : $negative += $rule->points;
                    $fired[] = ['key' => $rule->key, 'name' => $rule->name, 'points' => $rule->points, 'reason' => $rule->reason];
                }
            }

            // Baseline + bonuses are capped at the weight first, then penalties apply,
            // so a penalty is never absorbed by bonus points above the cap.
            $capped = min($max, $positive);
            $raw = $capped + $negative;
            $points = round(max(0.0, $raw), 2);
            $total += $points;

            $result['components'][$key] = [
                'label' => $label,
                'column' => $column,
                'max' => round($max, 2),
                'baseline' => round($fraction * $max, 2),
                'baseline_detail' => $detail,
                'rules' => $fired,
                'points' => $points,
                'capped' => $positive > $max ? 'max' : ($raw < 0 ? 'min' : null),
                'pct' => $max > 0 ? round($points / $max * 100, 1) : 0.0,
            ];
        }

        // Invite penalty, on the base just computed (never on a stored score).
        $invites = $context['competition']['invites_sent'];
        $penalty = $invites !== null && $invites > 0 ? self::INVITE_PENALTY : 0.0;
        $base = round($total, 2);
        $result['invites'] = [
            'invites_sent' => $invites === null ? null : (int) $invites,
            'penalty' => $penalty,
            'base_score' => $base,
            'final_score' => round(max(0.0, $base - $penalty), 2),
            'note' => $invites === null ? 'Invites sent not available: no penalty'
                : ($penalty ? 'Client already sent ' . (int) $invites . ' invite(s): -' . self::INVITE_PENALTY : 'No invites sent: no penalty'),
            'superseded_rules' => $inviteRules->pluck('key')->values()->all(),
        ];

        $result['score'] = $result['invites']['final_score'];
        $result['recommendation'] = UpworkOpportunity::recommendationForScore($result['score'], $ruleset->thresholds);

        // 3. Strategic Value: a separate 0-100 view of long-term importance.
        $result['strategic'] = (new StrategicScorer())->score($context, $result['components']['experience_fit']['pct'], $settings);

        // 4. Classifications may read the computed scores (as % of each component).
        //    Each records which rule assigned it and why, in plain English.
        // Commercial outlook ESTIMATES (heuristic win probability, contract value, expected value).
        $result['outlook'] = (new OutlookEstimator())->estimate($context, $result['components'], $settings);

        $context['scores'] = [
            'total' => $result['score'],
            'strategic' => $result['strategic']['score'],
            'win_probability' => $result['outlook']['win_probability']['value'],
            'expected_value' => $result['outlook']['value']['expected_value'],
        ] + array_map(fn($c) => $c['pct'], $result['components']);
        $describer = new ConditionDescriber($this->matcher->names(), $settings);
        foreach ($rules->where('type', UpworkRule::TYPE_CLASSIFICATION) as $rule) {
            if ($this->evaluator->matches($rule->conditions, $context)) {
                if (!in_array($rule->classification, $result['classifications'], true)) {
                    $result['classifications'][] = $rule->classification;
                }
                $result['classification_details'][$rule->classification][] = [
                    'rule' => $rule->key,
                    'name' => $rule->name,
                    'why' => $describer->describe($rule->conditions),
                ];
            }
        }
        $result['scope'] = ['size' => $context['signals']['scope_size'], 'source' => $context['signals']['scope_source']];

        // 5. Dual-track targeting: agency, individual or both. Additive; the score above is unchanged.
        $result['target'] = $targeting->score($context, $result['components'], $settings, $weights, $result['classifications']);

        return $result;
    }

    /**
     * @return array<string, array{0: float, 1: string}> component => [fraction 0..1, explanation]
     */
    private function baselines(array $context, array $tech, array $client, array $s): array
    {
        $job = $context['job'];
        $direct = $tech['direct'];
        $tiers = array_column($direct, 'tier');

        // Technical fit: best tier present in the job.
        if (in_array('proven', $tiers, true)) {
            $technical = [$s['tech_baseline_proven'], 'Proven technology detected'];
        } elseif (in_array('adjacent', $tiers, true)) {
            $technical = [$s['tech_baseline_adjacent'], 'Best match is an adjacent technology'];
        } elseif (in_array('emerging', $tiers, true)) {
            $technical = [$s['tech_baseline_emerging'], 'Best match is an emerging technology'];
        } elseif (array_filter($tech['related'], fn($t) => $t['tier'] === 'proven' && $t['weight'] >= 0.5)) {
            $technical = [$s['tech_baseline_related_only'], 'Only related technologies (via the graph)'];
        } else {
            $technical = [0.0, 'No catalog technology detected'];
        }

        // Experience fit: evidence-weighted share of the job's technologies.
        if ($direct) {
            $credit = 0.0;
            foreach ($direct as $t) {
                $credit += $s['experience_credit_' . $t['tier']] ?? 0;
            }
            $counts = array_count_values($tiers);
            ksort($counts);
            $experience = [$credit / count($direct), sprintf('%s of %d detected technologies', $this->tierSummary($counts), count($direct))];
        } else {
            $experience = [0.0, 'No catalog technology detected'];
        }

        // Client quality: the client score.
        $clientNote = 'Client score ' . $client['score'] . '/100' . ($client['unknown'] ? ' (unknown: ' . implode(', ', $client['unknown']) . ')' : '');
        $clientQuality = [$client['score'] / 100, $clientNote];

        // Commercial value: rate and engagement size, or fixed budget.
        $weeks = $job['duration_weeks'];
        if ($job['job_type'] === 'fixed' || ($job['fixed_budget'] !== null && $job['hourly_max'] === null && $job['hourly_min'] === null)) {
            if ($job['fixed_budget'] !== null) {
                $budget = max(1.0, $job['fixed_budget']);
                $fraction = $this->clamp((log10($budget) - log10($s['fixed_floor'])) / (log10($s['fixed_full']) - log10($s['fixed_floor'])));
                $commercial = [$fraction, 'Fixed budget $' . number_format($job['fixed_budget'])];
            } else {
                $commercial = [$s['unknown_budget'], 'Fixed price, budget not stated'];
            }
        } else {
            $rate = $job['hourly_max'] ?? $job['hourly_min'];
            $size = $weeks !== null ? $this->clamp($weeks / $s['size_full_weeks']) : 0.4;
            if (strtolower((string) $job['workload']) === 'full_time') {
                $size = $this->clamp($size + $s['full_time_bonus']);
            }
            if ($rate !== null) {
                $rateFraction = $this->clamp(($rate - $s['rate_floor']) / ($s['rate_full'] - $s['rate_floor']));
                $commercial = [0.6 * $rateFraction + 0.4 * $size, sprintf('Up to $%s/hr; engagement size %d%%', rtrim(rtrim(number_format($rate, 2), '0'), '.'), $size * 100)];
            } else {
                $commercial = [$s['unknown_budget'], 'Hourly rate not stated'];
            }
        }

        // Long-term potential: expected engagement length.
        $longTerm = $weeks !== null
            ? [$s['long_term_max_baseline'] * $this->clamp($weeks / $s['long_term_full_weeks']), ($job['duration'] ?: "{$weeks} weeks")]
            : [$s['unknown_duration'], 'Duration not stated'];

        // AI / automation: any AI or automation technology present.
        $aiTech = array_filter($direct, fn($t) => in_array($t['category'], self::AI_CATEGORIES, true));
        $ai = $aiTech
            ? [$s['ai_baseline'], 'AI/automation: ' . implode(', ', array_column($aiTech, 'name'))]
            : [0.0, 'No AI/automation technology detected'];

        // Competition: proposals already submitted, halved when interviews are under way.
        $competition = $this->competitionBaseline($context['competition'], $s);

        return [
            'technical_fit' => $technical,
            'experience_fit' => $experience,
            'client_quality' => $clientQuality,
            'commercial_value' => $commercial,
            'long_term' => $longTerm,
            'ai_automation' => $ai,
            'competition' => $competition,
        ];
    }

    /** Does a rule's condition tree read the invite count? */
    public static function readsInvites($conditions): bool
    {
        if (!is_array($conditions)) {
            return false;
        }
        if (($conditions['field'] ?? null) === self::INVITE_FIELD) {
            return true;
        }
        foreach ($conditions as $child) {
            if (is_array($child) && self::readsInvites($child)) {
                return true;
            }
        }

        return false;
    }

    private function competitionBaseline(array $c, array $s): array
    {
        $proposals = $c['proposals_count'] ?? $c['proposals_min'];
        if ($proposals === null) {
            $fraction = $s['competition_unknown'];
            $note = 'Proposal count unknown';
        } else {
            $fraction = self::COMPETITION_FLOOR;
            foreach (self::COMPETITION_CURVE as [$below, $value]) {
                if ($proposals < $below) {
                    $fraction = $value;
                    break;
                }
            }
            $note = $c['proposals_count'] !== null ? "{$c['proposals_count']} proposals" : "{$proposals}+ proposals (tier)";
        }

        if (($c['interviews'] ?? 0) >= $s['interviewing_threshold']) {
            $fraction /= 2;
            $note .= "; client already interviewing {$c['interviews']}";
        }

        return [$fraction, $note];
    }

    private function tierSummary(array $counts): string
    {
        return implode(', ', array_map(fn($tier, $n) => "{$n} {$tier}", array_keys($counts), $counts));
    }

    private function clamp(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }
}
