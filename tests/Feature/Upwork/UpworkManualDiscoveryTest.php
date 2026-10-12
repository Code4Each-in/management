<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkConfigLog;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

/**
 * "Run discovery now" on the configuration page: Super Admin only, one manual
 * read-only run after the response is sent, outcome kept for the page.
 */
class UpworkManualDiscoveryTest extends UpworkTestCase
{
    use FakesUpworkMcp;

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Http::swap(new Factory());

        UpworkSetting::set('discovery.client', 'mcp');
        UpworkSetting::set('discovery.api_pause_ms', 0);
        UpworkSetting::set('discovery.max_queries_per_run', 2);
        UpworkSetting::set('agent.enrich_limit_per_run', 1);
    }

    public function test_super_admin_runs_one_read_only_manual_discovery(): void
    {
        $admin = $this->superAdmin();
        $this->mcpConnect();
        $this->fakeUpworkMcp();
        $this->assertFalse((bool) UpworkSetting::get('agent.enabled'), 'works while scheduled discovery is off');

        $this->actingAs($admin)->get(route('upwork.config.index'))->assertOk()->assertSee('Run discovery now');

        $this->actingAs($admin)->post(route('upwork.config.discovery.run'))
            ->assertRedirect(route('upwork.config.index'))->assertSessionHas('success');

        $run = UpworkDiscoveryRun::latest('id')->first();
        $this->assertSame(['completed', 'manual', 'server:upwork-mcp'], [$run->status, $run->trigger, $run->agent_host]);
        $this->assertGreaterThan(0, UpworkOpportunity::count());
        $this->assertEmpty(array_diff($this->mcpToolNames(), ['find_jobs:search', 'find_jobs:get']), 'only read-only calls');

        $last = UpworkSetting::get('discovery.manual_last');
        $this->assertSame(['completed', $run->uuid, $admin->id], [$last['status'], $last['run'], $last['by']]);
        $this->assertTrue(UpworkConfigLog::where('label', 'Upwork discovery')->where('action', 'manual run started')->where('user_id', $admin->id)->exists());

        $this->actingAs($admin)->get(route('upwork.config.index'))->assertSee('Manual run')->assertSee('completed');
    }

    public function test_a_skipped_manual_run_is_reported_on_the_page(): void
    {
        $admin = $this->superAdmin();
        Http::fake();

        $this->actingAs($admin)->post(route('upwork.config.discovery.run'))->assertSessionHas('success');

        $this->assertSame(0, UpworkDiscoveryRun::count());
        Http::assertNothingSent();
        $this->assertSame('skipped', UpworkSetting::get('discovery.manual_last')['status']);
        $this->actingAs($admin)->get(route('upwork.config.index'))->assertSee('is not connected');
    }

    public function test_only_super_admin_can_run_discovery(): void
    {
        $this->mcpConnect();
        Http::fake();
        $bde = $this->bde();

        $this->actingAs($bde)->get(route('upwork.config.index'))->assertOk()->assertDontSee('Run discovery now');
        $this->actingAs($bde)->post(route('upwork.config.discovery.run'))->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame(0, UpworkDiscoveryRun::count());
        $this->assertNull(UpworkSetting::get('discovery.manual_last'));
    }
}
