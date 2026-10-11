<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkClassification;
use App\Models\UpworkOpportunity;
use App\Models\UpworkRule;
use App\Models\UpworkRuleset;
use App\Services\Upwork\Scoring\ConditionDescriber;
use App\Services\Upwork\Scoring\ScoringContext;
use App\Services\Upwork\Scoring\ScoringEngine;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * Phase 5: classification catalog, archetype detection (quick win, strategic,
 * long-term, AI automation, ...), Strategic Value, plain-English reasons and
 * the classification filter.
 */
class UpworkClassificationTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
    }

    private function job(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Laravel SaaS platform',
            'description' => 'Build features for our Laravel SaaS platform: React dashboard, Stripe billing, API integration.',
            'job_type' => 'hourly', 'hourly_min' => 35, 'hourly_max' => 60,
            'duration' => 'More than 6 months', 'workload' => 'full_time', 'contractor_type' => 'All freelancers',
            'client_rating' => 4.9, 'client_reviews_count' => 30, 'client_total_spent' => 80000, 'client_hire_rate' => 80,
            'client_jobs_posted' => 40, 'client_jobs_hired' => 32, 'payment_verified' => true,
            'proposals_tier' => '5 to 10',
        ], $overrides);
    }

    private function quickWin(array $overrides = []): array
    {
        return $this->job(array_merge([
            'title' => 'Laravel API fix + Stripe webhook',
            'description' => 'Fix one Laravel API endpoint and the Stripe webhook handler. React admin needs one small change.',
            'job_type' => 'fixed', 'fixed_budget' => 900, 'hourly_min' => null, 'hourly_max' => null,
            'duration' => 'Less than 1 month', 'workload' => null,
        ], $overrides));
    }

    private function score(array $job, ?UpworkRuleset $ruleset = null): array
    {
        return (new ScoringEngine())->score($job, ($ruleset ?? UpworkRuleset::current())->load('rules'));
    }

    private function publishChange(callable $change): UpworkRuleset
    {
        $draft = UpworkRuleset::draftOrCreate();
        $change($draft);
        $draft->publish(null);

        return $draft->fresh();
    }

    // ---- Catalog --------------------------------------------------------------------

    public function test_catalog_covers_every_seeded_classification_rule(): void
    {
        $codes = UpworkClassification::pluck('code')->all();
        $used = UpworkRuleset::current()->rules->where('type', 'classification')->pluck('classification')->unique()->all();

        $this->assertSame([], array_values(array_diff($used, $codes)));
        $this->assertSame('archetype', UpworkClassification::where('code', 'QUICK_WIN')->value('kind'));
        $this->assertSame('category', UpworkClassification::where('code', 'LARAVEL')->value('kind'));
    }

    public function test_publishing_rejects_labels_missing_from_the_catalog(): void
    {
        $draft = UpworkRuleset::draftOrCreate();
        $draft->rules()->create(['key' => 'typo', 'name' => 'Typo', 'type' => 'classification', 'classification' => 'QUIK_WIN',
            'conditions' => ['op' => 'has_any_tech', 'value' => ['laravel']]]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("'QUIK_WIN' is not in the classification catalog");
        $draft->publish(null);
    }

    public function test_catalog_codes_are_validated(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UpworkClassification::create(['code' => 'bad code!', 'label' => 'Bad', 'kind' => 'archetype']);
    }

    // ---- Reasons ----------------------------------------------------------------------

    public function test_conditions_are_described_in_plain_english(): void
    {
        $describer = new ConditionDescriber(['saas' => 'SaaS', 'crm' => 'CRM'], ['strategic_min' => 65]);

        $this->assertSame(
            'strategic value ≥ strategic_min (65) AND technical fit ≥ 50% AND mentions SaaS or CRM',
            $describer->describe(['all' => [
                ['field' => 'scores.strategic', 'op' => 'gte', 'value' => ['setting' => 'strategic_min']],
                ['field' => 'scores.technical_fit', 'op' => 'gte', 'value' => 50],
                ['op' => 'has_any_tech', 'value' => ['saas', 'crm']],
            ]])
        );
        $this->assertSame('NOT (has a PROVEN technology OR has a ADJACENT technology)', $describer->describe(['not' => ['any' => [
            ['op' => 'has_tier', 'value' => 'proven'], ['op' => 'has_tier', 'value' => 'adjacent'],
        ]]]));
    }

    public function test_every_classification_records_its_rule_and_reason(): void
    {
        $result = $this->score($this->job());

        foreach ($result['classifications'] as $code) {
            $this->assertNotEmpty($result['classification_details'][$code], "no reason for {$code}");
            $this->assertNotEmpty($result['classification_details'][$code][0]['why']);
        }
        $this->assertSame('strategic value ≥ strategic_min (65) AND duration (weeks) ≥ strategic_min_weeks (12) AND technical fit ≥ 50%', $result['classification_details']['STRATEGIC'][0]['why']);
    }

    // ---- Strategic value ----------------------------------------------------------------------

    public function test_strategic_value_is_separate_from_the_opportunity_score(): void
    {
        $long = $this->score($this->job());
        $short = $this->score($this->quickWin());

        $this->assertGreaterThanOrEqual(80, $long['strategic']['score']);
        $this->assertLessThan(50, $short['strategic']['score']);
        $this->assertEqualsWithDelta(array_sum(array_column($long['strategic']['parts'], 'points')), $long['strategic']['score'], 0.05);
        $this->assertNotEquals($long['score'], $long['strategic']['score']);
        $this->assertContains('STRATEGIC', $long['classifications']);
        $this->assertNotContains('STRATEGIC', $short['classifications']);

        // A strong client and proven platform work alone do not make a short job strategic.
        $shortStrong = $this->score($this->job(['duration' => 'Less than 1 month', 'workload' => 'part_time']));
        $this->assertNotContains('STRATEGIC', $shortStrong['classifications']);
    }

    public function test_strategic_weights_and_threshold_are_configurable(): void
    {
        $ruleset = $this->publishChange(fn($d) => $d->update(['settings' => array_merge($d->settings, [
            'strategic_weights' => ['engagement' => 0, 'client' => 0, 'repeat' => 0, 'platform' => 0, 'fit' => 50],
            'strategic_min' => 99,
        ])]));
        $result = $this->score($this->job(), $ruleset);

        $this->assertEqualsWithDelta(100, $result['strategic']['parts']['fit']['max'], 0.01, 'weights normalize to 100');
        $this->assertNotContains('STRATEGIC', $result['classifications']);
    }

    // ---- Quick wins ----------------------------------------------------------------------

    public function test_small_jobs_are_quick_wins_not_rejections(): void
    {
        $result = $this->score($this->quickWin());

        $this->assertSame([], $result['hard_filters']);
        $this->assertNotNull($result['score']);
        $this->assertContains('QUICK_WIN', $result['classifications']);
        $this->assertSame(['size' => 'small', 'source' => 'inferred'], $result['scope'], 'scope inferred from duration/budget');
    }

    public function test_agent_scope_signal_wins_over_inference(): void
    {
        $result = $this->score($this->quickWin(['agent_signals' => ['scope_size' => 'large']]));

        $this->assertSame(['size' => 'large', 'source' => 'agent'], $result['scope']);
        $this->assertNotContains('QUICK_WIN', $result['classifications']);
    }

    public function test_unclear_or_crowded_small_jobs_are_not_quick_wins(): void
    {
        $this->assertNotContains('QUICK_WIN', $this->score($this->quickWin(['agent_signals' => ['project_clarity' => 0.3]]))['classifications'], 'unclear deliverable');
        $this->assertNotContains('QUICK_WIN', $this->score($this->quickWin(['proposals_tier' => '20 to 50']))['classifications'], 'too much competition');
        $this->assertNotContains('QUICK_WIN', $this->score($this->quickWin(['fixed_budget' => 300]))['classifications'], 'budget too small');
        $this->assertNotContains('QUICK_WIN', $this->score($this->quickWin(['client_hire_rate' => 5, 'client_total_spent' => 150, 'client_jobs_hired' => 1]))['classifications'], 'weak client');
        $this->assertContains('QUICK_WIN', $this->score($this->quickWin(['agent_signals' => ['project_clarity' => 0.9]]))['classifications'], 'clear deliverable');
    }

    public function test_scope_inference_edges(): void
    {
        $this->assertSame('small', ScoringContext::inferScope(['duration' => 'Less than 1 month']));
        $this->assertSame('medium', ScoringContext::inferScope(['duration' => 'Less than 1 month', 'workload' => 'full_time']));
        $this->assertSame('small', ScoringContext::inferScope(['job_type' => 'fixed', 'fixed_budget' => 2500]));
        $this->assertNull(ScoringContext::inferScope(['job_type' => 'fixed', 'fixed_budget' => 12000]));
        $this->assertSame('large', ScoringContext::inferScope(['duration' => '3 to 6 months']));
        $this->assertSame('large', ScoringContext::inferScope(['duration' => '1 to 3 months', 'workload' => 'full_time']));
        $this->assertSame('medium', ScoringContext::inferScope(['duration' => '1 to 3 months']));
        $this->assertNull(ScoringContext::inferScope([]));
    }

    // ---- Other archetypes / edge cases --------------------------------------------------------

    public function test_a_job_can_carry_several_archetypes_and_categories(): void
    {
        $result = $this->score($this->job([
            'title' => 'Modernize our legacy PHP CRM with OpenAI automation',
            'description' => 'Modernize our legacy PHP CRM, add a React dashboard, integrate HubSpot via API, and automate follow-ups with OpenAI.',
            'client_total_spent' => 150000,
            // "integrate HubSpot via API" is free-form; the agent's semantic analysis supplies the catalog slug.
            'agent_signals' => ['technologies' => ['api-integration']],
        ]));

        foreach (['LEGACY_MODERNIZATION', 'AI_AUTOMATION', 'API_INTEGRATION', 'LONG_TERM', 'ENTERPRISE', 'CRM', 'REACT'] as $code) {
            $this->assertContains($code, $result['classifications'], "missing {$code}");
        }
    }

    public function test_unknown_duration_and_hard_rejects_make_no_claims(): void
    {
        $unknown = $this->score($this->job(['duration' => null, 'workload' => null]));
        $this->assertNotContains('LONG_TERM', $unknown['classifications']);
        $this->assertSame(['size' => null, 'source' => null], $unknown['scope']);

        $rejected = $this->score($this->job(['contractor_type' => 'Individuals only']));
        $this->assertSame([], $rejected['classifications']);
        $this->assertNull($rejected['strategic']);
    }

    public function test_disabled_classification_rule_is_ignored(): void
    {
        $ruleset = $this->publishChange(fn($d) => $d->rules()->where('key', 'class_long_term')->first()->update(['enabled' => false]));

        $this->assertNotContains('LONG_TERM', $this->score($this->job(), $ruleset)['classifications']);
    }

    // ---- Stored + UI ----------------------------------------------------------------------------

    public function test_classifications_strategic_value_and_reasons_are_stored_and_shown(): void
    {
        $token = $this->bde()->createToken('agent', ['upwork:ingest'])->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
        $base = ['upwork_url' => 'https://www.upwork.com/jobs/~02x'];

        $this->postJson('/api/upwork/opportunities', $base + ['upwork_job_id' => '~02strat'] + $this->job(), $headers)->assertStatus(201);
        $this->postJson('/api/upwork/opportunities', $base + ['upwork_job_id' => '~02quick'] + $this->quickWin(), $headers)->assertStatus(201);

        $strategic = UpworkOpportunity::where('upwork_job_id', '02strat')->first();
        $this->assertGreaterThanOrEqual(80, $strategic->strategic_score);
        $this->assertSame($strategic->strategic_score, $strategic->score_explanation['strategic']['score']);
        $this->assertNotEmpty($strategic->score_explanation['classifications']['STRATEGIC']);

        $user = $this->bde();
        $this->actingAs($user)->get(route('upwork.opportunities.show', $strategic))
            ->assertOk()
            ->assertSee('Strategic value')
            ->assertSee('Repeat-hire potential')
            ->assertSee('Why these classifications')
            ->assertSee('strategic value ≥ strategic_min (65)', false);

        $quick = UpworkOpportunity::where('upwork_job_id', '02quick')->first();
        $this->assertSame('skip', $quick->recommendation, 'a quick win can score below REVIEW...');
        $this->actingAs($user)->get(route('upwork.opportunities.index'))
            ->assertOk()->assertSee('Laravel API fix + Stripe webhook');  // ...and still stays in the default list
        $this->actingAs($user)->get(route('upwork.opportunities.show', $quick))
            ->assertOk()->assertSee('Quick Win')->assertSee('was inferred from duration and budget', false);

        $this->actingAs($user)->get(route('upwork.opportunities.index', ['class' => 'QUICK_WIN']))
            ->assertOk()->assertSee('Laravel API fix + Stripe webhook')->assertDontSee('Laravel SaaS platform');
        $this->actingAs($user)->get(route('upwork.opportunities.index', ['class' => 'STRATEGIC']))
            ->assertOk()->assertSee('Laravel SaaS platform')->assertDontSee('Laravel API fix + Stripe webhook');
        // An invalid filter value is dropped, not injected.
        $this->actingAs($user)->get(route('upwork.opportunities.index', ['class' => "X' OR 1=1 --"]))
            ->assertOk()->assertSee('Laravel SaaS platform')->assertSee('Laravel API fix + Stripe webhook');
    }
}
