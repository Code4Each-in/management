<?php

namespace Tests\Unit;

use App\Models\UpworkOpportunity;
use Tests\TestCase;

class UpworkOpportunityTest extends TestCase
{
    public function test_recommendation_thresholds(): void
    {
        $this->assertSame('apply', UpworkOpportunity::recommendationForScore(80));
        $this->assertSame('apply', UpworkOpportunity::recommendationForScore(97.5));
        $this->assertSame('review', UpworkOpportunity::recommendationForScore(79.99));
        $this->assertSame('review', UpworkOpportunity::recommendationForScore(70));
        $this->assertSame('skip', UpworkOpportunity::recommendationForScore(69.9));
        $this->assertNull(UpworkOpportunity::recommendationForScore(null));
    }

    public function test_thresholds_are_configurable(): void
    {
        config(['upwork.thresholds.apply' => 90]);

        $this->assertSame('review', UpworkOpportunity::recommendationForScore(85));
    }

    public function test_transitions(): void
    {
        $o = new UpworkOpportunity();
        $this->assertSame('new', $o->status);
        $this->assertTrue($o->canTransitionTo('approved'));
        $this->assertTrue($o->canTransitionTo('rejected'));
        $this->assertFalse($o->canTransitionTo('submitted'));
        $this->assertFalse($o->canTransitionTo('won'));
    }

    public function test_budget_label(): void
    {
        $this->assertSame('$40–$70/hr', (new UpworkOpportunity(['job_type' => 'hourly', 'hourly_min' => 40, 'hourly_max' => 70]))->budget_label);
        $this->assertSame('$5,000 fixed', (new UpworkOpportunity(['job_type' => 'fixed', 'fixed_budget' => 5000]))->budget_label);
        $this->assertSame('—', (new UpworkOpportunity())->budget_label);
    }

    public function test_money_format(): void
    {
        $this->assertSame('—', UpworkOpportunity::money(null));
        $this->assertSame('$950', UpworkOpportunity::money(950));
        $this->assertSame('$12.5K', UpworkOpportunity::money(12500));
        $this->assertSame('$1.2M', UpworkOpportunity::money(1200000));
    }
}
