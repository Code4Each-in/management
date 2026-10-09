<?php

namespace App\Services\Upwork\Scoring;

use Illuminate\Support\Arr;

/**
 * Evaluates a validated condition tree (see RuleConditions) against a scoring
 * context. Pure data interpretation: no code is ever executed from a rule.
 *
 * Unknown values never match a comparison: a missing hire rate is neither
 * "< 20" nor ">= 20", and a missing payment flag is neither true nor false.
 */
class RuleEvaluator
{
    /**
     * @param array $context keys: job, client, competition, signals, scores, settings, tech (direct matches)
     */
    public function matches(array $node, array $context): bool
    {
        if (isset($node['all'])) {
            foreach ($node['all'] as $child) {
                if (!$this->matches($child, $context)) {
                    return false;
                }
            }
            return true;
        }

        if (isset($node['any'])) {
            foreach ($node['any'] as $child) {
                if ($this->matches($child, $context)) {
                    return true;
                }
            }
            return false;
        }

        if (array_key_exists('not', $node)) {
            return !$this->matches($node['not'], $context);
        }

        return $this->leaf($node, $context);
    }

    private function leaf(array $leaf, array $context): bool
    {
        $op = $leaf['op'];
        $value = $leaf['value'] ?? null;
        $tech = $context['tech'] ?? [];

        switch ($op) {
            case 'has_any_tech':
                return (bool) array_intersect(array_keys($tech), (array) $value);
            case 'has_all_tech':
                return !array_diff((array) $value, array_keys($tech));
            case 'has_tier':
                return in_array($value, array_column($tech, 'tier'), true);
            case 'tier_count_gte':
                return count(array_filter($tech, fn($t) => $t['tier'] === $value['tier'])) >= $value['count'];
        }

        $actual = Arr::get($context, $leaf['field']);
        if (RuleConditions::isSettingReference($value)) {
            $value = Arr::get($context, 'settings.' . $value['setting']);
        }

        switch ($op) {
            case 'is_null':
                return $actual === null || $actual === '';
            case 'not_null':
                return !($actual === null || $actual === '');
            case 'is_true':
                return $actual === true || $actual === 1 || $actual === '1' || $actual === 'true';
            case 'is_false':
                return $actual === false || $actual === 0 || $actual === '0' || $actual === 'false';
        }

        if ($actual === null || $actual === '' || $value === null) {
            return false;
        }

        switch ($op) {
            case 'eq':
                return $this->equals($actual, $value);
            case 'neq':
                return !$this->equals($actual, $value);
            case 'gt':
                return is_numeric($actual) && is_numeric($value) && $actual > $value;
            case 'gte':
                return is_numeric($actual) && is_numeric($value) && $actual >= $value;
            case 'lt':
                return is_numeric($actual) && is_numeric($value) && $actual < $value;
            case 'lte':
                return is_numeric($actual) && is_numeric($value) && $actual <= $value;
            case 'between':
                return is_numeric($actual) && $actual >= $value[0] && $actual <= $value[1];
            case 'in':
                return $this->inList($actual, $value);
            case 'not_in':
                return !$this->inList($actual, $value);
            case 'contains_any':
                return $this->containsCount((string) $actual, $value) > 0;
            case 'contains_all':
                return $this->containsCount((string) $actual, $value) === count($value);
            case 'not_contains_any':
                return $this->containsCount((string) $actual, $value) === 0;
        }

        return false;
    }

    private function equals($a, $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }
        if (is_bool($a) || is_bool($b)) {
            return (bool) $a === (bool) $b;
        }

        return mb_strtolower(trim((string) $a)) === mb_strtolower(trim((string) $b));
    }

    private function inList($actual, array $list): bool
    {
        foreach ($list as $item) {
            if ($this->equals($actual, $item)) {
                return true;
            }
        }

        return false;
    }

    private function containsCount(string $text, array $terms): int
    {
        $found = 0;
        foreach ($terms as $term) {
            if (preg_match(TechnologyMatcher::patternFor(\App\Models\UpworkTechnologyAlias::normalize((string) $term)), $text)) {
                $found++;
            }
        }

        return $found;
    }
}
