<?php

namespace Tests\Feature\Upwork;

use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * Phase 7: AI hour estimates and a blended win probability, always labelled
 * as estimates and kept next to the heuristic numbers they adjust.
 */
class UpworkAiOutlookTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
    }

    private function outlook(array $attributes, ?array $ai = null): array
    {
        $payload = array_merge($this->payload(), $attributes);
        if ($ai !== null) {
            $payload['ai_signals'] = $ai;
        }

        return app(UpworkOpportunityService::class)->scoreWithCurrentRuleset($payload)['outlook'];
    }

    private function ai(array $overrides = []): array
    {
        return array_merge(['fit_score' => 90, 'confidence' => 1.0, 'recommendation' => 'apply',
            'estimated_hours_min' => 100, 'estimated_hours_max' => 200, 'hours_basis' => 'Dashboard + billing'], $overrides);
    }

    public function test_without_ai_the_heuristic_stands_alone(): void
    {
        $o = $this->outlook([]);

        $this->assertSame('heuristic-v1', $o['win_probability']['model']);
        $this->assertNull($o['win_probability']['blend']);
        $this->assertNull($o['value']['ai_estimate']);
        $this->assertSame('posting', $o['value']['source']);
    }

    public function test_ai_blend_is_weighted_by_confidence_and_recorded(): void
    {
        $plain = $this->outlook([])['win_probability']['value'];
        $full = $this->outlook([], $this->ai())['win_probability'];
        $half = $this->outlook([], $this->ai(['confidence' => 0.5]))['win_probability'];

        $this->assertSame('heuristic-v1+ai', $full['model']);
        $this->assertStringContainsString('Estimate', $full['note']);
        $this->assertSame($plain, $full['blend']['heuristic']);
        $this->assertSame(31.7, $full['blend']['ai_implied']);          // 2% + 0.9 x 33%
        $this->assertSame(0.3, $full['blend']['ai_weight']);
        $this->assertSame(0.15, $half['blend']['ai_weight']);
        $this->assertEqualsWithDelta(0.7 * $plain + 0.3 * 31.7, $full['value'], 0.01);
        $this->assertTrue(abs($half['value'] - $plain) < abs($full['value'] - $plain), 'less confidence, smaller shift');
    }

    public function test_ai_hours_never_override_a_posting_based_value(): void
    {
        $o = $this->outlook([], $this->ai());

        $this->assertSame('posting', $o['value']['source']);
        $this->assertSame(['hours_min' => 100, 'hours_max' => 200], array_intersect_key($o['value']['ai_estimate'], array_flip(['hours_min', 'hours_max'])));
        $this->assertSame(8250.0, $o['value']['ai_estimate']['contract_value']);   // $55/hr x 150 h
        $this->assertStringContainsString('assumption', $o['value']['ai_estimate']['note']);
    }

    public function test_ai_hours_fill_in_when_the_posting_gives_no_duration(): void
    {
        $o = $this->outlook(['duration' => null], $this->ai());

        $this->assertSame('ai_estimate', $o['value']['source']);
        $this->assertSame(8250.0, $o['value']['contract_value']);
        $this->assertContains('AI estimate of 100–200 hours (assumption)', $o['value']['assumptions']);
        $this->assertEqualsWithDelta(8250 * $o['win_probability']['value'] / 100, $o['value']['expected_value'], 0.5); // displayed % is rounded
    }

    public function test_fixed_price_gets_an_effective_hourly_rate(): void
    {
        $o = $this->outlook(['job_type' => 'fixed', 'hourly_min' => null, 'hourly_max' => null, 'fixed_budget' => 3000], $this->ai(['estimated_hours_min' => 40, 'estimated_hours_max' => 80]));

        $this->assertSame(50.0, $o['value']['ai_estimate']['effective_hourly']);
        $this->assertSame(3000.0, $o['value']['contract_value']);
    }

    public function test_unusable_hour_estimates_are_ignored(): void
    {
        $this->assertNull($this->outlook([], $this->ai(['estimated_hours_min' => null]))['value']['ai_estimate']);
        $this->assertNull($this->outlook([], $this->ai(['estimated_hours_min' => 300, 'estimated_hours_max' => 100]))['value']['ai_estimate']);
    }
}
