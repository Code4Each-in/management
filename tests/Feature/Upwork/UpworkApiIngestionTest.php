<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;

class UpworkApiIngestionTest extends UpworkTestCase
{
    private function agentHeaders(array $abilities = ['upwork:ingest']): array
    {
        $token = $this->bde()->createToken('claude-upwork-agent', $abilities)->plainTextToken;

        return ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }

    public function test_unauthenticated_ingestion_is_rejected_with_json(): void
    {
        // No Accept header on purpose: ForceJsonResponse must still yield JSON, not a login redirect.
        $this->post('/api/upwork/opportunities', $this->payload())
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->assertSame(0, UpworkOpportunity::count());
    }

    public function test_token_without_ingest_ability_is_forbidden(): void
    {
        $this->postJson('/api/upwork/opportunities', $this->payload(), $this->agentHeaders(['something-else']))
            ->assertStatus(403);

        $this->assertSame(0, UpworkOpportunity::count());
    }

    public function test_agent_can_create_opportunity_with_scoring_analysis_and_client_data(): void
    {
        $response = $this->postJson('/api/upwork/opportunities', $this->payload(), $this->agentHeaders());

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'created' => true])
            ->assertJsonPath('data.upwork_job_id', '01abc123def456')
            ->assertJsonPath('data.status', 'new');

        $o = UpworkOpportunity::firstOrFail();
        $this->assertSame('01abc123def456', $o->upwork_job_id, 'Leading ~ is stripped for deduplication');
        $this->assertSame(UpworkOpportunity::STATUS_NEW, $o->status);
        $this->assertSame('apply', $o->recommendation, 'Recommendation is normalised to lowercase');
        $this->assertEquals(86, $o->score);
        $this->assertEquals(18, $o->technical_fit_score);
        $this->assertEquals(17, $o->experience_fit_score);
        $this->assertEquals(19, $o->client_quality_score);
        $this->assertEquals(13, $o->commercial_value_score);
        $this->assertEquals(9, $o->long_term_score);
        $this->assertEquals(8, $o->ai_automation_score);
        $this->assertEquals(2, $o->competition_score);
        $this->assertSame(['Laravel', 'PHP', 'OpenAI'], $o->technologies);
        $this->assertSame('United States', $o->client_country);
        $this->assertEquals(125000, $o->client_total_spent);
        $this->assertTrue($o->payment_verified);
        $this->assertSame(['source' => 'test'], $o->raw_data);
        $this->assertNotNull($o->fetched_at);
        $this->assertSame(1, $o->logs()->where('action', 'ingested')->count());
    }

    public function test_duplicate_job_updates_instead_of_creating_and_preserves_human_state(): void
    {
        $headers = $this->agentHeaders();
        $this->postJson('/api/upwork/opportunities', $this->payload(), $headers)->assertStatus(201);

        $o = UpworkOpportunity::firstOrFail();
        $o->changeStatus(UpworkOpportunity::STATUS_APPROVED, null, ['approved_at' => now()]);

        // Same job, sent without "~" and with new numbers.
        $this->postJson('/api/upwork/opportunities', $this->payload([
            'upwork_job_id' => '01abc123def456',
            'proposals_count' => 25,
            'score' => 81,
        ]), $headers)
            ->assertStatus(200)
            ->assertJson(['created' => false]);

        $this->assertSame(1, UpworkOpportunity::count());
        $o->refresh();
        $this->assertSame(25, $o->proposals_count);
        $this->assertEquals(81, $o->score);
        $this->assertSame(UpworkOpportunity::STATUS_APPROVED, $o->status, 'Ingestion never changes human-owned status');
        $this->assertNotNull($o->approved_at);
        $this->assertSame(1, $o->logs()->where('action', 'updated')->count());
    }

    public function test_agent_cannot_set_status_or_proposal_fields_through_ingestion(): void
    {
        $this->postJson('/api/upwork/opportunities', $this->payload([
            'status' => 'won',
            'approved_by' => 1,
            'proposal_draft' => 'sneaky',
        ]), $this->agentHeaders())->assertStatus(201);

        $o = UpworkOpportunity::firstOrFail();
        $this->assertSame('new', $o->status);
        $this->assertNull($o->approved_by);
        $this->assertNull($o->proposal_draft);
    }

    public function test_validation_errors_are_returned_as_422(): void
    {
        $headers = $this->agentHeaders();

        $this->postJson('/api/upwork/opportunities', $this->payload(['title' => null, 'score' => 150]), $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'score']);

        $this->postJson('/api/upwork/opportunities', $this->payload(['upwork_url' => 'https://evil.example.com/jobs/1']), $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['upwork_url']);

        $this->postJson('/api/upwork/opportunities', $this->payload(['upwork_url' => 'javascript:alert(1)']), $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['upwork_url']);

        $this->postJson('/api/upwork/opportunities', $this->payload(['upwork_job_id' => '../../etc']), $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['upwork_job_id']);

        $this->postJson('/api/upwork/opportunities', $this->payload(['recommendation' => 'maybe']), $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['recommendation']);

        $this->assertSame(0, UpworkOpportunity::count());
    }

    public function test_recommendation_is_derived_from_score_when_missing(): void
    {
        $headers = $this->agentHeaders();

        foreach (['a1' => [85, 'apply'], 'a2' => [72, 'review'], 'a3' => [40, 'skip']] as $id => [$score, $expected]) {
            $this->postJson('/api/upwork/opportunities', $this->payload([
                'upwork_job_id' => $id, 'score' => $score, 'recommendation' => null,
            ]), $headers)->assertStatus(201);

            $this->assertSame($expected, UpworkOpportunity::where('upwork_job_id', $id)->value('recommendation'));
        }
    }

    public function test_utc_timestamps_are_stored_in_app_timezone(): void
    {
        config(['app.timezone' => 'Asia/Kolkata']);
        date_default_timezone_set('Asia/Kolkata');

        $this->postJson('/api/upwork/opportunities', $this->payload([
            'posted_at' => '2026-10-07T06:30:00Z',
            'fetched_at' => '2026-10-07T07:00:00+00:00',
        ]), $this->agentHeaders())->assertStatus(201);

        $row = \Illuminate\Support\Facades\DB::table('upwork_opportunities')->first();
        $this->assertSame('2026-10-07 12:00:00', $row->posted_at, '06:30 UTC is 12:00 IST');
        $this->assertSame('2026-10-07 12:30:00', $row->fetched_at);
    }

    public function test_list_analysis_fields_and_hard_filters_are_stored(): void
    {
        $this->postJson('/api/upwork/opportunities', $this->payload([
            'risks' => ['Low budget', 'New client'],
            'hard_filters' => ['extremely low budget'],
            'recommendation' => 'skip',
        ]), $this->agentHeaders())->assertStatus(201);

        $o = UpworkOpportunity::firstOrFail();
        $this->assertSame("- Low budget\n- New client", $o->risks);
        $this->assertSame(['extremely low budget'], $o->hard_filters);
        $this->assertSame('skip', $o->recommendation);
    }

    public function test_agent_can_list_approved_opportunities(): void
    {
        $this->opportunity(['status' => 'approved', 'title' => 'Approved one']);
        $this->opportunity(['title' => 'New one']);

        $this->getJson('/api/upwork/opportunities?status=approved', $this->agentHeaders())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Approved one');
    }

    public function test_agent_can_store_proposal_draft_only_for_approved_opportunities(): void
    {
        $headers = $this->agentHeaders();
        $new = $this->opportunity(['upwork_job_id' => 'newjob']);
        $approved = $this->opportunity(['upwork_job_id' => 'approvedjob', 'status' => 'approved']);
        $draft = ['proposal_draft' => 'Hello client', 'proposed_rate' => 55, 'screening_answers' => [['question' => 'Q1?', 'answer' => 'A1']]];

        $this->putJson('/api/upwork/opportunities/newjob/proposal-draft', $draft, $headers)->assertStatus(409);
        $this->putJson('/api/upwork/opportunities/missing/proposal-draft', $draft, $headers)->assertStatus(404);

        $this->putJson('/api/upwork/opportunities/~approvedjob/proposal-draft', $draft, $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'proposal_draft');

        $approved->refresh();
        $this->assertSame('Hello client', $approved->proposal_draft);
        $this->assertEquals(55, $approved->proposed_rate);
        $this->assertNotNull($approved->proposal_generated_at);
        $this->assertSame('new', $new->fresh()->status);
    }

    public function test_agent_cannot_overwrite_a_human_edited_draft(): void
    {
        $o = $this->opportunity(['upwork_job_id' => 'edited', 'status' => 'proposal_draft', 'proposal_draft' => 'Human text']);
        $o->forceFill(['proposal_edited_at' => now()])->save();

        $this->putJson('/api/upwork/opportunities/edited/proposal-draft', ['proposal_draft' => 'Agent text'], $this->agentHeaders())
            ->assertStatus(409);

        $this->assertSame('Human text', $o->fresh()->proposal_draft);
    }
}
