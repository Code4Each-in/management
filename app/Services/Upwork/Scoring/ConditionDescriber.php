<?php

namespace App\Services\Upwork\Scoring;

/**
 * Turns a structured rule condition into plain English for reviewers, e.g.
 * "duration ≥ 12 weeks AND client quality ≥ 60% AND mentions SaaS or CRM".
 * Setting references are shown with the value in force.
 */
class ConditionDescriber
{
    const FIELD_LABELS = [
        'job.title' => 'title', 'job.description' => 'description', 'job.text' => 'posting',
        'job.job_type' => 'job type', 'job.hourly_min' => 'hourly minimum ($)', 'job.hourly_max' => 'hourly maximum ($)',
        'job.fixed_budget' => 'fixed budget ($)', 'job.experience_level' => 'experience level', 'job.duration' => 'duration',
        'job.duration_weeks' => 'duration (weeks)', 'job.workload' => 'workload', 'job.contractor_type' => 'contractor type',
        'job.category' => 'category', 'job.connects_required' => 'Connects', 'job.technology_count' => 'technologies detected', 'job.age_hours' => 'job age (hours)',
        'ai.available' => 'AI analysis available', 'ai.fit_score' => 'AI fit score (estimate)', 'ai.confidence' => 'AI confidence',
        'ai.recommendation' => 'AI recommendation', 'ai.long_term_potential' => 'AI: long-term potential', 'ai.ai_component' => 'AI: AI/automation component',
        'ai.budget_realistic' => 'AI: budget realistic', 'ai.urgency' => 'AI: urgency', 'ai.complexity' => 'AI: complexity', 'ai.high_risk_count' => 'AI: high risks',
        'client.payment_verified' => 'payment verified', 'client.rating' => 'client rating', 'client.reviews_count' => 'client reviews',
        'client.total_spent' => 'client spend ($)', 'client.hire_rate' => 'client hire rate (%)', 'client.jobs_posted' => 'client jobs posted',
        'client.jobs_hired' => 'client hires', 'client.country' => 'client country', 'client.score' => 'client score',
        'client.seen_before' => 'previous jobs from this client', 'client.previous_wins' => 'previous wins with this client',
        'client.previous_approvals' => 'previous approvals for this client', 'client.previous_rejections' => 'previous rejections for this client',
        'competition.proposals_count' => 'proposals', 'competition.proposals_min' => 'proposals', 'competition.interviews' => 'interviews',
        'competition.invites_sent' => 'invites sent', 'competition.total_hired' => 'hires on this posting', 'competition.total_offered' => 'offers on this posting',
        'signals.project_clarity' => 'project clarity', 'signals.scope_size' => 'scope', 'signals.is_individual_only' => 'individual-only',
        'signals.previous_client' => 'Upwork previous client',
        'scores.total' => 'opportunity score', 'scores.technical_fit' => 'technical fit', 'scores.experience_fit' => 'proven experience',
        'scores.client_quality' => 'client quality', 'scores.commercial_value' => 'commercial value', 'scores.long_term' => 'long-term potential',
        'scores.ai_automation' => 'AI / automation value', 'scores.competition' => 'competition', 'scores.strategic' => 'strategic value',
        'scores.win_probability' => 'win probability (%)', 'scores.expected_value' => 'expected value ($)',
        'settings.agency_mode' => 'agency mode',
    ];

    const OPS = ['eq' => 'is', 'neq' => 'is not', 'gt' => '>', 'gte' => '≥', 'lt' => '<', 'lte' => '≤'];

    /** @var array slug => name */
    private $techNames;
    /** @var array */
    private $settings;

    public function __construct(array $techNames = [], array $settings = [])
    {
        $this->techNames = $techNames;
        $this->settings = $settings;
    }

    public function describe(array $node): string
    {
        if (isset($node['all'])) {
            return implode(' AND ', array_map(fn($c) => $this->wrap($c), $node['all']));
        }
        if (isset($node['any'])) {
            return implode(' OR ', array_map(fn($c) => $this->wrap($c), $node['any']));
        }
        if (array_key_exists('not', $node)) {
            return 'NOT ' . $this->wrap($node['not'], true);
        }

        return $this->leaf($node);
    }

    private function wrap(array $node, bool $always = false): string
    {
        $text = $this->describe($node);
        $compound = isset($node['all']) || isset($node['any']);

        return ($compound && (count($node['all'] ?? $node['any']) > 1)) || ($always && $compound) ? "({$text})" : $text;
    }

    private function leaf(array $leaf): string
    {
        $op = $leaf['op'];
        $value = $leaf['value'] ?? null;

        switch ($op) {
            case 'has_any_tech':
                return 'mentions ' . $this->techList($value, 'or');
            case 'has_all_tech':
                return 'mentions ' . $this->techList($value, 'and');
            case 'has_tier':
                return 'has a ' . strtoupper($value) . ' technology';
            case 'tier_count_gte':
                return "has ≥ {$value['count']} " . strtoupper($value['tier']) . ' technologies';
        }

        $field = self::FIELD_LABELS[$leaf['field']] ?? $leaf['field'];
        $isPct = str_starts_with($leaf['field'], 'scores.') && !in_array($leaf['field'], ['scores.total', 'scores.strategic', 'scores.win_probability', 'scores.expected_value'], true);

        switch ($op) {
            case 'is_null':
                return "{$field} is unknown";
            case 'not_null':
                return "{$field} is known";
            case 'is_true':
                return $field === 'agency mode' ? 'agency mode is on' : "{$field}: yes";
            case 'is_false':
                return "{$field}: no";
            case 'between':
                return "{$field} between {$value[0]} and {$value[1]}";
            case 'in':
                return "{$field} is one of " . implode(', ', $value);
            case 'not_in':
                return "{$field} is not one of " . implode(', ', $value);
            case 'contains_any':
                return "{$field} mentions any of “" . implode('”, “', $value) . '”';
            case 'contains_all':
                return "{$field} mentions all of “" . implode('”, “', $value) . '”';
            case 'not_contains_any':
                return "{$field} mentions none of “" . implode('”, “', $value) . '”';
        }

        if (RuleConditions::isSettingReference($value)) {
            $key = $value['setting'];
            $value = array_key_exists($key, $this->settings) ? "{$key} ({$this->settings[$key]})" : $key;
        } elseif ($isPct && is_numeric($value)) {
            $value .= '%';
        }

        return "{$field} " . (self::OPS[$op] ?? $op) . " {$value}";
    }

    private function techList($slugs, string $joiner): string
    {
        $names = array_map(fn($s) => $this->techNames[$s] ?? $s, (array) $slugs);
        if (count($names) <= 1) {
            return implode('', $names);
        }
        $last = array_pop($names);

        return implode(', ', $names) . " {$joiner} {$last}";
    }
}
