<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkAiUsage;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiBudgetGuard;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\Ai\Providers\FakeProvider;
use App\Services\Upwork\Ai\Qualification\QualificationService;
use App\Services\Upwork\Api\FixtureUpworkClient;
use App\Services\Upwork\Discovery\ServerDiscoveryRunner;
use App\Services\Upwork\MetricsService;
use App\Services\Upwork\RadarService;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Support\Facades\Queue;

/** Phase 9: cost and efficiency metrics on the Radar and in upwork:metrics. */
class UpworkMetricsTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Queue::fake();
    }

    /** Fixture discovery cycle, then AI on the two best jobs; people approve one and reject one. */
    private function scenario(): array
    {
        UpworkSetting::set('agent.enrich_limit_per_run', 6);
        app(ServerDiscoveryRunner::class, ['pause' => function () {}])->run('manual', new FixtureUpworkClient());

        foreach (['enabled' => true, 'provider' => 'fake'] as $k => $v) {
            UpworkSetting::set("ai.{$k}", $v);
        }
        $service = new QualificationService(new AiGateway(new FakeProvider(), new AiBudgetGuard(), function () {}));
        [$a, $b] = UpworkOpportunity::where('stage', 'enriched')->whereNotNull('score')->orderByDesc('score')->limit(2)->get()->all();
        $service->analyze($a);
        $service->analyze($b);

        $opps = app(UpworkOpportunityService::class);
        $opps->approve($a->fresh(), $this->bde()->id);
        $opps->reject($b->fresh(), $this->bde()->id, 'Not for us');

        return [$a->fresh(), $b->fresh()];
    }

    public function test_summary_counts_the_pipeline_and_its_cost(): void
    {
        [$a, $b] = $this->scenario();
        [$from, $to] = RadarService::window('today');

        $m = app(MetricsService::class)->summary($from, $to);

        $this->assertGreaterThan(0, $m['discovery']['searches']);
        $this->assertSame(UpworkOpportunity::count(), $m['discovery']['new_jobs']);
        $this->assertGreaterThan(0, $m['discovery']['detail_lookups']);
        $this->assertSame(2, $m['ai']['analyzed_jobs']);
        $this->assertSame(2, $m['ai']['requests']);
        $this->assertEqualsWithDelta((float) UpworkAiUsage::sum('estimated_cost_usd'), $m['ai']['cost'], 0.000001);
        $this->assertGreaterThan(0, $m['ai']['cost']);
        $this->assertSame([1, 1], [$m['outcomes']['approved'], $m['outcomes']['rejected']]);
        $this->assertSame(UpworkOpportunity::where('score', '>=', 70)->count(), $m['outcomes']['score_70']);

        $ef = $m['efficiency'];
        $this->assertEqualsWithDelta($m['ai']['cost'] / 2, $ef['cost_per_analyzed_job'], 0.0001);
        $this->assertEqualsWithDelta($m['ai']['cost'], $ef['cost_per_approved_job'], 0.0001);
        $this->assertSame(2, $ef['ai_human_compared']);
        $expected = (int) in_array($a->aiAnalysis->recommendation, ['apply', 'review'], true) + (int) ($b->aiAnalysis->recommendation === 'skip');
        $this->assertEquals($expected * 50, $ef['ai_human_agreement_pct']);
        $this->assertLessThanOrEqual(5.0, $m['ai']['monthly_budget']);
    }

    public function test_empty_period_has_no_division_by_zero(): void
    {
        [$from, $to] = RadarService::window('today');
        $m = app(MetricsService::class)->summary($from, $to);

        $this->assertSame(0, $m['discovery']['searches']);
        $this->assertNull($m['efficiency']['cost_per_analyzed_job']);
        $this->assertNull($m['efficiency']['ai_human_agreement_pct']);
        $this->assertNull($m['efficiency']['searches_per_new_job']);
    }

    public function test_radar_and_command_show_the_metrics(): void
    {
        $this->scenario();

        $this->actingAs($this->bde())->get(route('upwork.radar'))->assertOk()
            ->assertSee('AI &amp; efficiency', false)->assertSee('Cost / analysed job')->assertSee('Month to date (UTC)');

        $this->artisan('upwork:metrics', ['--range' => 'today'])->assertExitCode(0);
    }
}
