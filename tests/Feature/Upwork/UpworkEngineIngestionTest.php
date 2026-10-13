<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;

/**
 * Phase 2: ingestion with PMS-side scoring, versioning, status retention,
 * meaningful-change logging and the explainable scoring UI.
 */
class UpworkEngineIngestionTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
    }

    private function headers(): array
    {
        $token = $this->bde()->createToken('claude-upwork-agent', ['upwork:ingest'])->plainTextToken;

        return ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }

    /** A job as the agent sends it for PMS scoring: facts and signals, no score. */
    private function unscored(array $overrides = []): array
    {
        $payload = $this->payload(array_merge([
            'proposals_count' => null,
            'proposals_tier' => '50+',
            'contractor_type' => 'All freelancers',
            'workload' => 'full_time',
            'job_activity' => ['invitesSent' => 0, 'totalHired' => 0, 'totalOffered' => 0],
            'agent_signals' => ['technologies' => ['openai'], 'scope_size' => 'medium'],
            'raw_data' => ['source' => 'test', 'nested' => ['b' => 2, 'a' => 1]],
        ], $overrides));

        foreach (UpworkOpportunityService::ENGINE_FIELDS as $field) {
            if (!array_key_exists($field, $overrides)) {
                unset($payload[$field]);
            }
        }
        unset($payload['technologies']);

        return $payload;
    }

    public function test_unscored_job_is_scored_by_the_pms_with_an_explanation(): void
    {
        $this->postJson('/api/upwork/opportunities', $this->unscored(), $this->headers())->assertStatus(201);

        $o = UpworkOpportunity::first();
        $this->assertNotNull($o->score);
        $this->assertSame('engine-1/v1', $o->scoring_version);
        $this->assertSame(1, $o->ruleset_version);
        $this->assertSame(UpworkOpportunity::recommendationForScore($o->score, UpworkRuleset::current()->thresholds), $o->recommendation);
        $this->assertCount(7, $o->score_explanation['components']);
        $this->assertNotEmpty($o->score_explanation['scored_at']);
        $this->assertContains('Laravel', $o->technologies, 'detected technologies fill the list when the agent sends none');
        $this->assertContains('AI_AUTOMATION', $o->classifications);
        $this->assertSame('50+', $o->proposals_label);
        $this->assertEquals($o->technical_fit_score, $o->score_explanation['components']['technical_fit']['points']);
    }

    public function test_agent_cannot_inject_scores_into_a_pms_scored_job(): void
    {
        $payload = $this->unscored();
        $payload['recommendation'] = 'APPLY';
        $payload['ruleset_version'] = 99;
        $payload['hard_filters'] = [];

        $this->postJson('/api/upwork/opportunities', $payload, $this->headers())->assertStatus(201);

        $o = UpworkOpportunity::first();
        $this->assertSame(1, $o->ruleset_version);
        $this->assertSame(UpworkOpportunity::recommendationForScore($o->score, UpworkRuleset::current()->thresholds), $o->recommendation);
    }

    public function test_agent_scored_payload_keeps_legacy_behaviour(): void
    {
        $this->postJson('/api/upwork/opportunities', $this->payload(), $this->headers())->assertStatus(201);

        $o = UpworkOpportunity::first();
        $this->assertEquals(86, $o->score);
        $this->assertSame('1', $o->scoring_version);
        $this->assertNull($o->ruleset_version);
        $this->assertNull($o->score_explanation);
        $this->assertSame('apply', $o->recommendation);
    }

    public function test_hard_rejected_job_is_stored_not_discarded(): void
    {
        $this->postJson('/api/upwork/opportunities', $this->unscored(['contractor_type' => 'Individuals only']), $this->headers())
            ->assertStatus(201);

        $o = UpworkOpportunity::first();
        $this->assertSame('new', $o->status);
        $this->assertSame('skip', $o->recommendation);
        $this->assertNull($o->score);
        $this->assertNull($o->technical_fit_score);
        $this->assertSame(['Client wants individual freelancers only; Code4Each applies as an agency.'], $o->hard_filters);
        $this->assertSame('individual_only', $o->score_explanation['hard_filters'][0]['key']);
        $this->assertSame(1, $o->score_explanation['hard_filters'][0]['ruleset_version']);
    }

    public function test_rediscovering_an_unchanged_job_records_no_update(): void
    {
        $headers = $this->headers();
        $this->postJson('/api/upwork/opportunities', $this->unscored(), $headers)->assertStatus(201);
        $before = UpworkOpportunity::first();
        $logs = $before->logs()->count();

        $this->travel(5)->minutes();
        $this->postJson('/api/upwork/opportunities', $this->unscored(), $headers)->assertStatus(200);

        $after = UpworkOpportunity::first();
        $this->assertSame($logs, $after->logs()->count(), 'no "updated" entry for an unchanged job');
        $this->assertSame(0, $after->logs()->where('action', 'updated')->count());
        $this->assertEquals($before->updated_at, $after->updated_at);
        $this->assertSame($before->score_explanation['scored_at'], $after->score_explanation['scored_at']);
    }

    public function test_meaningful_changes_are_logged(): void
    {
        $headers = $this->headers();
        $this->postJson('/api/upwork/opportunities', $this->unscored(), $headers)->assertStatus(201);
        $this->postJson('/api/upwork/opportunities', $this->unscored(['proposals_tier' => '20 to 50']), $headers)->assertStatus(200);

        $log = UpworkOpportunity::first()->logs()->first();
        $this->assertSame('updated', $log->action);
        $this->assertStringContainsString('proposals_tier', $log->note);
        $this->assertStringContainsString('competition_score', $log->note);
    }

    public function test_approved_and_rejected_states_survive_rediscovery(): void
    {
        $headers = $this->headers();
        $service = app(UpworkOpportunityService::class);
        $reviewer = $this->bde();

        $this->postJson('/api/upwork/opportunities', $this->unscored(), $headers);
        $this->postJson('/api/upwork/opportunities', $this->unscored(['upwork_job_id' => '~02rejected', 'upwork_url' => 'https://www.upwork.com/jobs/~02rejected']), $headers);

        $approved = UpworkOpportunity::where('upwork_job_id', '01abc123def456')->first();
        $rejected = UpworkOpportunity::where('upwork_job_id', '02rejected')->first();
        $service->approve($approved, $reviewer->id);
        $service->reject($rejected, $reviewer->id, 'Not our niche');

        $this->postJson('/api/upwork/opportunities', $this->unscored(['hourly_max' => 90]), $headers)->assertStatus(200);
        $this->postJson('/api/upwork/opportunities', $this->unscored(['upwork_job_id' => '~02rejected', 'upwork_url' => 'https://www.upwork.com/jobs/~02rejected', 'hourly_max' => 90]), $headers)->assertStatus(200);

        $this->assertSame('approved', $approved->fresh()->status);
        $this->assertSame($reviewer->id, $approved->fresh()->approved_by);
        $this->assertSame('rejected', $rejected->fresh()->status);
        $this->assertSame('Not our niche', $rejected->fresh()->rejection_reason);
        $this->assertSame(2, UpworkOpportunity::count());
    }

    public function test_old_scores_keep_their_ruleset_version_until_rescored(): void
    {
        $headers = $this->headers();
        $this->postJson('/api/upwork/opportunities', $this->unscored(), $headers)->assertStatus(201);
        $v1Score = UpworkOpportunity::first()->score;

        $draft = UpworkRuleset::draftOrCreate();
        $draft->update(['weights' => array_merge($draft->weights, ['commercial_value' => 40])]);
        $draft->publish(null);

        $stored = UpworkOpportunity::first();
        $this->assertSame(1, $stored->ruleset_version, 'publishing a new version does not reinterpret stored scores');
        $this->assertEquals($v1Score, $stored->score);
        $this->assertEqualsWithDelta(15, $stored->score_explanation['components']['commercial_value']['max'], 0.01);

        $this->postJson('/api/upwork/opportunities', $this->unscored(), $headers)->assertStatus(200);
        $rescored = UpworkOpportunity::first();
        $this->assertSame(2, $rescored->ruleset_version);
        $this->assertSame('engine-1/v2', $rescored->scoring_version);
        $this->assertStringContainsString('ruleset_version', $rescored->logs()->first()->note);
    }

    public function test_detail_page_explains_the_score(): void
    {
        $this->postJson('/api/upwork/opportunities', $this->unscored(), $this->headers())->assertStatus(201);
        $o = UpworkOpportunity::first();

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))
            ->assertOk()
            ->assertSee('engine-1/v1')
            ->assertDontSee('vengine-1')
            ->assertSee('Ruleset v1')
            ->assertSee('Proven technology detected')
            ->assertSee('+10 Laravel')
            ->assertSee('Laravel · PROVEN')
            ->assertSee('AI Automation')
            ->assertSee('Client quality')
            ->assertSee('50+ /', false);

        $this->actingAs($this->bde())->get(route('upwork.opportunities.index'))->assertOk()->assertSee('50+');
    }

    public function test_detail_page_shows_hard_filter_record(): void
    {
        $this->postJson('/api/upwork/opportunities', $this->unscored(['job_activity' => ['totalHired' => 1]]), $this->headers())->assertStatus(201);

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', UpworkOpportunity::first()))
            ->assertOk()
            ->assertSee('Hard filters triggered')
            ->assertSee('already_hired')
            ->assertSee('ruleset v1');
    }
}
