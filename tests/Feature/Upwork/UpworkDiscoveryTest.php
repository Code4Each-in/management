<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkOpportunitySighting;
use App\Models\UpworkSearchProfile;
use App\Models\UpworkSetting;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * Phase 3: discovery engine (agent config, run lease, query plan, stage-1
 * sightings with cross-profile dedup, enrichment, run completion/backoff,
 * default visibility).
 */
class UpworkDiscoveryTest extends UpworkTestCase
{
    /** @var array */
    private $headers;

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        $token = $this->bde()->createToken('claude-upwork-agent', ['upwork:ingest'])->plainTextToken;
        $this->headers = ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }

    private function row(string $id, array $overrides = []): array
    {
        return array_merge([
            'upwork_job_id' => "~02{$id}",
            'upwork_url' => "https://www.upwork.com/jobs/~02{$id}",
            'title' => 'Laravel developer for SaaS dashboard',
            'description' => 'Extend our Laravel SaaS platform with a reporting dashboard and Stripe billing...',
            'job_type' => 'HOURLY', 'hourly_min' => 35, 'hourly_max' => 60, 'experience_level' => 'expert',
            'duration' => '3 to 6 months', 'workload' => 'part_time',
            'client_country' => 'United States', 'client_rating' => 4.9, 'client_reviews_count' => 30,
            'client_total_spent' => 80000, 'client_jobs_posted' => 40, 'payment_verified' => true,
            'proposals_tier' => '5 to 10', 'posted_at' => '2026-10-08T06:00:00Z',
            'raw_data' => ['skills' => ['Laravel', 'PHP']],
        ], $overrides);
    }

    private function startRun(string $trigger = 'manual'): array
    {
        return $this->postJson('/api/upwork/agent/runs', ['agent_host' => 'mac-mini', 'trigger' => $trigger], $this->headers)
            ->assertStatus(201)->json('data');
    }

    private function report(string $uuid, ?string $profileSlug, array $jobs, array $query = []): array
    {
        $profileId = $profileSlug ? UpworkSearchProfile::where('slug', $profileSlug)->value('id') : null;

        return $this->postJson("/api/upwork/agent/runs/{$uuid}/queries", [
            'query' => array_merge(['search_profile_id' => $profileId, 'type' => 'query', 'text' => 'Laravel', 'params' => ['query' => 'Laravel'], 'page' => 1, 'status' => 'ok', 'duration_ms' => 900], $query),
            'jobs' => $jobs,
        ], $this->headers)->assertOk()->json('data');
    }

    // ---- Config & access ------------------------------------------------------------

    public function test_agent_endpoints_require_the_ingest_token(): void
    {
        $this->getJson('/api/upwork/agent/config')->assertStatus(401);

        $other = $this->bde()->createToken('x', ['something-else'])->plainTextToken;
        $this->getJson('/api/upwork/agent/config', ['Authorization' => "Bearer {$other}"])->assertStatus(403);
        $this->postJson('/api/upwork/agent/runs', [], ['Authorization' => "Bearer {$other}"])->assertStatus(403);
    }

    public function test_config_is_read_only_and_comes_from_the_pms(): void
    {
        $config = $this->getJson('/api/upwork/agent/config', $this->headers)->assertOk()->json('data');

        $this->assertSame('read_only', $config['upwork']['mode']);
        $this->assertSame(['find_jobs', 'list_accounts'], $config['upwork']['allowed_tools']);
        $this->assertSame(['search', 'get'], $config['upwork']['allowed_find_jobs_actions']);
        $this->assertFalse($config['agent']['enabled']);
        $this->assertSame(20, $config['agent']['interval_minutes']);
        $this->assertSame(1, $config['ruleset']['version']);
        $this->assertCount(11, $config['profiles']);
        $this->assertContains('laravel', array_column($config['catalog'], 'slug'));
    }

    // ---- Run lease --------------------------------------------------------------------

    public function test_scheduled_runs_need_the_agent_enabled(): void
    {
        $this->postJson('/api/upwork/agent/runs', ['trigger' => 'scheduled'], $this->headers)->assertStatus(423);

        UpworkSetting::set('agent.enabled', true);
        $this->postJson('/api/upwork/agent/runs', ['trigger' => 'scheduled'], $this->headers)->assertStatus(201);
    }

    public function test_runs_never_overlap_and_dead_runs_are_abandoned(): void
    {
        $first = $this->startRun();

        $this->postJson('/api/upwork/agent/runs', ['trigger' => 'manual'], $this->headers)
            ->assertStatus(409)->assertJsonFragment(['success' => false]);

        $this->travel(16)->minutes(); // past the 15-minute lease
        $second = $this->startRun();

        $this->assertNotSame($first['run']['uuid'], $second['run']['uuid']);
        $this->assertSame('abandoned', UpworkDiscoveryRun::where('uuid', $first['run']['uuid'])->value('status'));
        $this->postJson("/api/upwork/agent/runs/{$first['run']['uuid']}/queries", ['query' => ['type' => 'query', 'text' => 'x'], 'jobs' => []], $this->headers)
            ->assertStatus(409);
    }

    public function test_heartbeat_extends_the_lease(): void
    {
        $uuid = $this->startRun()['run']['uuid'];
        $this->travel(10)->minutes();
        $this->postJson("/api/upwork/agent/runs/{$uuid}/heartbeat", [], $this->headers)->assertOk();
        $this->travel(10)->minutes(); // 20 minutes after start, 10 after the heartbeat

        $this->postJson('/api/upwork/agent/runs', ['trigger' => 'manual'], $this->headers)->assertStatus(409);
    }

    // ---- Query plan -------------------------------------------------------------------

    public function test_plan_is_bounded_prioritised_and_rotates(): void
    {
        $plan = $this->startRun()['plan'];

        $this->assertLessThanOrEqual(30, array_sum(array_map(fn($p) => count($p['queries']), $plan)));
        $this->assertSame('laravel-php', $plan[0]['slug'], 'highest priority first');
        foreach ($plan as $profile) {
            $this->assertLessThanOrEqual(4, count($profile['queries']));
        }

        $first = $plan[0]['queries'];
        $this->assertSame('query', $first[0]['type']);
        $this->assertSame('Laravel', $first[0]['params']['query']);
        $this->assertSame(10, $first[0]['params']['limit']);
        $this->assertSame('recency', $first[0]['params']['sort']);
        $this->assertTrue($first[0]['params']['verified_payment_only']);
        $this->assertTrue($first[0]['params']['include_full_details']);
        $this->assertArrayNotHasKey('pages', $first[0]['params'], 'PMS-only settings are not passed to Upwork');

        // A profile that actually ran continues its rotation next time; one that was
        // planned but never executed keeps its place.
        $uuid = UpworkDiscoveryRun::latest('id')->value('uuid');
        $this->report($uuid, 'laravel-php', []);
        $this->postJson("/api/upwork/agent/runs/{$uuid}/complete", ['status' => 'completed'], $this->headers)->assertOk();
        $this->travel(21)->minutes();
        $next = collect($this->startRun()['plan']);
        $this->assertNotSame(array_column($first, 'text'), array_column($next->firstWhere('slug', 'laravel-php')['queries'], 'text'));
        $this->assertSame(
            array_column(collect($plan)->firstWhere('slug', 'saas')['queries'], 'text'),
            array_column($next->firstWhere('slug', 'saas')['queries'], 'text'),
            'unexecuted profile repeats the same slice'
        );
    }

    public function test_plan_includes_titles_and_related_technologies_but_not_excluded_ones(): void
    {
        $profile = UpworkSearchProfile::create(['name' => 'Booking Platforms test', 'keywords' => ['reservation'], 'exact_phrases' => ['booking engine'], 'priority' => 1, 'tier' => 'hot']);
        $profile->syncTechnologies(\App\Models\UpworkTechnology::whereIn('slug', ['stripe', 'flutter'])->pluck('id')->all());
        UpworkSetting::set('discovery.max_queries_per_profile', 10);
        UpworkSetting::set('discovery.tier_searches_per_run', ['hot' => 20, 'normal' => 6, 'deep' => 12, 'exploratory' => 5]);

        $queries = collect($this->startRun()['plan'])->firstWhere('slug', 'booking-platforms-test')['queries'];
        $this->assertSame(['reservation', 'booking engine'], array_column($queries, 'text'), 'related technologies are opt-in');

        $profile->update(['search_related' => true]);
        UpworkDiscoveryRun::query()->update(['status' => 'completed', 'finished_at' => now()]);
        $queries = collect($this->startRun()['plan'])->firstWhere('slug', 'booking-platforms-test')['queries'];

        $this->assertSame(['reservation', 'booking engine', 'Stripe'], array_column($queries, 'text'));
        $this->assertSame('booking engine', $queries[1]['params']['title']);
        $this->assertArrayNotHasKey('query', $queries[1]['params']);
    }

    public function test_disabled_backed_off_and_not_due_profiles_are_skipped(): void
    {
        UpworkSearchProfile::where('slug', 'laravel-php')->update(['enabled' => false]);
        UpworkSearchProfile::where('slug', 'ai-automation')->update(['backoff_until' => now()->addHour()]);
        UpworkSearchProfile::where('slug', 'saas')->update(['last_run_at' => now()->subMinutes(5)]);

        $slugs = array_column($this->startRun()['plan'], 'slug');

        $this->assertNotContains('laravel-php', $slugs);
        $this->assertNotContains('ai-automation', $slugs);
        $this->assertNotContains('saas', $slugs);
    }

    // ---- Sightings ----------------------------------------------------------------------

    public function test_search_rows_become_discovered_scored_opportunities(): void
    {
        $uuid = $this->startRun()['run']['uuid'];
        $data = $this->report($uuid, 'laravel-php', [$this->row('aaa')]);

        $o = UpworkOpportunity::first();
        $this->assertSame('discovered', $o->stage);
        $this->assertSame('aaa', substr($o->upwork_job_id, 2));
        $this->assertNotNull($o->score);
        $this->assertSame(1, $o->ruleset_version);
        $this->assertSame('discovered', $o->logs()->first()->action);
        $this->assertStringContainsString('profile laravel-php', $o->logs()->first()->note);
        $this->assertTrue($data['results'][0]['created']);
        $this->assertContains($o->upwork_job_id, $data['enrich'], 'a promising row is flagged for enrichment');
    }

    public function test_same_job_from_several_searches_is_one_opportunity(): void
    {
        $uuid = $this->startRun()['run']['uuid'];

        $this->report($uuid, 'laravel-php', [$this->row('dup'), $this->row('dup')]);
        $this->report($uuid, 'saas', [$this->row('dup')], ['text' => 'SaaS platform']);

        $this->assertSame(1, UpworkOpportunity::count());
        $o = UpworkOpportunity::first();
        $this->assertSame(2, $o->sightings()->count(), 'one sighting per profile');
        $this->assertSame(1, $o->logs()->count(), 'rediscovery of an unchanged job is not logged');

        $queries = UpworkDiscoveryRun::where('uuid', $uuid)->first()->queries()->orderBy('id')->get();
        $this->assertSame([1, 0], [$queries[0]->new_count, $queries[1]->new_count]);
        $this->assertSame(1, $queries[0]->duplicate_count, 'second copy in the same batch');
        $this->assertSame(1, $queries[1]->duplicate_count, 'already returned by another profile in this run');
    }

    public function test_profile_exclusions_ignore_a_job_only_when_every_finding_profile_excludes_it(): void
    {
        $uuid = $this->startRun()['run']['uuid'];
        $junior = $this->row('jr', ['title' => 'Junior Laravel developer internship']);

        $data = $this->report($uuid, 'laravel-php', [$junior]);
        $o = UpworkOpportunity::first();
        $this->assertTrue($o->discovery_ignored);
        $this->assertFalse($data['results'][0]['enrich']);
        $this->assertStringContainsString('excluded keyword', $o->sightings()->first()->ignore_reason);

        UpworkSearchProfile::where('slug', 'saas')->update(['excluded_keywords' => null]);
        $this->report($uuid, 'saas', [$junior], ['text' => 'SaaS']);
        $this->assertFalse($o->fresh()->discovery_ignored, 'another profile still finds it relevant');
    }

    public function test_required_keywords_are_enforced(): void
    {
        UpworkSearchProfile::where('slug', 'erp')->update(['required_keywords' => ['odoo']]);
        $uuid = $this->startRun()['run']['uuid'];

        $this->report($uuid, 'erp', [$this->row('erp1', ['title' => 'ERP integration', 'description' => 'Integrate SAP with our shop.'])], ['text' => 'ERP']);

        $this->assertStringContainsString('required keywords', UpworkOpportunitySighting::first()->ignore_reason);
    }

    public function test_invalid_rows_are_audited_and_do_not_block_the_batch(): void
    {
        $uuid = $this->startRun()['run']['uuid'];
        $data = $this->report($uuid, 'laravel-php', [
            $this->row('good'),
            ['upwork_job_id' => '../etc/passwd', 'title' => 'x'],
            $this->row('bad', ['upwork_url' => 'https://evil.example.com/jobs/1']),
        ]);

        $this->assertSame(1, UpworkOpportunity::count());
        $this->assertCount(2, $data['invalid']);
        $query = UpworkDiscoveryRun::where('uuid', $uuid)->first()->queries()->first();
        $this->assertSame(3, $query->results_count);
        $this->assertStringContainsString('2 invalid row(s)', $query->error);
    }

    public function test_hard_rejected_search_rows_are_stored_counted_and_not_enriched(): void
    {
        $uuid = $this->startRun()['run']['uuid'];
        $data = $this->report($uuid, 'laravel-php', [$this->row('cheap', ['job_type' => 'fixed', 'fixed_budget' => 40, 'hourly_min' => null, 'hourly_max' => null])]);

        $this->assertSame(['impossible_fixed'], $data['results'][0]['hard_filters']);
        $this->assertFalse($data['results'][0]['enrich']);
        $this->assertSame(1, UpworkDiscoveryRun::where('uuid', $uuid)->first()->queries()->first()->hard_rejected_count);
    }

    // ---- Enrichment & rediscovery -------------------------------------------------------------

    public function test_enrichment_upgrades_the_job_and_search_rows_never_overwrite_details(): void
    {
        $uuid = $this->startRun()['run']['uuid'];
        $this->report($uuid, 'laravel-php', [$this->row('rich')]);

        $full = $this->row('rich', [
            'description' => 'Full description from the job detail page. Laravel, Stripe, SaaS, React dashboard.',
            'client_hire_rate' => 81, 'client_jobs_hired' => 33, 'contractor_type' => 'All freelancers',
            'job_activity' => ['totalHired' => 0, 'totalOffered' => 0], 'agent_signals' => ['scope_size' => 'large'],
        ]);
        $this->postJson('/api/upwork/opportunities', $full, $this->headers)->assertStatus(200);
        $this->assertSame('enriched', UpworkOpportunity::first()->stage);

        $this->travel(30)->minutes(); // the first run's lease has expired; the next cycle starts a new run
        $next = $this->startRun()['run']['uuid'];
        $this->report($next, 'laravel-php', [$this->row('rich', ['proposals_tier' => '20 to 50', 'client_total_spent' => 1, 'description' => 'snippet...'])]);

        $o = UpworkOpportunity::first();
        $this->assertSame('enriched', $o->stage, 'never downgraded');
        $this->assertStringStartsWith('Full description', $o->description, 'snippet does not overwrite detail');
        $this->assertEquals(80000, $o->client_total_spent, 'search row does not overwrite live client figures');
        $this->assertSame('20 to 50', $o->proposals_tier, 'competition figures are refreshed');
        $this->assertSame(2, $o->times_seen);
        $this->assertStringContainsString('proposals_tier', $o->logs()->first()->note);
    }

    public function test_human_decisions_survive_rediscovery(): void
    {
        $uuid = $this->startRun()['run']['uuid'];
        $this->report($uuid, 'laravel-php', [$this->row('ok'), $this->row('no')]);

        $service = app(UpworkOpportunityService::class);
        $reviewer = $this->bde();
        $service->approve(UpworkOpportunity::where('upwork_job_id', '02ok')->first(), $reviewer->id);
        $service->reject(UpworkOpportunity::where('upwork_job_id', '02no')->first(), $reviewer->id, 'Too small');

        $this->report($uuid, 'saas', [$this->row('ok', ['hourly_max' => 90]), $this->row('no', ['hourly_max' => 90])], ['text' => 'SaaS']);

        $this->assertSame('approved', UpworkOpportunity::where('upwork_job_id', '02ok')->value('status'));
        $this->assertSame('rejected', UpworkOpportunity::where('upwork_job_id', '02no')->value('status'));
        $this->assertSame('Too small', UpworkOpportunity::where('upwork_job_id', '02no')->value('rejection_reason'));
    }

    // ---- Completion, run state, backoff -------------------------------------------------------------

    public function test_completion_aggregates_stats_and_updates_profile_state(): void
    {
        $data = $this->startRun();
        $uuid = $data['run']['uuid'];
        $this->report($uuid, 'laravel-php', [$this->row('s1'), $this->row('s2')]);
        $this->report($uuid, 'ai-automation', [], ['text' => 'OpenAI', 'status' => 'error', 'error' => 'MCP timeout']);

        $stats = $this->postJson("/api/upwork/agent/runs/{$uuid}/complete", ['status' => 'completed', 'enriched' => 1], $this->headers)
            ->assertOk()->json('data.stats');

        $this->assertSame(['queries' => 2, 'failed_queries' => 1, 'found' => 2, 'new' => 2], array_intersect_key($stats, array_flip(['queries', 'failed_queries', 'found', 'new'])));
        $this->assertSame(1, $stats['enriched']);

        $laravel = UpworkSearchProfile::where('slug', 'laravel-php')->first();
        $this->assertNotNull($laravel->last_success_at);
        $this->assertSame(0, $laravel->consecutive_failures);

        $ai = UpworkSearchProfile::where('slug', 'ai-automation')->first();
        $this->assertSame(1, $ai->consecutive_failures);
        $this->assertEqualsWithDelta(20, now()->diffInMinutes($ai->backoff_until), 1);
        $this->assertStringContainsString('MCP timeout', $ai->last_error);

        $saas = UpworkSearchProfile::where('slug', 'saas')->first();
        $this->assertNull($saas->last_run_at, 'planned but never executed: stays due');

        $this->postJson("/api/upwork/agent/runs/{$uuid}/complete", ['status' => 'completed'], $this->headers)->assertStatus(409);
    }

    public function test_backoff_grows_exponentially_and_doubles_on_rate_limits(): void
    {
        UpworkSearchProfile::where('slug', 'laravel-php')->update(['consecutive_failures' => 2]);
        $uuid = $this->startRun()['run']['uuid'];
        $this->report($uuid, 'laravel-php', [], ['status' => 'rate_limited', 'error' => '429']);
        $this->postJson("/api/upwork/agent/runs/{$uuid}/complete", ['status' => 'completed'], $this->headers)->assertOk();

        $profile = UpworkSearchProfile::where('slug', 'laravel-php')->first();
        $this->assertSame(3, $profile->consecutive_failures);
        // 20 min * 2^(3-1) = 80, doubled for a rate limit = 160 (cap 240).
        $this->assertEqualsWithDelta(160, now()->diffInMinutes($profile->backoff_until), 1);
        $this->assertStringContainsString('[rate limited]', $profile->last_error);
    }

    // ---- Visibility ---------------------------------------------------------------------------

    public function test_list_hides_untouched_noise_by_default(): void
    {
        $uuid = $this->startRun()['run']['uuid'];
        $this->report($uuid, 'laravel-php', [
            $this->row('good', ['title' => 'Laravel SaaS platform build']),
            $this->row('cheap', ['title' => 'Tiny Laravel fix', 'job_type' => 'fixed', 'fixed_budget' => 40, 'hourly_min' => null, 'hourly_max' => null]),
        ]);
        $rejected = $this->opportunity(['title' => 'Rejected skipped job', 'recommendation' => 'skip', 'status' => 'rejected']);

        $user = $this->bde();
        $this->actingAs($user)->get(route('upwork.opportunities.index'))
            ->assertOk()
            ->assertSee('Laravel SaaS platform build')
            ->assertDontSee('Tiny Laravel fix')
            ->assertSee($rejected->title, 'human decisions stay visible')
            ->assertSee('Show all (1 hidden)');

        $this->actingAs($user)->get(route('upwork.opportunities.index', ['show' => 'all']))->assertSee('Tiny Laravel fix');
        $this->actingAs($user)->get(route('upwork.opportunities.index', ['recommendation' => 'skip']))->assertSee('Tiny Laravel fix');
    }
}
