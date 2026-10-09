<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;

class UpworkProposalFlowTest extends UpworkTestCase
{
    public function test_full_flow_from_approval_to_won(): void
    {
        $user = $this->bde();
        $o = $this->opportunity(['match_summary' => 'Great Laravel fit.']);

        $this->actingAs($user)->post(route('upwork.opportunities.approve', $o));

        // Generate starter draft
        $this->actingAs($user)->post(route('upwork.opportunities.proposal.generate', $o))->assertSessionHas('success');
        $o->refresh();
        $this->assertSame(UpworkOpportunity::STATUS_PROPOSAL_DRAFT, $o->status);
        $this->assertStringContainsString('Great Laravel fit.', $o->proposal_draft);
        $this->assertNotNull($o->proposal_generated_at);

        // Cannot mark submitted before the draft is approved
        $this->actingAs($user)->post(route('upwork.opportunities.proposal.submitted', $o))->assertSessionHas('error');
        $this->assertSame('proposal_draft', $o->fresh()->status);

        // Human edit
        $this->actingAs($user)->put(route('upwork.opportunities.proposal.update', $o), [
            'proposal_draft' => 'Edited proposal text',
            'proposed_rate' => 60,
            'estimated_hours' => 120,
        ])->assertSessionHas('success');
        $o->refresh();
        $this->assertSame('Edited proposal text', $o->proposal_draft);
        $this->assertEquals(60, $o->proposed_rate);
        $this->assertNotNull($o->proposal_edited_at);
        $this->assertSame($user->id, $o->proposal_edited_by);

        // Approve draft, then a further edit clears the approval
        $this->actingAs($user)->post(route('upwork.opportunities.proposal.approve', $o));
        $this->assertNotNull($o->fresh()->proposal_approved_at);
        $this->actingAs($user)->put(route('upwork.opportunities.proposal.update', $o), [
            'proposal_draft' => 'Edited again', 'proposed_rate' => 60, 'estimated_hours' => 120,
        ]);
        $this->assertNull($o->fresh()->proposal_approved_at);

        // Re-approve, mark submitted (manual on Upwork), record outcome
        $this->actingAs($user)->post(route('upwork.opportunities.proposal.approve', $o));
        $this->actingAs($user)->post(route('upwork.opportunities.proposal.submitted', $o))->assertSessionHas('success');
        $o->refresh();
        $this->assertSame(UpworkOpportunity::STATUS_SUBMITTED, $o->status);
        $this->assertNotNull($o->submitted_at);

        $this->actingAs($user)->post(route('upwork.opportunities.outcome', $o), ['status' => 'client_response', 'note' => 'Client replied']);
        $this->actingAs($user)->post(route('upwork.opportunities.outcome', $o), ['status' => 'won']);
        $this->assertSame(UpworkOpportunity::STATUS_WON, $o->fresh()->status);

        // Created directly (not via ingestion), so there is no "ingested" entry.
        $actions = \App\Models\UpworkOpportunityLog::where('upwork_opportunity_id', $o->id)->orderBy('id')->pluck('action')->all();
        $this->assertSame([
            'approved', 'proposal_draft', 'proposal_edited', 'proposal_approved', 'proposal_edited',
            'proposal_approved', 'submitted', 'client_response', 'won',
        ], $actions);
    }

    public function test_proposal_draft_validation(): void
    {
        $user = $this->bde();
        $o = $this->opportunity(['status' => 'proposal_draft', 'proposal_draft' => 'x']);

        $this->actingAs($user)->put(route('upwork.opportunities.proposal.update', $o), ['proposal_draft' => ''])
            ->assertSessionHasErrors('proposal_draft');
        $this->actingAs($user)->put(route('upwork.opportunities.proposal.update', $o), ['proposal_draft' => 'ok', 'proposed_rate' => -5])
            ->assertSessionHasErrors('proposed_rate');
    }

    public function test_cannot_generate_draft_unless_approved(): void
    {
        $o = $this->opportunity();

        $this->actingAs($this->bde())->post(route('upwork.opportunities.proposal.generate', $o))->assertSessionHas('error');
        $this->assertSame('new', $o->fresh()->status);
        $this->assertNull($o->fresh()->proposal_draft);
    }

    public function test_users_without_access_cannot_manage_proposals(): void
    {
        $o = $this->opportunity(['status' => 'approved']);

        $this->actingAs($this->developer())->post(route('upwork.opportunities.proposal.generate', $o))->assertForbidden();
        $this->assertSame('approved', $o->fresh()->status);
    }

    public function test_detail_page_renders_draft_editor(): void
    {
        $o = $this->opportunity(['status' => 'proposal_draft', 'proposal_draft' => 'Draft body here', 'screening_answers' => [['question' => 'Why you?', 'answer' => 'Because']]]);

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))
            ->assertOk()
            ->assertSee('Draft body here')
            ->assertSee('Why you?')
            ->assertSee('Approve draft');
    }
}
