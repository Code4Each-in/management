<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSearchProfile;
use App\Models\UpworkSetting;
use App\Services\Upwork\Scoring\ScoringContext;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * AI redesign phase 2: tiered search profiles, search budgets, opt-in related
 * searches, and local filtering fixes (stale jobs, new clients).
 */
class UpworkTieredDiscoveryTest extends UpworkTestCase
{
    /** @var array */
    private $headers;

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        $token = $this->bde()->createToken('agent', ['upwork:ingest'])->plainTextToken;
        $this->headers = ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }

    private function plan(): array
    {
        UpworkDiscoveryRun::query()->whereNull('finished_at')->update(['status' => 'completed', 'finished_at' => now()]);

        return $this->postJson('/api/upwork/agent/runs', ['trigger' => 'manual'], $this->headers)->assertStatus(201)->json('data.plan');
    }

    private function searchesInPlan(array $plan, ?string $tier = null): int
    {
        return collect($plan)->when($tier, fn($c) => $c->where('tier', $tier))->sum(fn($p) => count($p['queries']));
    }

    private function recordSearches(string $slug, int $count): void
    {
        $run = UpworkDiscoveryRun::latest('id')->first();
        $profileId = UpworkSearchProfile::where('slug', $slug)->value('id');
        for ($i = 0; $i < $count; $i++) {
            \DB::table('upwork_discovery_queries')->insert(['run_id' => $run->id, 'search_profile_id' => $profileId, 'query_type' => 'query',
                'query_text' => "q{$i}", 'page' => 1, 'status' => 'ok', 'created_at' => now()]);
        }
    }

    public function test_seeded_profiles_have_conservative_tiers(): void
    {
        $tiers = UpworkSearchProfile::pluck('tier', 'slug');
        $this->assertSame('hot', $tiers['laravel-php']);
        $this->assertSame('hot', $tiers['ai-automation']);
        $this->assertSame('deep', $tiers['wordpress']);
        $this->assertSame(['hot' => 20, 'normal' => 120, 'deep' => 480], [
            'hot' => UpworkSearchProfile::where('slug', 'laravel-php')->value('frequency_minutes'),
            'normal' => UpworkSearchProfile::where('slug', 'saas')->value('frequency_minutes'),
            'deep' => UpworkSearchProfile::where('slug', 'wordpress')->value('frequency_minutes'),
        ]);
        $this->assertFalse((bool) UpworkSearchProfile::where('search_related', true)->exists(), 'related searches are opt-in');
    }

    public function test_hot_profiles_plan_first_and_tiers_respect_their_allowance(): void
    {
        $plan = $this->plan();

        $this->assertSame('hot', $plan[0]['tier']);
        $order = array_flip(array_keys(UpworkSearchProfile::TIERS));
        $ranks = array_map(fn($p) => $order[$p['tier']], $plan);
        $sorted = $ranks;
        sort($sorted);
        $this->assertSame($sorted, $ranks, 'tiers are planned hot → normal → deep → exploratory');

        foreach (UpworkSetting::get('discovery.tier_searches_per_run') as $tier => $allowance) {
            $this->assertLessThanOrEqual($allowance, $this->searchesInPlan($plan, $tier), "{$tier} allowance");
        }
        $this->assertLessThanOrEqual(15, $this->searchesInPlan($plan), 'run cap');
    }

    public function test_a_tier_with_zero_allowance_is_paused(): void
    {
        UpworkSetting::set('discovery.tier_searches_per_run', ['hot' => 0, 'normal' => 6, 'deep' => 0, 'exploratory' => 0]);

        $plan = $this->plan();

        $this->assertNotEmpty($plan);
        $this->assertSame(['normal'], array_values(array_unique(array_column($plan, 'tier'))));
    }

    public function test_profile_daily_budget_and_global_daily_cap_stop_searches(): void
    {
        UpworkSearchProfile::where('slug', 'laravel-php')->update(['daily_call_budget' => 2]);
        $this->plan();
        $this->recordSearches('laravel-php', 2);

        $this->assertNull(collect($this->plan())->firstWhere('slug', 'laravel-php'), 'profile budget used up today');

        $this->travel(1)->days();
        $this->assertNotNull(collect($this->plan())->firstWhere('slug', 'laravel-php'), 'budget resets at midnight');

        UpworkSetting::set('discovery.max_searches_per_day', 3);
        $this->recordSearches('saas', 3);
        $this->assertSame([], $this->plan(), 'global daily cap reached');
    }

    public function test_max_results_sets_page_size_and_pages(): void
    {
        UpworkSearchProfile::where('slug', 'laravel-php')->update(['max_results' => 5]);
        $q = collect($this->plan())->firstWhere('slug', 'laravel-php')['queries'][0];
        $this->assertSame([5, 1], [$q['params']['limit'], $q['pages']]);

        $this->travel(1)->days();
        UpworkSearchProfile::where('slug', 'laravel-php')->update(['max_results' => 25]);
        $q = collect($this->plan())->firstWhere('slug', 'laravel-php')['queries'][0];
        $this->assertSame([10, 3], [$q['params']['limit'], $q['pages']]);
    }

    public function test_tier_allowances_are_editable_and_audited(): void
    {
        $form = ['agent_enabled' => 0, 'agent_interval_minutes' => 20, 'agent_lease_minutes' => 15, 'agent_max_backoff_minutes' => 240,
            'agent_enrich_limit_per_run' => 5, 'discovery_max_queries_per_profile' => 4, 'discovery_max_queries_per_run' => 15,
            'discovery_max_searches_per_day' => 200, 'discovery_enrich_min_score' => 55, 'discovery_known_ids_per_profile' => 50,
            'tier_searches' => ['hot' => 4, 'normal' => 5, 'deep' => 2, 'exploratory' => 0]];

        $this->actingAs($this->superAdmin())->put(route('upwork.config.settings.update'), array_merge($form, ['tier_searches' => ['hot' => 99]]))
            ->assertSessionHasErrors('tier_searches.hot');
        $this->actingAs($this->superAdmin())->put(route('upwork.config.settings.update'), $form)->assertSessionHas('success', 'Settings saved.');

        $this->assertSame(['hot' => 4, 'normal' => 5, 'deep' => 2, 'exploratory' => 0], UpworkSetting::get('discovery.tier_searches_per_run'));
        $this->assertSame(200, UpworkSetting::get('discovery.max_searches_per_day'));
        $this->actingAs($this->superAdmin())->get(route('upwork.config.settings.edit'))->assertOk()->assertSee('Searches per run, by tier');
    }

    public function test_invalid_tier_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UpworkSearchProfile::create(['name' => 'Bad tier', 'keywords' => ['x'], 'tier' => 'urgent']);
    }

    // ---- Local filtering fixes ----------------------------------------------------------

    public function test_job_age_is_derived_from_posted_at(): void
    {
        $this->assertSame(26.0, ScoringContext::ageHours('2026-10-07T10:00:00Z'));
        $this->assertNull(ScoringContext::ageHours(null));
        $this->assertNull(ScoringContext::ageHours('not a date'));
    }

    public function test_stale_jobs_are_hard_rejected_but_stored(): void
    {
        $payload = $this->payload(['posted_at' => '2026-09-20T10:00:00Z']);
        unset($payload['score'], $payload['recommendation']);

        [$o] = app(UpworkOpportunityService::class)->ingest($payload);
        $o = $o->fresh();

        $this->assertSame(UpworkOpportunity::RECOMMENDATION_SKIP, $o->recommendation);
        $this->assertSame('stale_job', $o->score_explanation['hard_filters'][0]['key']);
    }

    public function test_first_time_clients_are_not_penalised_for_a_zero_hire_rate(): void
    {
        $base = $this->payload(['client_hire_rate' => 0, 'client_jobs_hired' => 0]);
        unset($base['score'], $base['recommendation']);
        $service = app(UpworkOpportunityService::class);

        [$new] = $service->ingest(array_merge($base, ['upwork_job_id' => '~01newclient', 'upwork_url' => 'https://www.upwork.com/jobs/~01newclient', 'client_jobs_posted' => 1]));
        [$old] = $service->ingest(array_merge($base, ['upwork_job_id' => '~01oldclient', 'upwork_url' => 'https://www.upwork.com/jobs/~01oldclient', 'client_jobs_posted' => 12]));

        $rules = fn($o) => collect($o->fresh()->score_explanation['components'] ?? [])->pluck('rules')->flatten(1)->pluck('key')->all();
        $this->assertNotContains('zero_hire_rate', $rules($new));
        $this->assertContains('zero_hire_rate', $rules($old));
        $this->assertGreaterThan($old->fresh()->score, $new->fresh()->score);
    }
}
