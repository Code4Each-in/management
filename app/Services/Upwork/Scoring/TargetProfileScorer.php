<?php

namespace App\Services\Upwork\Scoring;

use App\Models\UpworkRuleset;
use App\Services\Upwork\Targeting\TargetProfiles;

/**
 * Dual-track targeting: is a job better suited to the Code4Each agency
 * profile, an individual freelancer profile, or either?
 *
 * Purely additive. It never changes the Opportunity Score, recommendation or
 * hard filters, and never excludes a job: it classifies and explains.
 *
 *  1. Posting restriction (contractor type, agent flag, explicit wording)
 *     makes the other track ineligible for this job.
 *  2. Track fit (0-100) re-weights the engine's own component scores with
 *     per-track weights; the individual track uses the best individual
 *     profile's verified technologies for experience when profiles exist.
 *  3. Suitability signals from scope, skills, budget, hiring needs and
 *     wording add or remove points per track, each with a reason.
 *  4. Best fit: the restricted track, else the higher track fit unless the
 *     two are within the margin ("both"). Conflicting or missing signals
 *     mark the result ambiguous so a person decides.
 *
 * Everything is configurable through the ruleset setting `target_profiles`
 * (weights, margin, points, preferences, phrases); defaults are below.
 */
class TargetProfileScorer
{
    const VERSION = 'target-1';

    const AGENCY = TargetProfiles::TRACK_AGENCY;
    const INDIVIDUAL = TargetProfiles::TRACK_INDIVIDUAL;
    const BOTH = TargetProfiles::TRACK_BOTH;

    /** Individual track weights when the ruleset sets none. The agency track defaults to the ruleset's own weights. */
    const DEFAULT_INDIVIDUAL_WEIGHTS = [
        'technical_fit' => 25, 'experience_fit' => 25, 'client_quality' => 20, 'commercial_value' => 10,
        'long_term' => 5, 'ai_automation' => 5, 'competition' => 10,
    ];

    const DEFAULTS = [
        'weights' => ['agency' => null, 'individual' => null],
        'margin' => 8, // track fits closer than this are suitable for either profile
        'points' => [
            'scope' => 6, 'team_wording' => 8, 'solo_wording' => 8, 'multiple_hires' => 8,
            'multi_discipline' => 6, 'focused_skills' => 4, 'below_preference' => 8, 'ai_agency_friendly' => 4,
        ],
        // Profile-specific preferences. A job outside them is still scored and shown; it only loses points on that track.
        'preferences' => [
            'agency' => ['scopes' => ['medium', 'large'], 'min_hourly' => 25, 'min_fixed' => 1000],
            'individual' => ['scopes' => ['small', 'medium'], 'min_hourly' => 15, 'min_fixed' => 150],
        ],
        'multi_discipline_min' => 3,
        'individual_only_phrases' => [
            'no agencies', 'no agency', 'agencies need not apply', 'not looking for an agency', 'not looking for agencies',
            'individuals only', 'individual freelancers only', 'freelancers only', 'freelancer only', 'independent contractors only',
        ],
        'agency_only_phrases' => ['agencies only', 'agency only', 'only agencies', 'must be an agency', 'agency required'],
        'team_phrases' => [
            'team of developers', 'team of engineers', 'dedicated team', 'full team', 'cross-functional team', 'development agency',
            'agencies welcome', 'multiple developers', 'several developers', 'project manager', 'qa engineer', 'qa tester',
            'designers and developers', 'designer and developer', 'end-to-end delivery',
        ],
        'solo_phrases' => [
            'individual freelancer', 'solo developer', 'single developer', 'one developer', 'independent developer',
            'independent freelancer', 'join our team', 'work directly with',
        ],
    ];

    /** Catalog category => engineering discipline (integration/domain are cross-cutting and not counted). */
    const DISCIPLINES = [
        'backend' => 'backend', 'database' => 'backend', 'frontend' => 'frontend', 'mobile' => 'mobile', 'devops' => 'devops',
        'cms' => 'CMS / e-commerce', 'ecommerce' => 'CMS / e-commerce', 'ai' => 'AI', 'automation' => 'automation',
        'non-engineering' => 'non-engineering',
    ];

    /** Proposal angles: code => [label, guidance used by the proposal draft]. */
    const ANGLES = [
        'team_delivery' => ['Team delivery', 'Lead with how a coordinated team (development, QA, project management) delivers the full scope reliably and on schedule.'],
        'specialist' => ['Hands-on specialist', 'Lead with direct, personal expertise in the exact stack and a single point of contact.'],
        'quick_turnaround' => ['Quick, focused fix', 'Lead with a short, clearly bounded plan for a small task and an early first deliverable.'],
        'long_term_partner' => ['Long-term partner', 'Lead with continuity: ongoing support, roadmap thinking and availability over months.'],
        'proven_experience' => ['Proven experience', 'Lead with the most relevant verified past work and how it maps to this job.'],
        'ai_automation' => ['AI / automation outcome', 'Lead with the business outcome of the automation and a safe, staged rollout.'],
    ];

    /**
     * @param array $context         engine context (job, client, signals, ai, tech)
     * @param array $components      engine components (empty when a hard filter fired)
     * @param array $settings        ruleset settings (merged with engine defaults)
     * @param array $rulesetWeights  the ruleset's normalized component weights
     * @param string[] $classifications codes assigned by classification rules
     */
    public function score(array $context, array $components, array $settings, array $rulesetWeights, array $classifications = []): array
    {
        $cfg = self::config($settings);
        $job = $context['job'];
        $text = (string) $job['text'];
        $tech = $context['tech'] ?? [];

        $restriction = $this->restriction($context, $text, $cfg);
        $signals = $this->signals($context, $text, $tech, $cfg);

        // Track fit from the engine's components, per-track weights.
        $individuals = TargetProfiles::individuals();
        $bestIndividual = $this->bestIndividual($individuals, $tech);
        $scored = $components !== [];
        $weights = [
            self::AGENCY => self::normalizeWeights($cfg['weights']['agency'] ?? null, $rulesetWeights),
            self::INDIVIDUAL => self::normalizeWeights($cfg['weights']['individual'] ?? null, self::DEFAULT_INDIVIDUAL_WEIGHTS),
        ];
        $experience = [
            self::AGENCY => $this->agencyExperience($components, $tech),
            self::INDIVIDUAL => $this->individualExperience($components, $tech, $bestIndividual, (bool) $individuals),
        ];

        $tracks = [];
        foreach ([self::AGENCY, self::INDIVIDUAL] as $track) {
            $points = array_sum(array_map(fn($s) => $s['points'], array_filter($signals, fn($s) => $s['track'] === $track)));
            $base = null;
            if ($scored) {
                $base = 0.0;
                foreach ($weights[$track] as $key => $w) {
                    $pct = $key === 'experience_fit' && $experience[$track]['pct'] !== null ? $experience[$track]['pct'] : ($components[$key]['pct'] ?? 0);
                    $base += $pct / 100 * $w;
                }
            }
            $eligible = !$restriction || $restriction['track'] === $track;
            $tracks[$track] = [
                'eligible' => $eligible,
                'base' => $base !== null ? round($base, 2) : null,
                'points' => round($points, 2),
                'score' => $base !== null && $eligible ? round(max(0.0, min(100.0, $base + $points)), 2) : null,
                'weights' => array_map(fn($w) => round($w, 2), $weights[$track]),
                'experience' => $experience[$track],
            ];
        }
        $tracks[self::INDIVIDUAL]['profile'] = $bestIndividual['key'] ?? null;

        // Best fit. A signal that favours both tracks (e.g. a scope both profiles prefer) does not distinguish them.
        $margin = (float) $cfg['margin'];
        $positiveKeys = fn($track) => array_unique(array_column(array_filter($signals, fn($s) => $s['track'] === $track && $s['points'] > 0), 'key'));
        $shared = array_intersect($positiveKeys(self::AGENCY), $positiveKeys(self::INDIVIDUAL));
        $agencyLean = array_values(array_filter($signals, fn($s) => $s['track'] === self::AGENCY && $s['points'] > 0 && !in_array($s['key'], $shared, true)));
        $individualLean = array_values(array_filter($signals, fn($s) => $s['track'] === self::INDIVIDUAL && $s['points'] > 0 && !in_array($s['key'], $shared, true)));
        $notes = [];

        if ($restriction) {
            $target = $restriction['track'];
            $opposite = $target === self::AGENCY ? $individualLean : $agencyLean;
            $ambiguous = $opposite || $restriction['conflict'];
            if ($restriction['conflict']) {
                $notes[] = "Contradiction: {$restriction['evidence']}, but " . lcfirst($restriction['conflict']) . '. Upwork\'s own field is trusted first; check the posting.';
            }
            if ($opposite) {
                $notes[] = 'The posting only accepts ' . ($target === self::AGENCY ? 'agencies' : 'individuals') . ', but other signals point the other way: ' . implode('; ', array_column($opposite, 'reason')) . '.';
            }
        } else {
            $a = $scored ? $tracks[self::AGENCY]['score'] : $tracks[self::AGENCY]['points'];
            $i = $scored ? $tracks[self::INDIVIDUAL]['score'] : $tracks[self::INDIVIDUAL]['points'];
            $diff = $a - $i;
            $target = abs($diff) < $margin ? self::BOTH : ($diff > 0 ? self::AGENCY : self::INDIVIDUAL);
            if ($agencyLean && $individualLean) {
                $ambiguous = true;
                $notes[] = 'Signals conflict: some favour the agency, others an individual; a person should choose.';
            } elseif (!$agencyLean && !$individualLean && $target !== self::BOTH) {
                $ambiguous = true;
                $notes[] = 'No posting preference, scope or hiring signal separates the profiles; only the track weights do. A person should choose.';
            } else {
                $ambiguous = false;
            }
        }
        $confidence = $ambiguous ? 'low' : ($restriction ? 'high' : 'medium');

        $suggested = $this->suggestedProfile($target, $tracks, $bestIndividual, (bool) $individuals);
        [$angle, $angleReason] = $this->suggestAngle(TargetProfiles::trackOf($suggested), $context, $components, $classifications);

        $reasons = $this->reasons($target, $restriction, $signals, $tracks, $scored, $margin, $notes);

        return [
            'version' => self::VERSION,
            'target' => $target,
            'confidence' => $confidence,
            'ambiguous' => $ambiguous,
            'summary' => $this->summary($target, $restriction, $signals, $tracks, $scored),
            'restriction' => $restriction,
            'tracks' => $tracks,
            'signals' => array_values($signals),
            'reasons' => $reasons,
            'margin' => $margin,
            'suggested_profile' => $suggested,
            'suggested_angle' => $angle,
            'angle_reason' => $angleReason,
        ];
    }

    /** Defaults overridden by the ruleset's `target_profiles` setting (lists replace, maps merge). */
    public static function config(array $settings): array
    {
        return self::merge(self::DEFAULTS, (array) ($settings['target_profiles'] ?? []));
    }

    private static function merge(array $defaults, array $custom): array
    {
        foreach ($custom as $key => $value) {
            $isMap = fn($v) => is_array($v) && $v !== [] && array_keys($v) !== range(0, count($v) - 1);
            $defaults[$key] = isset($defaults[$key]) && $isMap($defaults[$key]) && $isMap($value) ? self::merge($defaults[$key], $value) : $value;
        }

        return $defaults;
    }

    /** Weights over the engine components, scaled to total 100. */
    private static function normalizeWeights($weights, array $fallback): array
    {
        $weights = is_array($weights) && $weights ? $weights : $fallback;
        $out = [];
        foreach (array_keys(UpworkRuleset::COMPONENTS) as $key) {
            $out[$key] = max(0.0, (float) ($weights[$key] ?? 0));
        }
        $sum = array_sum($out);

        return $sum > 0 ? array_map(fn($w) => $w * 100 / $sum, $out) : array_map(fn() => 0.0, $out);
    }

    /**
     * Who the posting accepts, strongest source first: Upwork's contractor
     * type, the agent's flag, then explicit wording. The first source wins;
     * a weaker source saying the opposite is kept as `conflict`.
     */
    private function restriction(array $context, string $text, array $cfg): ?array
    {
        $found = [];
        $type = strtolower((string) $context['job']['contractor_type']);
        if ($type !== '' && (str_contains($type, 'individual') || str_contains($type, 'independent'))) {
            $found[] = ['track' => self::INDIVIDUAL, 'source' => 'contractor_type', 'evidence' => "Upwork contractor type: {$context['job']['contractor_type']}"];
        } elseif ($type !== '' && str_contains($type, 'agenc')) {
            $found[] = ['track' => self::AGENCY, 'source' => 'contractor_type', 'evidence' => "Upwork contractor type: {$context['job']['contractor_type']}"];
        }
        if (($context['signals']['is_individual_only'] ?? null) === true) {
            $found[] = ['track' => self::INDIVIDUAL, 'source' => 'agent_signal', 'evidence' => 'Agent flagged the job as individual-only'];
        }
        foreach ([self::INDIVIDUAL => 'individual_only_phrases', self::AGENCY => 'agency_only_phrases'] as $track => $key) {
            if ($phrase = $this->firstPhrase($text, $cfg[$key])) {
                $found[] = ['track' => $track, 'source' => 'wording', 'evidence' => "Posting says \"{$phrase}\""];
            }
        }
        if (!$found) {
            return null;
        }

        $restriction = $found[0];
        $contradicting = array_values(array_filter($found, fn($f) => $f['track'] !== $restriction['track']));
        $restriction['conflict'] = $contradicting ? $contradicting[0]['evidence'] : null;

        return $restriction;
    }

    /** Suitability signals: [key, track, points, reason]. */
    private function signals(array $context, string $text, array $tech, array $cfg): array
    {
        $job = $context['job'];
        $p = $cfg['points'];
        $prefs = $cfg['preferences'];
        $out = [];
        $add = function (string $key, string $track, float $points, string $reason) use (&$out) {
            if ($points != 0) {
                $out[] = ['key' => $key, 'track' => $track, 'points' => $points, 'reason' => $reason];
            }
        };

        // Scope against each profile's preferred scopes.
        $scope = $context['signals']['scope_size'] ?? null;
        if ($scope) {
            $how = ($context['signals']['scope_source'] ?? null) === 'inferred' ? 'inferred from ' . ($job['duration'] ?: 'duration') . ($job['fixed_budget'] !== null ? ' and budget' : '') : 'from the agent';
            foreach ([self::AGENCY, self::INDIVIDUAL] as $track) {
                if (in_array($scope, (array) ($prefs[$track]['scopes'] ?? []), true)) {
                    $add('scope', $track, (float) $p['scope'], ucfirst($scope) . " scope ({$how}) suits the {$track} profile");
                }
            }
        }

        // Hiring needs and wording.
        if (($job['freelancers_to_hire'] ?? 0) >= 2) {
            $add('multiple_hires', self::AGENCY, (float) $p['multiple_hires'], "Client wants to hire {$job['freelancers_to_hire']} freelancers");
        }
        if ($phrase = $this->firstPhrase($text, $cfg['team_phrases'])) {
            $add('team_wording', self::AGENCY, (float) $p['team_wording'], "Posting asks for a team (\"{$phrase}\")");
        }
        if ($phrase = $this->firstPhrase($text, $cfg['solo_phrases'])) {
            $add('solo_wording', self::INDIVIDUAL, (float) $p['solo_wording'], "Posting asks for one person (\"{$phrase}\")");
        }

        // Skills: breadth of disciplines.
        $disciplines = [];
        foreach ($tech as $t) {
            if (isset(self::DISCIPLINES[$t['category'] ?? ''])) {
                $disciplines[self::DISCIPLINES[$t['category']]][] = $t['name'];
            }
        }
        if (count($disciplines) >= (int) $cfg['multi_discipline_min']) {
            $add('multi_discipline', self::AGENCY, (float) $p['multi_discipline'], 'Spans ' . count($disciplines) . ' disciplines (' . implode(', ', array_keys($disciplines)) . ')');
        } elseif (count($disciplines) === 1 && count($tech) <= 3) {
            $add('focused_skills', self::INDIVIDUAL, (float) $p['focused_skills'], 'Focused skill set (' . implode(', ', array_column($tech, 'name')) . ')');
        }

        // Budget against each profile's minimums.
        $rate = $job['hourly_max'] ?? $job['hourly_min'];
        $fixed = $job['job_type'] === 'fixed' || ($job['fixed_budget'] !== null && $rate === null) ? $job['fixed_budget'] : null;
        foreach ([self::AGENCY, self::INDIVIDUAL] as $track) {
            $min = $prefs[$track] ?? [];
            if ($fixed === null && $rate !== null && isset($min['min_hourly']) && $rate < (float) $min['min_hourly']) {
                $add('below_preference', $track, -(float) $p['below_preference'], 'Rate up to $' . self::num($rate) . "/hr is below the {$track} minimum of \$" . self::num($min['min_hourly']) . '/hr');
            } elseif ($fixed !== null && isset($min['min_fixed']) && $fixed < (float) $min['min_fixed']) {
                $add('below_preference', $track, -(float) $p['below_preference'], 'Fixed budget $' . number_format($fixed) . " is below the {$track} minimum of \$" . number_format((float) $min['min_fixed']));
            }
        }

        // AI judgement (only when an analysis passed the confidence gate).
        $friendly = $context['ai']['agency_friendly'] ?? null;
        if ($friendly === 'yes') {
            $add('ai_agency_friendly', self::AGENCY, (float) $p['ai_agency_friendly'], 'AI analysis: agency-friendly (estimate)');
        } elseif ($friendly === 'no') {
            $add('ai_agency_friendly', self::INDIVIDUAL, (float) $p['ai_agency_friendly'], 'AI analysis: not agency-friendly (estimate)');
        }

        return $out;
    }

    private function firstPhrase(string $text, array $phrases): ?string
    {
        foreach ($phrases as $phrase) {
            if (is_string($phrase) && $phrase !== '' && preg_match(TechnologyMatcher::patternFor(mb_strtolower($phrase)), $text)) {
                return $phrase;
            }
        }

        return null;
    }

    /** The individual profile whose verified technologies cover most of the job. */
    private function bestIndividual(array $individuals, array $tech): ?array
    {
        $best = null;
        $names = array_map(fn($t) => $t['name'], $tech); // slug => name
        foreach ($individuals as $profile) {
            $matched = array_values(array_intersect_key($names, array_flip($profile['technologies'])));
            if (!$best || count($matched) > count($best['matched'])) {
                $best = $profile + ['matched' => $matched];
            }
        }

        return $best;
    }

    private function agencyExperience(array $components, array $tech): array
    {
        $proven = array_values(array_column(array_filter($tech, fn($t) => $t['tier'] === 'proven'), 'name'));
        $excluded = array_values(array_column(array_filter($tech, fn($t) => $t['tier'] === 'excluded'), 'name'));
        $detail = $proven ? 'Verified agency experience (PROVEN): ' . implode(', ', $proven) : 'No PROVEN agency technology in this job';
        if ($excluded) {
            $detail .= '; excluded capability: ' . implode(', ', $excluded);
        }

        return ['pct' => $components['experience_fit']['pct'] ?? null, 'detail' => $detail, 'source' => 'catalog'];
    }

    private function individualExperience(array $components, array $tech, ?array $best, bool $configured): array
    {
        if (!$configured) {
            return ['pct' => $components['experience_fit']['pct'] ?? null, 'source' => 'catalog',
                'detail' => 'No individual profiles configured: using the agency catalog evidence'];
        }
        if (!$tech) {
            return ['pct' => 0.0, 'source' => 'profile', 'detail' => 'No catalog technology detected'];
        }
        $matched = $best['matched'] ?? [];

        return [
            'pct' => round(count($matched) / count($tech) * 100, 1),
            'source' => 'profile',
            'detail' => $matched
                ? "Best individual match: {$best['name']} — verified " . implode(', ', $matched) . ' (' . count($matched) . ' of ' . count($tech) . ' job technologies)'
                : 'No individual profile lists the job\'s technologies',
        ];
    }

    private function suggestedProfile(string $target, array $tracks, ?array $best, bool $configured): string
    {
        $track = $target;
        if ($target === self::BOTH) {
            $a = $tracks[self::AGENCY]['score'] ?? $tracks[self::AGENCY]['points'];
            $i = $tracks[self::INDIVIDUAL]['score'] ?? $tracks[self::INDIVIDUAL]['points'];
            $track = $i > $a ? self::INDIVIDUAL : self::AGENCY;
        }
        if ($track === self::AGENCY) {
            return self::AGENCY;
        }

        return $configured && $best ? "individual:{$best['key']}" : TargetProfiles::GENERIC_INDIVIDUAL;
    }

    /** @return array{0: string, 1: string} [angle code, why] */
    private function suggestAngle(?string $track, array $context, array $components, array $classifications): array
    {
        $job = $context['job'];
        if (in_array('AI_AUTOMATION', $classifications, true)) {
            return ['ai_automation', 'The job is AI / automation work'];
        }
        if (($context['signals']['scope_size'] ?? null) === 'small' || in_array('QUICK_WIN', $classifications, true)) {
            return ['quick_turnaround', 'Small, bounded scope'];
        }
        if (array_intersect(['LONG_TERM', 'STRATEGIC'], $classifications) || ($job['duration_weeks'] ?? 0) >= 24) {
            return ['long_term_partner', 'Long or strategic engagement'];
        }
        if (($components['experience_fit']['pct'] ?? 0) >= 70) {
            return ['proven_experience', 'Strong match with verified experience'];
        }

        return $track === self::INDIVIDUAL
            ? ['specialist', 'Individual profile: lead with personal expertise']
            : ['team_delivery', 'Agency profile: lead with team delivery'];
    }

    private function reasons(string $target, ?array $restriction, array $signals, array $tracks, bool $scored, float $margin, array $notes): array
    {
        $reasons = [];
        if ($restriction) {
            $reasons[] = $restriction['evidence'] . ': only ' . ($restriction['track'] === self::AGENCY ? 'agencies' : 'individuals') . ' can bid.';
        }
        usort($signals, fn($x, $y) => abs($y['points']) <=> abs($x['points']));
        foreach ($signals as $s) {
            $reasons[] = sprintf('%s (%s%s %s)', $s['reason'], $s['points'] >= 0 ? '+' : '', self::num($s['points']), $s['track']);
        }
        if ($scored) {
            $a = $tracks[self::AGENCY]['score'];
            $i = $tracks[self::INDIVIDUAL]['score'];
            $reasons[] = 'Track fit: agency ' . ($a === null ? 'n/a' : self::num($a)) . ', individual ' . ($i === null ? 'n/a' : self::num($i))
                . ($restriction ? '' : ' (within ' . self::num($margin) . ' points counts as either)');
        }
        foreach ([self::AGENCY, self::INDIVIDUAL] as $track) {
            if ($target === $track || $target === self::BOTH) {
                $reasons[] = $tracks[$track]['experience']['detail'];
            }
        }

        return array_merge($reasons, $notes);
    }

    private function summary(string $target, ?array $restriction, array $signals, array $tracks, bool $scored): string
    {
        $lead = [self::AGENCY => 'Better suited to the agency profile', self::INDIVIDUAL => 'Better suited to an individual freelancer profile', self::BOTH => 'Suitable for either profile'][$target];
        $why = [];
        if ($restriction) {
            $why[] = $restriction['evidence'];
        }
        $support = array_filter($signals, fn($s) => $s['points'] > 0 && ($target === self::BOTH || $s['track'] === $target));
        usort($support, fn($x, $y) => $y['points'] <=> $x['points']);
        foreach (array_slice($support, 0, 2) as $s) {
            $why[] = lcfirst($s['reason']);
        }
        if ($scored) {
            $a = $tracks[self::AGENCY]['score'];
            $i = $tracks[self::INDIVIDUAL]['score'];
            $why[] = 'fit agency ' . ($a === null ? 'n/a' : self::num($a)) . ' vs individual ' . ($i === null ? 'n/a' : self::num($i));
        }

        return $lead . ($why ? ': ' . implode('; ', $why) : '') . '.';
    }

    private static function num($v): string
    {
        return rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    }
}
