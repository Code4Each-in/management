<?php

namespace App\Services\Upwork\Scoring;

use App\Models\UpworkTechnology;
use App\Models\UpworkTechnologyAlias;

/**
 * Finds catalog technologies in a job. Direct matches come from aliases in the
 * job text (whole words, case-insensitive, optional plural) and from catalog
 * slugs the agent's LLM identified semantically. Related technologies are then
 * added by walking the relationship graph; they give partial credit only and
 * never satisfy has_any_tech rules on their own.
 */
class TechnologyMatcher
{
    const MAX_DEPTH = 2;
    const MIN_RELATED_WEIGHT = 0.3;

    /** @var array<string, array{name: string, tier: string, category: ?string, scoring_weight: float}> */
    private $technologies = [];

    /** @var array<int, array{0: string, 1: string}> [regex, slug] */
    private $patterns = [];

    /** @var array<string, array<int, array{0: string, 1: float}>> slug => [[related slug, weight]] */
    private $edges = [];

    public function __construct()
    {
        $technologies = UpworkTechnology::enabled()->with('aliases')->get();
        $idToSlug = $technologies->pluck('slug', 'id');

        foreach ($technologies as $technology) {
            $this->technologies[$technology->slug] = [
                'name' => $technology->name,
                'tier' => $technology->tier,
                'category' => $technology->category,
                'scoring_weight' => (float) $technology->scoring_weight,
            ];

            $terms = $technology->aliases->pluck('alias_normalized')->push(UpworkTechnologyAlias::normalize($technology->name))->unique();
            foreach ($terms as $term) {
                $this->patterns[] = [self::patternFor($term), $technology->slug, $term];
            }
        }

        $relations = \App\Models\UpworkTechnologyRelation::whereIn('technology_id', $idToSlug->keys())
            ->whereIn('related_technology_id', $idToSlug->keys())
            ->get();
        foreach ($relations as $relation) {
            $this->edges[$idToSlug[$relation->technology_id]][] = [$idToSlug[$relation->related_technology_id], (float) $relation->weight];
        }
    }

    /** @return array<string, string> slug => name of every enabled technology */
    public function names(): array
    {
        return array_map(fn($t) => $t['name'], $this->technologies);
    }

    public static function patternFor(string $term): string
    {
        $quoted = preg_quote($term, '/');
        // Whole word, optional plural. Word characters are letters and digits only,
        // so "node.js" and "c#" still match as written.
        return '/(?<![\p{L}\p{N}])' . str_replace('\ ', '\s+', $quoted) . '(?:e?s)?(?![\p{L}\p{N}])/iu';
    }

    /**
     * @param string   $text        title + description (+ skills)
     * @param string[] $agentSlugs  catalog slugs suggested by the agent
     * @return array{direct: array, related: array}
     */
    public function detect(string $text, array $agentSlugs = []): array
    {
        $direct = [];

        foreach ($this->patterns as [$pattern, $slug, $term]) {
            if (preg_match($pattern, $text)) {
                $direct[$slug]['terms'][] = $term;
            }
        }

        foreach (array_unique($agentSlugs) as $slug) {
            if (isset($this->technologies[$slug])) {
                $direct[$slug]['agent'] = true;
            }
        }

        foreach ($direct as $slug => $match) {
            $direct[$slug] = $this->technologies[$slug] + [
                'terms' => array_values(array_unique($match['terms'] ?? [])),
                'source' => isset($match['terms']) ? (isset($match['agent']) ? 'text+agent' : 'text') : 'agent',
            ];
        }

        return ['direct' => $direct, 'related' => $this->expand($direct)];
    }

    private function expand(array $direct): array
    {
        $related = [];
        $frontier = [];
        foreach (array_keys($direct) as $slug) {
            $frontier[] = [$slug, 1.0, $slug];
        }

        for ($depth = 1; $depth <= self::MAX_DEPTH; $depth++) {
            $next = [];
            foreach ($frontier as [$slug, $weight, $via]) {
                foreach ($this->edges[$slug] ?? [] as [$target, $edgeWeight]) {
                    $carried = round($weight * $edgeWeight, 4);
                    if (isset($direct[$target]) || $carried < self::MIN_RELATED_WEIGHT) {
                        continue;
                    }
                    if (!isset($related[$target]) || $related[$target]['weight'] < $carried) {
                        $related[$target] = $this->technologies[$target] + ['weight' => $carried, 'via' => $via];
                        $next[] = [$target, $carried, $via];
                    }
                }
            }
            $frontier = $next;
        }

        return $related;
    }
}
