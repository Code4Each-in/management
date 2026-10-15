<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkDiscoveryQuery;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkOpportunitySighting;
use App\Models\UpworkSearchProfile;
use App\Services\Upwork\RadarService;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Support\Str;

/**
 * Phase 8: Opportunity Radar (database-derived metrics) and list views.
 */
class UpworkRadarTest extends UpworkTestCase
{
    /** @var array<string, UpworkOpportunity> */
    private $o = [];

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        $this->travelTo(now()->setTime(15, 0)); // mid-afternoon, so "today" is unambiguous
    }

    private function make(string $name, array $attributes, ?string $firstSeen = 'now'): UpworkOpportunity
    {
        $o = $this->opportunity(array_merge(['title' => $name, 'score' => null, 'recommendation' => null], $attributes));
        $o->forceFill([
            'first_seen_at' => $firstSeen === null ? null : ($firstSeen === 'now' ? now() : now()->modify($firstSeen)),
            'discovery_ignored' => $attributes['discovery_ignored'] ?? false,
            // Engine-owned fields are not mass-assignable; set them directly.
            'expected_value' => $attributes['expected_value'] ?? null,
        ])->save();

        return $this->o[$name] = $o;
    }

    private function seedRadar(): void
    {
        $this->make('A AI build', ['score' => 85, 'recommendation' => 'apply', 'classifications' => ['AI_AUTOMATION'], 'expected_value' => 2000]);
        $this->make('B quick fix', ['score' => 72, 'recommendation' => 'review', 'classifications' => ['QUICK_WIN']]);
        $this->make('C viable skip', ['score' => 60, 'recommendation' => 'skip']);
        $this->make('D weak', ['score' => 40, 'recommendation' => 'skip']);
        $this->make('E rejected by filter', ['recommendation' => 'skip', 'hard_filters' => ['Already hired']]);
        $this->make('F excluded', ['score' => 70, 'recommendation' => 'review', 'discovery_ignored' => true]);
        $this->make('G older apply', ['score' => 90, 'recommendation' => 'apply'], '-3 days');
        $this->make('H direct ingest', ['score' => 75, 'recommendation' => 'review'], null); // no sighting: created_at counts

        // Pipeline rows from long ago (outside every window).
        foreach ([
            ['approved', []], ['proposal_draft', []], ['proposal_draft', ['proposal_approved_at' => now()->subDays(40)]],
            ['submitted', []], ['won', []], ['won', []], ['lost', []],
        ] as $i => [$status, $extra]) {
            $o = $this->make("P{$i} {$status}", ['score' => 80, 'recommendation' => 'apply', 'status' => $status], '-40 days');
            $o->forceFill($extra + ['created_at' => now()->subDays(40)])->save();
        }

        $run = UpworkDiscoveryRun::create(['uuid' => (string) Str::uuid(), 'status' => 'completed', 'started_at' => now()->subMinutes(30), 'finished_at' => now()->subMinutes(28)]);
        UpworkDiscoveryRun::create(['uuid' => (string) Str::uuid(), 'status' => 'failed', 'started_at' => now()->subMinutes(10), 'finished_at' => now()->subMinutes(9)]);
        $profile = UpworkSearchProfile::where('slug', 'ai-automation')->first();
        UpworkDiscoveryQuery::create(['run_id' => $run->id, 'search_profile_id' => $profile->id, 'query_type' => 'query', 'query_text' => 'OpenAI', 'results_count' => 30]);
        UpworkDiscoveryQuery::create(['run_id' => $run->id, 'search_profile_id' => $profile->id, 'query_type' => 'query', 'query_text' => 'n8n', 'results_count' => 12, 'status' => 'rate_limited']);
        foreach (['A AI build' => false, 'C viable skip' => false, 'F excluded' => true] as $name => $ignored) {
            UpworkOpportunitySighting::create(['opportunity_id' => $this->o[$name]->id, 'search_profile_id' => $profile->id, 'first_seen_at' => now(), 'last_seen_at' => now(), 'ignored' => $ignored]);
        }

        $this->o['A AI build']->changeStatus('approved', null, ['approved_at' => now()]);   // agrees with APPLY
        $this->o['B quick fix']->changeStatus('rejected', null, ['rejected_at' => now()]);  // disagrees with REVIEW
    }

    public function test_funnel_counts_are_database_derived(): void
    {
        $this->seedRadar();
        $today = app(RadarService::class)->radar('today');

        $this->assertSame([
            'results' => 42, 'discovered' => 7, 'relevant' => 5, 'viable' => 4, 'review' => 2, 'apply' => 1, 'hard_rejected' => 1, 'ignored' => 1,
        ], array_intersect_key($today['funnel'], array_flip(['results', 'discovered', 'relevant', 'viable', 'review', 'apply', 'hard_rejected', 'ignored'])));

        $week = app(RadarService::class)->radar('7d')['funnel'];
        $this->assertSame(8, $week['discovered']);
        $this->assertSame(2, $week['apply']);

        $month = app(RadarService::class)->radar('30d')['funnel'];
        $this->assertSame(8, $month['discovered'], 'pipeline rows from 40 days ago are outside every window');
    }

    public function test_categories_top_list_and_estimates(): void
    {
        $this->seedRadar();
        $today = app(RadarService::class)->radar('today');

        $categories = collect($today['categories'])->mapWithKeys(fn($r) => [$r['classification']->code => $r['count']])->all();
        $this->assertSame(['QUICK_WIN' => 1, 'AI_AUTOMATION' => 1], $categories);

        $this->assertSame(['A AI build', 'H direct ingest', 'C viable skip', 'D weak'], $today['top']->pluck('title')->all(), 'rejected B is left out; ignored F is not relevant');
        $this->assertSame(2000.0, $today['expected_value']['total']);
        $this->assertSame(1, $today['expected_value']['jobs']);
    }

    public function test_pipeline_decisions_and_health(): void
    {
        $this->seedRadar();
        $today = app(RadarService::class)->radar('today');

        $this->assertSame(['approved' => 2, 'proposal_drafts' => 1, 'ready_to_submit' => 1, 'submitted' => 1, 'won' => 2, 'lost' => 1],
            array_intersect_key($today['pipeline'], array_flip(['approved', 'proposal_drafts', 'ready_to_submit', 'submitted', 'won', 'lost'])));
        $this->assertSame(66.7, $today['pipeline']['win_rate']);
        $this->assertSame(['approved' => 1, 'rejected' => 1, 'agreement' => 50.0], array_map(fn($v) => is_int($v) ? $v : (float) $v, $today['decisions']));

        $h = $today['health'];
        $this->assertSame([2, 1, 1, 2, 1], [$h['runs'], $h['completed'], $h['failed'], $h['queries'], $h['rate_limited']]);
        $this->assertSame(90, $h['avg_duration_s']);

        $yield = collect($today['profiles'])->firstWhere('profile.slug', 'ai-automation');
        $this->assertSame(['found' => 3, 'ignored' => 1, 'viable' => 2, 'review_plus' => 1], array_intersect_key($yield, array_flip(['found', 'ignored', 'viable', 'review_plus'])));
    }

    public function test_radar_page_renders_and_is_access_controlled(): void
    {
        $this->seedRadar();
        $bde = $this->bde();

        $this->actingAs($bde)->get(route('upwork.radar'))
            ->assertOk()
            ->assertSee('UPWORK RADAR — TODAY')
            ->assertSee('Jobs discovered')->assertSee('42 search results returned', false)
            ->assertSee('A AI build')->assertSee('Quick Win')
            ->assertSee('Search profile yield')
            ->assertSee('ESTIMATE')
            ->assertSee('1 rate-limited');
        $this->actingAs($bde)->get(route('upwork.radar', ['range' => '7d']))->assertOk()->assertSee('LAST 7 DAYS')->assertSee('G older apply');
        $this->actingAs($bde)->get(route('upwork.radar', ['range' => 'bogus']))->assertOk()->assertSee('TODAY');

        $this->actingAs($this->developer())->get(route('upwork.radar'))->assertForbidden();
    }

    public function test_empty_radar_makes_no_claims(): void
    {
        $this->actingAs($this->bde())->get(route('upwork.radar'))
            ->assertOk()
            ->assertSee('No relevant opportunities in this period.')
            ->assertSee('no won/lost outcomes yet')
            ->assertSee('no decisions in this period');
    }

    public function test_list_views(): void
    {
        $this->seedRadar();
        $bde = $this->bde();

        $counts = collect(array_keys(RadarService::VIEWS))->mapWithKeys(fn($v) => [$v => RadarService::view($v)->count()])->all();
        $counts = \Illuminate\Support\Arr::except($counts, ['reminder_queue', 'overdue', 'snoozed']); // covered in UpworkArchiveTest
        $counts = \Illuminate\Support\Arr::except($counts, ['all_jobs', 'eligible', 'location_restricted', 'location_unknown']); // covered in UpworkLocationEligibilityTest
        $this->assertSame(['new_matches' => 2, 'review' => 1, 'apply' => 1, 'approved' => 2, 'rejected' => 1, 'proposal_drafts' => 1, 'ready_to_submit' => 1, 'archived' => 0], $counts);

        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['view' => 'new_matches']))
            ->assertOk()->assertSee('G older apply')->assertSee('H direct ingest')
            ->assertDontSee('A AI build')->assertDontSee('C viable skip')->assertDontSee('F excluded')
            ->assertSee('name="view" value="new_matches"', false);
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['view' => 'ready_to_submit']))
            ->assertOk()->assertSee('P2 proposal_draft')->assertDontSee('P1 proposal_draft');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['view' => 'new_matches', 'search' => 'older']))
            ->assertOk()->assertSee('G older apply')->assertDontSee('H direct ingest');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['view' => 'approved']))->assertOk()->assertSee('A AI build');
        $this->actingAs($bde)->get(route('upwork.opportunities.index'))->assertOk()->assertSee('Ready to Submit')->assertSee(route('upwork.radar'), false);
    }
}
