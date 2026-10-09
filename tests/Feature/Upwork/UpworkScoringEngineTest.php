<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkRuleset;
use App\Models\UpworkTechnology;
use App\Services\Upwork\Scoring\RuleEvaluator;
use App\Services\Upwork\Scoring\ScoringContext;
use App\Services\Upwork\Scoring\ScoringEngine;
use App\Services\Upwork\Scoring\TechnologyMatcher;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * Phase 2: configurable scoring engine (technology detection, rules, hard
 * filters, normalization, thresholds, classification, explanations).
 */
class UpworkScoringEngineTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
    }

    private function job(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Laravel developer for SaaS dashboard',
            'description' => 'Extend our Laravel SaaS platform with a reporting dashboard and Stripe billing.',
            'job_type' => 'hourly', 'hourly_min' => 35, 'hourly_max' => 60,
            'duration' => '3 to 6 months', 'workload' => 'part_time', 'contractor_type' => 'All freelancers',
            'client_rating' => 4.9, 'client_reviews_count' => 30, 'client_total_spent' => 80000, 'client_hire_rate' => 80,
            'client_jobs_posted' => 40, 'client_jobs_hired' => 32, 'payment_verified' => true,
            'proposals_tier' => '5 to 10',
        ], $overrides);
    }

    private function score(array $job, ?UpworkRuleset $ruleset = null): array
    {
        return (new ScoringEngine())->score($job, ($ruleset ?? UpworkRuleset::current())->load('rules'));
    }

    /** Copy the current version to a draft, change it, publish it as the next version. */
    private function publishChange(callable $change): UpworkRuleset
    {
        $draft = UpworkRuleset::draftOrCreate();
        $change($draft);
        $draft->publish(null);

        return $draft->fresh();
    }

    // ---- Technology detection -----------------------------------------------------

    public function test_aliases_match_whole_words_case_insensitively_with_plurals(): void
    {
        $matcher = new TechnologyMatcher();

        $found = $matcher->detect('We use REACTJS, a couple of Webhooks and the ChatGPT API.')['direct'];
        $this->assertArrayHasKey('react', $found);
        $this->assertArrayHasKey('webhooks', $found);
        $this->assertArrayHasKey('openai', $found, 'ChatGPT is an alias of OpenAI');

        $none = $matcher->detect('Make it reactive. We make apps. Brazilian catering.')['direct'];
        $this->assertArrayNotHasKey('react', $none, '"reactive" is not React');
        $this->assertArrayNotHasKey('make', $none, 'the bare word "make" is not Make.com');
        $this->assertArrayNotHasKey('ai', $none, '"ai" inside a word does not match');

        $this->assertArrayHasKey('make', $matcher->detect('Automate it with Make.com scenarios')['direct']);
    }

    public function test_legacy_php_crm_example_is_understood_beyond_literal_keywords(): void
    {
        $tech = (new TechnologyMatcher())->detect('Modernize our legacy PHP CRM and build a React dashboard');

        foreach (['php', 'legacy-modernization', 'crm', 'react', 'dashboard'] as $slug) {
            $this->assertArrayHasKey($slug, $tech['direct'], "missing {$slug}");
        }
        $this->assertArrayHasKey('mysql', $tech['related'], 'graph expansion adds related technologies');
        $this->assertArrayNotHasKey('laravel', $tech['direct'], 'related technologies are never treated as mentioned');
    }

    public function test_agent_slugs_are_added_and_tiers_are_visible(): void
    {
        $tech = (new TechnologyMatcher())->detect('Build a mobile app for our ferry routes.', ['flutter', 'laravel', 'not-a-slug']);

        $this->assertSame('excluded', $tech['direct']['flutter']['tier']);
        $this->assertSame('proven', $tech['direct']['laravel']['tier']);
        $this->assertSame('agent', $tech['direct']['laravel']['source']);
        $this->assertArrayNotHasKey('not-a-slug', $tech['direct'], 'unknown slugs from the agent are ignored');
    }

    public function test_related_technologies_follow_weighted_edges(): void
    {
        $related = (new TechnologyMatcher())->detect('Lumen microservice')['related'];

        $this->assertSame('laravel', $related['php']['via']);
        $this->assertEqualsWithDelta(0.9, $related['php']['weight'], 0.0001);
    }

    public function test_disabled_technologies_are_not_detected(): void
    {
        UpworkTechnology::where('slug', 'laravel')->update(['enabled' => false]);

        $this->assertArrayNotHasKey('laravel', (new TechnologyMatcher())->detect('Laravel app')['direct']);
    }

    // ---- Rule evaluation ------------------------------------------------------------

    public function test_unknown_values_never_satisfy_comparisons(): void
    {
        $e = new RuleEvaluator();
        $ctx = ['client' => ['hire_rate' => null, 'payment_verified' => null], 'settings' => ['min_hourly' => 25], 'job' => ['hourly_max' => 20]];

        $this->assertFalse($e->matches(['field' => 'client.hire_rate', 'op' => 'lt', 'value' => 20], $ctx));
        $this->assertFalse($e->matches(['field' => 'client.hire_rate', 'op' => 'gte', 'value' => 20], $ctx));
        $this->assertFalse($e->matches(['field' => 'client.payment_verified', 'op' => 'is_false'], $ctx), 'unknown is not false');
        $this->assertTrue($e->matches(['field' => 'client.hire_rate', 'op' => 'is_null'], $ctx));
        $this->assertTrue($e->matches(['field' => 'job.hourly_max', 'op' => 'lt', 'value' => ['setting' => 'min_hourly']], $ctx));
    }

    public function test_groups_text_and_technology_operators(): void
    {
        $e = new RuleEvaluator();
        $ctx = [
            'job' => ['text' => 'Looking for a cheap developer for WordPress', 'job_type' => 'Fixed'],
            'tech' => ['wordpress' => ['tier' => 'proven'], 'flutter' => ['tier' => 'excluded']],
        ];

        $this->assertTrue($e->matches(['field' => 'job.text', 'op' => 'contains_any', 'value' => ['Cheap Developer']], $ctx));
        $this->assertTrue($e->matches(['field' => 'job.job_type', 'op' => 'eq', 'value' => 'fixed'], $ctx), 'case-insensitive');
        $this->assertTrue($e->matches(['all' => [['op' => 'has_any_tech', 'value' => ['wordpress']], ['not' => ['op' => 'has_tier', 'value' => 'emerging']]]], $ctx));
        $this->assertTrue($e->matches(['any' => [['op' => 'has_all_tech', 'value' => ['wordpress', 'react']], ['op' => 'tier_count_gte', 'value' => ['tier' => 'excluded', 'count' => 1]]]], $ctx));
        $this->assertFalse($e->matches(['op' => 'has_all_tech', 'value' => ['wordpress', 'react']], $ctx));
    }

    public function test_duration_and_proposal_tiers_are_normalized(): void
    {
        $this->assertSame(2.0, ScoringContext::durationWeeks('Less than 1 month'));
        $this->assertSame(18.0, ScoringContext::durationWeeks('3 to 6 months (full-time)'));
        $this->assertSame(30.0, ScoringContext::durationWeeks('More than 6 months'));
        $this->assertNull(ScoringContext::durationWeeks('unknown'));
        $this->assertSame([50, null], ScoringContext::proposalTierRange('50+'));
        $this->assertSame([20, 50], ScoringContext::proposalTierRange('20 to 50'));
    }

    // ---- Engine ---------------------------------------------------------------------

    public function test_score_is_explained_and_normalized_to_100(): void
    {
        $result = $this->score($this->job());

        $this->assertNull($result['hard_filters'] ?: null);
        $this->assertCount(7, $result['components']);
        $this->assertEqualsWithDelta(array_sum(array_column($result['components'], 'points')), $result['score'], 0.02);
        $this->assertEqualsWithDelta(100, array_sum(array_column($result['components'], 'max')), 0.05);

        $technical = $result['components']['technical_fit'];
        $this->assertSame('Proven technology detected', $technical['baseline_detail']);
        $this->assertContains('tech_laravel', array_column($technical['rules'], 'key'));
        $this->assertLessThanOrEqual($technical['max'], $technical['points']);
        $this->assertSame('engine-1/v1', $result['scoring_version']);
        $this->assertSame(1, $result['ruleset_version']);
    }

    public function test_hard_filters_run_before_scoring_and_are_recorded(): void
    {
        $result = $this->score($this->job([
            'contractor_type' => 'Individuals only',
            'job_activity' => ['totalHired' => 1],
        ]));

        $this->assertNull($result['score']);
        $this->assertSame('skip', $result['recommendation']);
        $this->assertSame([], $result['components'], 'no points are computed for a rejected job');
        $this->assertEqualsCanonicalizing(['already_hired', 'individual_only'], array_column($result['hard_filters'], 'key'));
        foreach ($result['hard_filters'] as $filter) {
            $this->assertNotEmpty($filter['reason']);
            $this->assertSame(1, $filter['ruleset_version']);
            $this->assertNotEmpty($filter['evaluated_at']);
        }
    }

    public function test_individual_only_is_allowed_when_agency_mode_is_off(): void
    {
        $ruleset = $this->publishChange(fn($d) => $d->update(['settings' => array_merge($d->settings, ['agency_mode' => false])]));

        $this->assertNotContains('individual_only', array_column($this->score($this->job(['contractor_type' => 'Individuals only']), $ruleset)['hard_filters'], 'key'));
    }

    public function test_penalties_are_not_absorbed_by_bonuses_above_the_cap(): void
    {
        $job = $this->job(['description' => 'Laravel and React app with API integration plus a Flutter mobile app.']);
        $technical = $this->score($job)['components']['technical_fit'];

        $this->assertSame('max', $technical['capped']);
        $this->assertContains('excluded_tech', array_column($technical['rules'], 'key'));
        $this->assertEqualsWithDelta($technical['max'] - 12, $technical['points'], 0.01);
    }

    public function test_weights_from_the_ruleset_change_the_score_without_code_changes(): void
    {
        $before = $this->score($this->job());

        $ruleset = $this->publishChange(function ($d) {
            $d->update(['weights' => array_merge($d->weights, ['ai_automation' => 0, 'competition' => 25])]);
        });
        $after = $this->score($this->job(), $ruleset);

        $this->assertEqualsWithDelta(0, $after['components']['ai_automation']['max'], 0.001);
        // Weights now total 110, so each is normalized back to a 100-point scale.
        $this->assertEqualsWithDelta(25 * 100 / 110, $after['components']['competition']['max'], 0.01);
        $this->assertEqualsWithDelta(100, array_sum(array_column($after['components'], 'max')), 0.05);
        $this->assertNotEquals($before['score'], $after['score']);
        $this->assertSame(2, $after['ruleset_version']);
    }

    public function test_new_positive_and_negative_rules_apply_without_code_changes(): void
    {
        $job = $this->job(['description' => 'Laravel SaaS dashboard. Must use Kendo UI grids. Payment only via crypto.']);
        $before = $this->score($job)['score'];

        $ruleset = $this->publishChange(function ($d) {
            $d->rules()->create(['key' => 'kendo_bonus', 'name' => 'Kendo UI', 'type' => 'score', 'component' => 'experience_fit', 'points' => 4,
                'reason' => 'Kendo UI is portfolio-proven', 'conditions' => ['op' => 'has_any_tech', 'value' => ['kendo-ui']]]);
            $d->rules()->create(['key' => 'crypto_only', 'name' => 'Crypto-only payment', 'type' => 'score', 'component' => 'client_quality', 'points' => -6,
                'reason' => 'Off-platform payment risk', 'conditions' => ['field' => 'job.text', 'op' => 'contains_any', 'value' => ['crypto']]]);
        });
        $after = $this->score($job, $ruleset);

        $this->assertContains('kendo_bonus', array_column($after['components']['experience_fit']['rules'], 'key'));
        $this->assertContains('crypto_only', array_column($after['components']['client_quality']['rules'], 'key'));
        $this->assertNotEquals($before, $after['score']);
    }

    public function test_disabled_rules_are_ignored(): void
    {
        $ruleset = $this->publishChange(fn($d) => $d->rules()->where('key', 'tech_laravel')->first()->update(['enabled' => false]));

        $this->assertNotContains('tech_laravel', array_column($this->score($this->job(), $ruleset)['components']['technical_fit']['rules'], 'key'));
    }

    public function test_thresholds_come_from_the_ruleset(): void
    {
        $score = $this->score($this->job())['score'];

        $strict = $this->publishChange(fn($d) => $d->update(['thresholds' => ['apply' => 99, 'review' => 98]]));
        $this->assertSame('skip', $this->score($this->job(), $strict)['recommendation']);

        $lenient = $this->publishChange(fn($d) => $d->update(['thresholds' => ['apply' => floor($score), 'review' => floor($score) - 1]]));
        $this->assertSame('apply', $this->score($this->job(), $lenient)['recommendation']);
    }

    public function test_client_quality_score_uses_reported_facts_and_flags_unknowns(): void
    {
        $strong = $this->score($this->job())['client'];
        $unknown = $this->score($this->job([
            'client_rating' => null, 'client_reviews_count' => null, 'client_total_spent' => null, 'client_hire_rate' => null,
            'client_jobs_posted' => null, 'client_jobs_hired' => null, 'payment_verified' => null,
        ]))['client'];
        $weak = $this->score($this->job(['payment_verified' => false, 'client_hire_rate' => 5, 'client_total_spent' => 150, 'client_jobs_hired' => 1]))['client'];

        $this->assertGreaterThan(80, $strong['score']);
        $this->assertSame(50.0, (float) $unknown['score'], 'all-unknown client gets neutral credit');
        $this->assertCount(6, $unknown['unknown']);
        $this->assertLessThan(45, $weak['score']);
        $this->assertSame(0.0, (float) $weak['parts']['payment']);
    }

    public function test_quick_win_and_strategic_classification(): void
    {
        $quickWin = $this->score($this->job([
            'title' => 'Laravel API fix + Stripe webhook',
            'description' => 'Fix a Laravel API endpoint and the Stripe webhook handler.',
            'job_type' => 'fixed', 'fixed_budget' => 800, 'hourly_min' => null, 'hourly_max' => null,
            'duration' => 'Less than 1 month', 'proposals_tier' => '5 to 10',
            'agent_signals' => ['scope_size' => 'small'],
        ]));
        $this->assertContains('QUICK_WIN', $quickWin['classifications']);
        $this->assertNotContains('STRATEGIC', $quickWin['classifications']);

        $strategic = $this->score($this->job(['duration' => 'More than 6 months', 'workload' => 'full_time']));
        $this->assertContains('STRATEGIC', $strategic['classifications']);
        $this->assertContains('LONG_TERM', $strategic['classifications']);
        $this->assertContains('SAAS', $strategic['classifications']);
        $this->assertNotContains('QUICK_WIN', $strategic['classifications']);
    }

    public function test_ai_automation_classification_and_component(): void
    {
        $result = $this->score($this->job([
            'title' => 'n8n + OpenAI workflow automation',
            'description' => 'Connect our CRM to OpenAI via n8n webhooks.',
        ]));

        $this->assertContains('AI_AUTOMATION', $result['classifications']);
        $ai = $result['components']['ai_automation'];
        $this->assertEqualsCanonicalizing(['ai_general', 'ai_openai', 'ai_n8n'], array_values(array_intersect(['ai_general', 'ai_openai', 'ai_n8n'], array_column($ai['rules'], 'key'))));
        $this->assertSame($ai['max'], $ai['points']);
    }
}
