<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Services\Upwork\Scoring\ScoringEngine;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * Phase 6: heuristic win probability and expected value - explainable,
 * clearly labelled as estimates, configurable and versioned.
 */
class UpworkOutlookTest extends UpworkTestCase
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
            'description' => 'Build features for our Laravel SaaS platform: React dashboard and API integration.',
            'job_type' => 'hourly', 'hourly_min' => 40, 'hourly_max' => 60,
            'duration' => '3 to 6 months', 'workload' => 'part_time', 'contractor_type' => 'All freelancers',
            'client_rating' => 4.9, 'client_reviews_count' => 30, 'client_total_spent' => 80000, 'client_hire_rate' => 80,
            'client_jobs_posted' => 40, 'client_jobs_hired' => 32, 'payment_verified' => true,
            'proposals_tier' => '10 to 15',
        ], $overrides);
    }

    private function outlook(array $job, ?UpworkRuleset $ruleset = null): array
    {
        return (new ScoringEngine())->score($job, ($ruleset ?? UpworkRuleset::current())->load('rules'))['outlook'];
    }

    private function publishSettings(array $settings): UpworkRuleset
    {
        $draft = UpworkRuleset::draftOrCreate();
        $draft->update(['settings' => array_merge($draft->settings, $settings)]);
        $draft->publish(null);

        return $draft->fresh();
    }

    // ---- Win probability ------------------------------------------------------------

    public function test_win_probability_is_a_labelled_explainable_heuristic(): void
    {
        $wp = $this->outlook($this->job())['win_probability'];

        $this->assertSame('heuristic-v1', $wp['model']);
        $this->assertStringContainsString('not a trained model', $wp['note']);
        $this->assertGreaterThanOrEqual(1, $wp['value']);
        $this->assertLessThanOrEqual(50, $wp['value']);
        $this->assertNull($wp['clamped']);
        $this->assertEqualsWithDelta(array_sum(array_column($wp['contributions'], 'value')), $wp['value'], 0.05, 'contributions add up to the estimate');
        $this->assertContains('Base rate', array_column($wp['contributions'], 'label'));
    }

    public function test_win_probability_moves_in_the_expected_direction(): void
    {
        $p = fn(array $o) => $this->outlook($this->job($o))['win_probability']['value'];
        $base = $p([]);

        $this->assertGreaterThan($base, $p(['proposals_tier' => 'Fewer than 5']), 'less competition');
        $this->assertLessThan($base, $p(['proposals_tier' => '50+']), 'more competition');
        $this->assertLessThan($base, $p(['job_activity' => ['totalInvitedToInterview' => 5]]), 'client already interviewing');
        $this->assertLessThan($base, $p(['hourly_max' => 20, 'hourly_min' => 15]), 'rate below our minimum');
        $this->assertLessThan($base, $p(['title' => 'WooCommerce shop tweaks', 'description' => 'Small WooCommerce changes.']), 'weaker fit');
        $this->assertGreaterThan($base, $p(['agent_signals' => ['previous_client' => true]]), 'Upwork previous client');
        $this->assertGreaterThan($base, $p(['agent_signals' => ['project_clarity' => 1.0]]), 'clear project');
        $this->assertLessThan($base, $p(['agent_signals' => ['project_clarity' => 0.0]]), 'unclear project');
    }

    public function test_win_probability_is_clamped_and_says_so(): void
    {
        $ruleset = $this->publishSettings(['win_probability' => ['base' => 0.9]]);
        $wp = $this->outlook($this->job(), $ruleset)['win_probability'];

        $this->assertSame(50.0, (float) $wp['value']);
        $this->assertSame('clamped to 1–50%', $wp['clamped']);
    }

    public function test_coefficients_are_configurable_per_ruleset_version(): void
    {
        $before = $this->outlook($this->job())['win_probability']['value'];
        $ruleset = $this->publishSettings(['win_probability' => ['technical_fit' => 0.2]]);

        $this->assertGreaterThan($before, $this->outlook($this->job(), $ruleset)['win_probability']['value']);
    }

    // ---- Expected value ------------------------------------------------------------------

    public function test_hourly_expected_value_uses_stated_assumptions(): void
    {
        $outlook = $this->outlook($this->job());
        $value = $outlook['value'];
        $p = $outlook['win_probability']['value'] / 100;

        // midpoint $50/hr x 15 hrs/week (part-time) x 18 weeks ("3 to 6 months")
        $this->assertEqualsWithDelta(50 * 15 * 18, $value['contract_value'], 0.01);
        $this->assertEqualsWithDelta(50 * 15 * 18 * $p, $value['expected_value'], 0.05);
        $this->assertSame([
            'Rate $50/hr (midpoint of posted range)',
            '15 hrs/week (assumed for part-time)',
            '~18 weeks (midpoint of "3 to 6 months")',
        ], $value['assumptions']);
    }

    public function test_hours_per_week_assumptions_are_configurable(): void
    {
        $ruleset = $this->publishSettings(['ev_hours_per_week' => ['full_time' => 40]]);
        $value = $this->outlook($this->job(['workload' => 'full_time']), $ruleset)['value'];

        $this->assertEqualsWithDelta(50 * 40 * 18, $value['contract_value'], 0.01);
    }

    public function test_fixed_expected_value_is_budget_times_probability(): void
    {
        $outlook = $this->outlook($this->job(['job_type' => 'fixed', 'fixed_budget' => 5000, 'hourly_min' => null, 'hourly_max' => null]));

        $this->assertEqualsWithDelta(5000, $outlook['value']['contract_value'], 0.01);
        $this->assertEqualsWithDelta(5000 * $outlook['win_probability']['value'] / 100, $outlook['value']['expected_value'], 0.05);
    }

    public function test_no_number_is_invented_when_inputs_are_missing(): void
    {
        $noRate = $this->outlook($this->job(['hourly_min' => null, 'hourly_max' => null]))['value'];
        $this->assertNull($noRate['expected_value']);
        $this->assertSame('Hourly rate not stated.', $noRate['reason']);

        $noDuration = $this->outlook($this->job(['duration' => null]))['value'];
        $this->assertNull($noDuration['contract_value']);
        $this->assertSame('Duration not stated.', $noDuration['reason']);

        $noBudget = $this->outlook($this->job(['job_type' => 'fixed', 'fixed_budget' => null, 'hourly_min' => null, 'hourly_max' => null]))['value'];
        $this->assertNull($noBudget['expected_value']);

        $rejected = (new ScoringEngine())->score($this->job(['contractor_type' => 'Individuals only']), UpworkRuleset::current()->load('rules'));
        $this->assertNull($rejected['outlook']);
    }

    public function test_rules_can_use_the_estimates(): void
    {
        $draft = UpworkRuleset::draftOrCreate();
        $draft->rules()->create(['key' => 'class_high_ev', 'name' => 'High expected value', 'type' => 'classification', 'classification' => 'HIGH_VALUE',
            'conditions' => ['field' => 'scores.expected_value', 'op' => 'gte', 'value' => 1000]]);
        $draft->publish(null);

        $result = (new ScoringEngine())->score($this->job(), $draft->fresh()->load('rules'));
        $this->assertContains('HIGH_VALUE', $result['classifications']);
        $this->assertSame('expected value ($) ≥ 1000', collect($result['classification_details']['HIGH_VALUE'])->firstWhere('rule', 'class_high_ev')['why']);
    }

    // ---- Stored, versioned, shown -------------------------------------------------------------

    public function test_estimates_are_stored_versioned_and_shown_as_estimates(): void
    {
        $token = $this->bde()->createToken('agent', ['upwork:ingest'])->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
        $url = ['upwork_url' => 'https://www.upwork.com/jobs/~02x'];

        $this->postJson('/api/upwork/opportunities', $url + ['upwork_job_id' => '~02big'] + $this->job(['workload' => 'full_time']), $headers)->assertStatus(201);
        $this->postJson('/api/upwork/opportunities', $url + ['upwork_job_id' => '~02small', 'title' => 'Small Laravel job'] + $this->job(['duration' => 'Less than 1 month']), $headers)->assertStatus(201);
        $this->postJson('/api/upwork/opportunities', $url + ['upwork_job_id' => '~02norate', 'title' => 'Laravel job without rate'] + $this->job(['hourly_min' => null, 'hourly_max' => null]), $headers)->assertStatus(201);

        $big = UpworkOpportunity::where('upwork_job_id', '02big')->first();
        $this->assertSame($big->win_probability, $big->score_explanation['outlook']['win_probability']['value']);
        $this->assertSame($big->expected_value, $big->score_explanation['outlook']['value']['expected_value']);
        $this->assertNotNull($big->contract_value_estimate);

        // Publishing new coefficients does not reinterpret stored estimates.
        $stored = $big->win_probability;
        $this->publishSettings(['win_probability' => ['base' => 0.2]]);
        $this->assertSame($stored, $big->fresh()->win_probability);

        $user = $this->bde();
        $this->actingAs($user)->get(route('upwork.opportunities.show', $big))
            ->assertOk()
            ->assertSee('Commercial outlook')->assertSee('ESTIMATE')
            ->assertSee('heuristic-v1')->assertSee('Heuristic estimate, not a trained model.')
            ->assertSee('How it was estimated')
            ->assertSee('35 hrs/week (assumed for full-time)');

        $this->actingAs($user)->get(route('upwork.opportunities.show', UpworkOpportunity::where('upwork_job_id', '02norate')->first()))
            ->assertOk()->assertSee('No value estimate: Hourly rate not stated.');

        $html = $this->actingAs($user)->get(route('upwork.opportunities.index', ['sort' => 'expected_value']))->assertOk()->assertSee('(est.)')->getContent();
        $this->assertLessThan(strpos($html, 'Small Laravel job'), strpos($html, 'Laravel SaaS platform'), 'sorted by expected value, highest first');
        $this->assertLessThan(strpos($html, 'Laravel job without rate'), strpos($html, 'Small Laravel job'), 'unknown expected value last');
    }
}
