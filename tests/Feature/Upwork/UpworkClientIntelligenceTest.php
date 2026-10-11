<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkClient;
use App\Models\UpworkClientSnapshot;
use App\Models\UpworkOpportunity;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * Phase 4: client identity, snapshots, history-backed intelligence, client
 * scoring signals and the client intelligence UI.
 */
class UpworkClientIntelligenceTest extends UpworkTestCase
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

    /** A fully detailed (enriched) job for PMS scoring, from client C-100 unless overridden. */
    private function detailed(string $id, array $overrides = []): array
    {
        $payload = array_merge([
            'upwork_job_id' => "~02{$id}",
            'upwork_url' => "https://www.upwork.com/jobs/~02{$id}",
            'title' => 'Laravel SaaS dashboard',
            'description' => 'Extend our Laravel SaaS platform with a React reporting dashboard.',
            'job_type' => 'hourly', 'hourly_min' => 30, 'hourly_max' => 45, 'duration' => '1 to 3 months',
            'contractor_type' => 'All freelancers', 'proposals_tier' => '10 to 15',
            'client_country' => 'United States', 'client_rating' => 4.8, 'client_reviews_count' => 20,
            'client_total_spent' => 50000, 'client_hire_rate' => 70, 'client_jobs_posted' => 30, 'client_jobs_hired' => 21,
            'payment_verified' => true, 'job_activity' => ['totalHired' => 0, 'totalOffered' => 0],
            'upwork_client_id' => 'C-100',
            'client_open_jobs' => 2,
            'client_work_history' => [
                ['title' => 'Laravel API build', 'type' => 'HOURLY', 'status' => 'CLOSED', 'started' => '2026-06-01', 'ended' => '2026-07-01', 'feedback_score' => 5, 'reviewer' => 'Jane Doe', 'comment' => 'Great'],
            ],
        ], $overrides);

        return $payload;
    }

    private function send(array $payload, int $status = 201)
    {
        return $this->postJson('/api/upwork/opportunities', $payload, $this->headers)->assertStatus($status);
    }

    public function test_full_detail_creates_and_links_the_client_by_upwork_id(): void
    {
        $this->send($this->detailed('a1'));

        $client = UpworkClient::first();
        $this->assertSame('C-100', $client->external_id);
        $this->assertSame('United States', $client->country);
        $this->assertEquals(70, $client->hire_rate);
        $this->assertSame(2, $client->open_jobs);
        $this->assertNotNull($client->last_client_score);
        $this->assertSame($client->id, UpworkOpportunity::first()->upwork_client_id);

        $contract = $client->work_history[0];
        $this->assertSame('Laravel API build', $contract['title']);
        $this->assertArrayNotHasKey('reviewer', $contract, 'reviewer names are never stored');
        $this->assertArrayNotHasKey('comment', $contract);

        $this->assertSame(1, UpworkClientSnapshot::count());
    }

    public function test_jobs_without_a_client_id_are_never_linked_by_guesswork(): void
    {
        $this->send($this->detailed('a1'));
        $this->send($this->detailed('a2', ['upwork_client_id' => null]));

        $this->assertSame(1, UpworkClient::count());
        $this->assertNull(UpworkOpportunity::where('upwork_job_id', '02a2')->value('upwork_client_id'), 'identical figures are not treated as the same client');
    }

    public function test_returning_client_history_is_built_from_stored_jobs(): void
    {
        $service = app(UpworkOpportunityService::class);
        $reviewer = $this->bde();

        $this->send($this->detailed('j1', ['hourly_min' => 30, 'hourly_max' => 40]));
        $this->send($this->detailed('j2', ['hourly_min' => 40, 'hourly_max' => 50, 'title' => 'WordPress plugin', 'description' => 'Custom WordPress plugin.']));
        $this->send($this->detailed('j3', ['job_type' => 'fixed', 'fixed_budget' => 2000, 'hourly_min' => null, 'hourly_max' => null]));
        $service->approve(UpworkOpportunity::where('upwork_job_id', '02j1')->first(), $reviewer->id);
        $service->reject(UpworkOpportunity::where('upwork_job_id', '02j2')->first(), $reviewer->id, 'Budget too low');

        $this->send($this->detailed('j4'));
        $new = UpworkOpportunity::where('upwork_job_id', '02j4')->first();

        $history = app(\App\Services\Upwork\Clients\ClientIntelligenceService::class)->history($new->client, $new->id);
        $this->assertSame(3, $history['previous_jobs']);
        $this->assertEquals(['min' => 35.0, 'max' => 45.0, 'samples' => 2], $history['typical_hourly']);
        $this->assertEquals(['median' => 2000.0, 'samples' => 1], $history['typical_fixed']);
        $this->assertSame(1, $history['decisions']['approved']);
        $this->assertSame(1, $history['decisions']['rejected']);
        $this->assertSame(['Budget too low'], $history['rejection_reasons']);
        $this->assertSame(3, $new->score_explanation['client']['history']['seen_before']);
        $this->assertContains('RETURNING_CLIENT', $new->classifications);
        $this->assertNotContains('PREVIOUS_CLIENT', $new->classifications, 'no win yet');
    }

    public function test_a_previous_win_adds_explained_client_points(): void
    {
        $this->send($this->detailed('w1'));
        UpworkOpportunity::where('upwork_job_id', '02w1')->update(['status' => 'won']);

        $this->send($this->detailed('w2'));
        $o = UpworkOpportunity::where('upwork_job_id', '02w2')->first();

        $this->assertContains('won_before', array_column($o->score_explanation['components']['client_quality']['rules'], 'key'));
        $this->assertContains('PREVIOUS_CLIENT', $o->classifications);
    }

    public function test_upwork_previous_client_signal_is_used(): void
    {
        $this->send($this->detailed('p1', ['upwork_client_id' => 'C-200', 'agent_signals' => ['previous_client' => true]]));
        $o = UpworkOpportunity::first();

        $this->assertContains('upwork_previous_client', array_column($o->score_explanation['components']['client_quality']['rules'], 'key'));
        $this->assertContains('PREVIOUS_CLIENT', $o->classifications);
    }

    public function test_snapshots_record_changes_only(): void
    {
        $this->send($this->detailed('s1'));
        $this->send($this->detailed('s1'), 200);                            // same figures
        $this->send($this->detailed('s2', ['client_hire_rate' => 75]));   // changed

        $client = UpworkClient::first();
        $this->assertSame(2, $client->snapshots()->count());
        $this->assertEquals(75, $client->hire_rate);

        $trend = app(\App\Services\Upwork\Clients\ClientIntelligenceService::class)->history($client)['hire_rate_trend'];
        $this->assertEquals(['first' => 70.0, 'latest' => 75.0, 'snapshots' => 2], $trend);
    }

    public function test_search_rows_never_create_or_overwrite_clients(): void
    {
        $this->send($this->detailed('r1'));
        $run = $this->postJson('/api/upwork/agent/runs', ['trigger' => 'manual'], $this->headers)->assertStatus(201)->json('data.run.uuid');

        $this->postJson("/api/upwork/agent/runs/{$run}/queries", [
            'query' => ['type' => 'query', 'text' => 'Laravel'],
            'jobs' => [
                $this->detailed('r1', ['client_total_spent' => 1, 'upwork_client_id' => 'C-999']),
                $this->detailed('r9', ['upwork_client_id' => 'C-999']),
            ],
        ], $this->headers)->assertOk();

        $this->assertSame(['C-100'], UpworkClient::pluck('external_id')->all());
        $this->assertEquals(50000, UpworkClient::first()->total_spent);
        $this->assertNull(UpworkOpportunity::where('upwork_job_id', '02r9')->value('upwork_client_id'));
    }

    public function test_client_id_is_validated(): void
    {
        $this->send($this->detailed('v1', ['upwork_client_id' => '../../etc']), 422);
        $this->send($this->detailed('v2', ['client_work_history' => [['feedback_score' => 9]]]), 422);
    }

    public function test_detail_page_shows_history_backed_client_intelligence(): void
    {
        $this->send($this->detailed('u1', ['hourly_min' => 30, 'hourly_max' => 45]));
        $this->send($this->detailed('u2', ['client_hire_rate' => 75]));
        $o = UpworkOpportunity::where('upwork_job_id', '02u2')->first();

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))
            ->assertOk()
            ->assertSee('Seen before: 1 previous job.')
            ->assertSee('Historical hire rate')
            ->assertSee('70% → 75%', false)
            ->assertSee('$30–$45/hr', false)
            ->assertSee('median of 1 hourly job')
            ->assertSee('Laravel API build')
            ->assertSee('Returning Client')
            ->assertDontSee('Jane Doe');

        $first = UpworkOpportunity::where('upwork_job_id', '02u1')->first();
        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $first))->assertOk()->assertSee('Seen before: 1 previous job.');
    }

    public function test_unidentified_client_makes_no_claims(): void
    {
        $this->send($this->detailed('n1', ['upwork_client_id' => null]));

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', UpworkOpportunity::first()))
            ->assertOk()
            ->assertSee('Client not identified yet')
            ->assertDontSee('Seen before');
    }

    public function test_client_pages_and_access(): void
    {
        $this->send($this->detailed('c1'));
        $client = UpworkClient::first();

        $this->actingAs($this->bde())->get(route('upwork.clients.index'))->assertOk()->assertSee('C-100')->assertSee('1 clients');
        $this->actingAs($this->bde())->get(route('upwork.clients.show', $client))
            ->assertOk()->assertSee('Upwork client C-100')->assertSee('1 job from this client in the PMS.')->assertSee('Reported figures over time');

        $this->actingAs($this->developer())->get(route('upwork.clients.index'))->assertForbidden();
        $this->actingAs($this->developer())->get(route('upwork.clients.show', $client))->assertForbidden();
        auth()->guard('web')->logout();
        $this->app['auth']->forgetGuards();
        $this->get(route('upwork.clients.index'))->assertRedirect(route('login'));
    }
}
