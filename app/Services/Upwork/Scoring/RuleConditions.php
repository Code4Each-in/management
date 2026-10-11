<?php

namespace App\Services\Upwork\Scoring;

/**
 * Structure of scoring-rule conditions. Rules are data, never code: a
 * condition is a tree of {all|any: [...]}, {not: node} and leaves
 * {field, op, value}. Only whitelisted fields and operators are accepted, so
 * an admin can add rules from the PMS without any eval-style execution.
 *
 * A value may be a literal or {"setting": "key"} to read a ruleset setting.
 */
class RuleConditions
{
    const MAX_DEPTH = 5;

    /** Fields a leaf condition may read. Dotted paths into the evaluation context. */
    const FIELDS = [
        // Job
        'job.title', 'job.description', 'job.text', 'job.job_type', 'job.hourly_min', 'job.hourly_max',
        'job.fixed_budget', 'job.experience_level', 'job.duration', 'job.workload', 'job.contractor_type',
        'job.category', 'job.connects_required', 'job.duration_weeks', 'job.technology_count', 'job.age_hours',
        // Client
        'client.payment_verified', 'client.rating', 'client.reviews_count', 'client.total_spent',
        'client.hire_rate', 'client.jobs_posted', 'client.jobs_hired', 'client.country', 'client.score',
        'client.seen_before', 'client.previous_wins', 'client.previous_approvals', 'client.previous_rejections',
        // Competition / hiring funnel for this posting
        'competition.proposals_count', 'competition.proposals_min', 'competition.interviews',
        'competition.invites_sent', 'competition.total_hired', 'competition.total_offered',
        // Semantic signals from the agent (LLM judgement, catalog terms only)
        'signals.project_clarity', 'signals.scope_size', 'signals.is_individual_only', 'signals.previous_client',
        // AI qualification (null unless an analysis passed the confidence gate). Labelled
        // as AI judgement in explanations; facts above are always preferred.
        'ai.available', 'ai.fit_score', 'ai.confidence', 'ai.recommendation', 'ai.long_term_potential',
        'ai.ai_component', 'ai.budget_realistic', 'ai.urgency', 'ai.complexity', 'ai.high_risk_count',
        // Computed scores (available to classification rules, which run after scoring).
        // Component scores are a PERCENTAGE of that component's max (0-100), so rules
        // keep their meaning when weights change. scores.total is the 0-100 total.
        'scores.total', 'scores.technical_fit', 'scores.experience_fit', 'scores.client_quality',
        'scores.commercial_value', 'scores.long_term', 'scores.ai_automation', 'scores.competition',
        'scores.strategic', 'scores.win_probability', 'scores.expected_value',
        // Ruleset settings
        'settings.agency_mode',
    ];

    const COMPARISON_OPS = ['eq', 'neq', 'gt', 'gte', 'lt', 'lte'];
    const VALUE_OPS = ['between', 'in', 'not_in'];
    const UNARY_OPS = ['is_null', 'not_null', 'is_true', 'is_false'];
    const TEXT_OPS = ['contains_any', 'contains_all', 'not_contains_any'];
    /** Technology operators take no field; they read the detected technology set. */
    const TECH_OPS = ['has_any_tech', 'has_all_tech', 'has_tier', 'tier_count_gte'];

    public static function operators(): array
    {
        return array_merge(self::COMPARISON_OPS, self::VALUE_OPS, self::UNARY_OPS, self::TEXT_OPS, self::TECH_OPS);
    }

    /**
     * @return string[] Human-readable problems; empty when the tree is valid.
     */
    public static function validate($node): array
    {
        $errors = [];
        self::walk($node, 'conditions', 1, $errors);

        return $errors;
    }

    public static function isValid($node): bool
    {
        return self::validate($node) === [];
    }

    private static function walk($node, string $path, int $depth, array &$errors): void
    {
        if (!is_array($node) || $node === []) {
            $errors[] = "{$path}: must be a non-empty object.";
            return;
        }
        if ($depth > self::MAX_DEPTH) {
            $errors[] = "{$path}: nested deeper than " . self::MAX_DEPTH . ' levels.';
            return;
        }

        foreach (['all', 'any'] as $group) {
            if (array_key_exists($group, $node)) {
                if (count($node) !== 1) {
                    $errors[] = "{$path}: '{$group}' must be the only key in its object.";
                }
                if (!is_array($node[$group]) || $node[$group] === [] || array_keys($node[$group]) !== range(0, count($node[$group]) - 1)) {
                    $errors[] = "{$path}.{$group}: must be a non-empty list.";
                    return;
                }
                foreach ($node[$group] as $i => $child) {
                    self::walk($child, "{$path}.{$group}[{$i}]", $depth + 1, $errors);
                }
                return;
            }
        }

        if (array_key_exists('not', $node)) {
            if (count($node) !== 1) {
                $errors[] = "{$path}: 'not' must be the only key in its object.";
            }
            self::walk($node['not'], "{$path}.not", $depth + 1, $errors);
            return;
        }

        self::validateLeaf($node, $path, $errors);
    }

    private static function validateLeaf(array $leaf, string $path, array &$errors): void
    {
        $unknownKeys = array_diff(array_keys($leaf), ['field', 'op', 'value']);
        if ($unknownKeys) {
            $errors[] = "{$path}: unknown key(s) " . implode(', ', $unknownKeys) . '.';
        }

        $op = $leaf['op'] ?? null;
        if (!is_string($op) || !in_array($op, self::operators(), true)) {
            $errors[] = "{$path}: unknown operator '" . (is_scalar($op) ? $op : json_encode($op)) . "'.";
            return;
        }

        $value = $leaf['value'] ?? null;

        if (in_array($op, self::TECH_OPS, true)) {
            if (array_key_exists('field', $leaf)) {
                $errors[] = "{$path}: '{$op}' does not take a field.";
            }
            self::validateTechValue($op, $value, $path, $errors);
            return;
        }

        $field = $leaf['field'] ?? null;
        if (!is_string($field) || !in_array($field, self::FIELDS, true)) {
            $errors[] = "{$path}: unknown field '" . (is_scalar($field) ? $field : json_encode($field)) . "'.";
        }

        if (in_array($op, self::UNARY_OPS, true)) {
            if (array_key_exists('value', $leaf)) {
                $errors[] = "{$path}: '{$op}' does not take a value.";
            }
            return;
        }

        if (!array_key_exists('value', $leaf)) {
            $errors[] = "{$path}: '{$op}' needs a value.";
            return;
        }

        if (self::isSettingReference($value)) {
            if (!in_array($op, self::COMPARISON_OPS, true)) {
                $errors[] = "{$path}: a setting reference can only be used with " . implode('/', self::COMPARISON_OPS) . '.';
            }
            return;
        }

        if ($op === 'between') {
            if (!is_array($value) || count($value) !== 2 || !is_numeric($value[0] ?? null) || !is_numeric($value[1] ?? null)) {
                $errors[] = "{$path}: 'between' needs [min, max] numbers.";
            }
            return;
        }

        if (in_array($op, ['in', 'not_in'], true) || in_array($op, self::TEXT_OPS, true)) {
            if (!is_array($value) || $value === [] || !self::allScalars($value)) {
                $errors[] = "{$path}: '{$op}' needs a non-empty list of values.";
            }
            return;
        }

        if (!is_scalar($value)) {
            $errors[] = "{$path}: '{$op}' needs a single value.";
        }
    }

    private static function validateTechValue(string $op, $value, string $path, array &$errors): void
    {
        $tiers = \App\Models\UpworkTechnology::TIERS;

        if ($op === 'has_tier') {
            if (!is_string($value) || !array_key_exists($value, $tiers)) {
                $errors[] = "{$path}: 'has_tier' needs one of " . implode(', ', array_keys($tiers)) . '.';
            }
            return;
        }

        if ($op === 'tier_count_gte') {
            if (!is_array($value) || !array_key_exists($value['tier'] ?? null, $tiers) || !is_int($value['count'] ?? null) || $value['count'] < 1) {
                $errors[] = "{$path}: 'tier_count_gte' needs {\"tier\": <tier>, \"count\": <int >= 1>}.";
            }
            return;
        }

        if (!is_array($value) || $value === [] || !self::allScalars($value)) {
            $errors[] = "{$path}: '{$op}' needs a non-empty list of technology slugs.";
        }
    }

    public static function isSettingReference($value): bool
    {
        return is_array($value) && array_keys($value) === ['setting'] && is_string($value['setting']) && $value['setting'] !== '';
    }

    private static function allScalars(array $values): bool
    {
        foreach ($values as $v) {
            if (!is_scalar($v) || $v === '') {
                return false;
            }
        }

        return true;
    }
}
