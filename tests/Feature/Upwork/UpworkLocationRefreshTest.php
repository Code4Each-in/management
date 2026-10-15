<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Api\McpUpworkClient;
use App\Services\Upwork\Eligibility\LocationEligibility;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Support\Facades\Artisan;

/**
 * Facts Upwork gives only in full job details: the location list
 * (preferred_locations) and invites sent (activityStat.jobActivity.invitesSent).
 * They are kept when details arrive, and stored jobs can have them re-read.
 * Only read-only find_jobs get calls are made, and only with --fetch.
 */
class UpworkLocationRefreshTest extends UpworkTestCase
{
    use FakesUpworkMcp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mcpConnect();
        UpworkSetting::set('discovery.client', 'mcp');
        UpworkSetting::set('notifications.channels', ['log']);
        $this->mcpGetFixture = 'mcp_get_preferred_locations';
        $this->fakeUpworkMcp();
    }

    public function test_job_details_keep_the_location_list_and_classify_it(): void
    {
        $row = McpUpworkClient::mapJobDetail($this->mcpFixture('mcp_get_preferred_locations'));
        $this->assertSame(['countries' => ['Philippines', 'Argentina', 'Spain'], 'location_required' => false], $row['raw_data']['preferred_locations']);
        // No location block on the posting: stored as [] ("fetched, none given").
        $this->assertSame([], McpUpworkClient::mapJobDetail($this->mcpFixture('mcp_get'))['raw_data']['preferred_locations']);

        [$o] = app(UpworkOpportunityService::class)->ingest($row + ['score' => 86, 'recommendation' => 'apply']);
        $o->refresh();
        $this->assertSame('Philippines, Argentina, Spain (preferred, not required)', $o->location_text);
        $this->assertSame(['unknown', 'location_preferred', 'preferred_locations'], [$o->location_eligibility, $o->location_rule, $o->location_source]);
        $this->assertSame('apply', $o->recommendation, 'a preference is flagged, not skipped');
        $this->assertSame(4, $o->invites_sent);

        $this->actingAs($this->bde())->get(route('upwork.opportunities.index'))->assertOk()
            ->assertSee('Philippines, Argentina, Spain (preferred, not required)')->assertDontSee('no location restriction given');

        // The same posting with the list marked required is skipped.
        $required = $row;
        $required['upwork_job_id'] = '~01required';
        $required['raw_data']['preferred_locations']['location_required'] = true;
        [$r] = app(UpworkOpportunityService::class)->ingest($required + ['score' => 86, 'recommendation' => 'apply']);
        $this->assertSame(['restricted', 'skip'], [$r->fresh()->location_eligibility, $r->fresh()->recommendation]);
    }

    public function test_refresh_lists_without_calling_upwork_then_fetches_only_location_fields(): void
    {
        // Stored before the location list was kept: enriched, no preferred_locations, classified "blank".
        $old = $this->opportunity(['title' => 'Old job', 'recommendation' => 'apply', 'score' => 86, 'upwork_job_id' => '2000000000000000001',
            'raw_data' => ['source' => 'upwork-mcp', 'preferred_qualifications' => ['english_proficiency' => 'Any']]]);
        $old->forceFill(['stage' => UpworkOpportunity::STAGE_ENRICHED])->save();
        app(UpworkOpportunityService::class)->classifyLocation($old, true);
        $this->assertSame('blank', $old->fresh()->location_rule);
        $archived = $this->opportunity(['status' => 'archived', 'raw_data' => ['source' => 'upwork-mcp']]);
        $archived->forceFill(['stage' => UpworkOpportunity::STAGE_ENRICHED])->save();
        $before = $old->fresh()->only(['score', 'status', 'title', 'review_due_at', 'first_seen_at']);

        Artisan::call('upwork:refresh-job-details');
        $out = Artisan::output();
        $this->assertSame([], $this->mcpCalls, 'listing makes no Upwork call');
        $this->assertStringContainsString('Old job', $out);
        $this->assertStringNotContainsString((string) $archived->upwork_job_id, $out, 'archived jobs are left alone');
        $this->assertStringContainsString('nothing was fetched', $out);

        Artisan::call('upwork:refresh-job-details', ['--fetch' => true, '--pause' => 0]);
        $this->assertSame(['find_jobs:get'], $this->mcpToolNames(), 'one read-only get, no search');

        $old->refresh();
        $this->assertSame(4, $old->invites_sent, 'invites sent read from the job details');
        $this->assertSame(['unknown', 'location_preferred'], [$old->location_eligibility, $old->location_rule]);
        $this->assertSame(['Philippines', 'Argentina', 'Spain'], $old->raw_data['preferred_locations']['countries']);
        $this->assertSame('upwork-mcp', $old->raw_data['source'], 'other raw data kept');
        $this->assertEquals($before, $old->only(array_keys($before)), 'score, status and dates untouched');

        // Done once: a second run has nothing to fetch.
        Artisan::call('upwork:refresh-job-details', ['--fetch' => true, '--pause' => 0]);
        $this->assertStringContainsString('No jobs need their details re-read', Artisan::output());
        $this->assertCount(1, $this->mcpCalls);
    }

    public function test_refresh_reads_invites_and_rescores_once_without_sending_anything(): void
    {
        (new \Database\Seeders\UpworkIntelligenceSeeder)->run();
        $row = McpUpworkClient::mapJobDetail($this->mcpFixture('mcp_get'));
        unset($row['job_activity']); // enriched before Upwork recorded any invites
        [$o] = app(UpworkOpportunityService::class)->ingest($row);
        $o->refresh();
        $this->assertNull($o->invites_sent);
        $base = $o->score;
        $notified = $o->notified_at;
        \Illuminate\Support\Facades\Log::spy();

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))->assertOk()
            ->assertSeeInOrder(['Invites sent', 'Not available', 'upwork:refresh-job-details']);

        Artisan::call('upwork:refresh-job-details', ['--fetch' => true, '--pause' => 0, '--id' => [$o->id]]);
        $out = Artisan::output();

        $o->refresh();
        $this->assertSame(4, $o->invites_sent);
        $this->assertEquals(5, $o->score_explanation['invites']['penalty']);
        $this->assertEquals(round($base - 5, 2), $o->score, 'penalty applied once to the fresh base');
        $this->assertStringContainsString('n/a -> 4', $out);
        $this->assertEquals($notified, $o->notified_at);
        \Illuminate\Support\Facades\Log::shouldNotHaveReceived('info', [\Mockery::pattern('/Upwork opportunity/'), \Mockery::any()]);

        // Re-reading again changes nothing (no second subtraction).
        Artisan::call('upwork:refresh-job-details', ['--fetch' => true, '--pause' => 0, '--id' => [$o->id]]);
        $this->assertEquals(round($base - 5, 2), $o->fresh()->score);

        $bde = $this->bde();
        $this->actingAs($bde)->get(route('upwork.opportunities.show', $o))->assertOk()
            ->assertSeeInOrder(['Invites sent', '4', '−5 score']);
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['show' => 'all']))->assertOk()
            ->assertSee('Invites sent: <strong>4</strong>', false);
    }
}
