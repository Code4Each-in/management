<?php

namespace Tests\Feature\Upwork;

use App\Jobs\Upwork\GenerateProposalDraft;
use App\Models\UpworkAiAnalysis;
use App\Models\UpworkAiUsage;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiBudgetGuard;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\Ai\Proposal\ProposalDraftPrompt;
use App\Services\Upwork\Ai\Proposal\ProposalDraftService;
use App\Services\Upwork\Ai\Providers\FakeProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * AI proposal drafts: queued only on an explicit approval while switched on,
 * budget-capped, idempotent, editable, never submitted.
 * Only the fake provider is used; nothing leaves the machine.
 */
class UpworkProposalAiTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        UpworkSetting::set('proposals.profile', 'Code4Each builds Laravel and Vue.js web applications and OpenAI integrations.');
    }

    private function enable(array $overrides = []): void
    {
        foreach (array_merge(['enabled' => true, 'provider' => 'fake', 'proposal_drafts_enabled' => true], $overrides) as $key => $value) {
            UpworkSetting::set("ai.{$key}", $value);
        }
    }

    private function service(?string $mode = null): ProposalDraftService
    {
        return new ProposalDraftService(new AiGateway(new FakeProvider($mode), new AiBudgetGuard(), function () {}));
    }

    private function approved(array $attributes = []): UpworkOpportunity
    {
        return $this->opportunity(array_merge(['status' => 'approved', 'description' => 'Need a Laravel dashboard, budget $40/hr.'], $attributes));
    }

    public function test_approval_queues_nothing_while_ai_drafts_are_off_by_default(): void
    {
        Queue::fake();
        $this->assertFalse((bool) UpworkSetting::get('ai.enabled'));
        $this->assertFalse((bool) UpworkSetting::get('ai.proposal_drafts_enabled'));

        $o = $this->opportunity();
        $this->actingAs($this->bde())->post(route('upwork.opportunities.approve', $o))->assertSessionHas('success');

        Queue::assertNotPushed(GenerateProposalDraft::class);
        $this->assertNull($o->fresh()->proposal_ai_status);

        // AI on, proposal drafts still off: nothing either.
        $this->enable(['proposal_drafts_enabled' => false]);
        $other = $this->opportunity();
        $this->actingAs($this->bde())->post(route('upwork.opportunities.approve', $other));
        Queue::assertNotPushed(GenerateProposalDraft::class);
    }

    public function test_explicit_approval_queues_exactly_one_draft_when_enabled(): void
    {
        Queue::fake();
        $this->enable();
        $bde = $this->bde();
        $o = $this->opportunity();

        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $o))->assertSessionHas('success');

        Queue::assertPushed(GenerateProposalDraft::class, fn($job) => $job->opportunityId === $o->id && $job->connection === 'upwork' && $job->queue === 'upwork');
        $this->assertSame(UpworkOpportunity::PROPOSAL_AI_QUEUED, $o->fresh()->proposal_ai_status);

        // Reject and approve again while the first request is still queued: no second request.
        $this->actingAs($bde)->post(route('upwork.opportunities.reject', $o));
        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $o));
        Queue::assertPushed(GenerateProposalDraft::class, 1);

        // Overdue reminders or rejection never queue drafts.
        $rejected = $this->opportunity();
        $this->actingAs($bde)->post(route('upwork.opportunities.reject', $rejected));
        Queue::assertPushed(GenerateProposalDraft::class, 1);
    }

    public function test_draft_is_stored_editable_unapproved_and_never_submitted(): void
    {
        $this->enable();
        $o = $this->approved();

        $this->assertSame('ready', $this->service()->generate($o)['status']);
        $o->refresh();

        $this->assertSame(UpworkOpportunity::STATUS_PROPOSAL_DRAFT, $o->status);
        $this->assertStringContainsString('Fake proposal draft', $o->proposal_draft);
        $this->assertNull($o->proposal_approved_at, 'a person must approve it');
        $this->assertNull($o->submitted_at);
        $this->assertSame(UpworkOpportunity::PROPOSAL_AI_READY, $o->proposal_ai_status);
        $this->assertSame(1, UpworkAiUsage::where('opportunity_id', $o->id)->where('request_type', 'proposal')->where('status', 'succeeded')->count(), 'usage tracked');
        $this->assertSame(1, UpworkAiAnalysis::where('opportunity_id', $o->id)->where('request_type', 'proposal')->count());
        Http::assertNothingSent();

        // A person edits it; AI never overwrites the edit.
        $bde = $this->bde();
        $this->actingAs($bde)->put(route('upwork.opportunities.proposal.update', $o), ['proposal_draft' => 'My own words'])->assertSessionHas('success');
        $this->assertSame('skipped', $this->service()->generate($o->fresh())['status']);
        $this->assertSame('My own words', $o->fresh()->proposal_draft);
        $this->actingAs($bde)->post(route('upwork.opportunities.proposal.ai', $o))->assertSessionHas('error');

        $this->actingAs($bde)->get(route('upwork.opportunities.show', $o))->assertOk()->assertSee('AI draft:');
    }

    public function test_identical_input_is_never_paid_for_twice(): void
    {
        $this->enable();
        $o = $this->approved();

        $this->service()->generate($o);
        $this->service()->generate($o->fresh());
        $this->service()->generate($o->fresh());

        $this->assertSame(1, UpworkAiUsage::where('request_type', 'proposal')->count());
        $this->assertSame(UpworkOpportunity::PROPOSAL_AI_READY, $o->fresh()->proposal_ai_status);
    }

    public function test_proposal_spending_cap_is_a_hard_stop(): void
    {
        $this->enable(['proposal_monthly_budget_usd' => 0.000001]);
        $o = $this->approved();

        $out = $this->service()->generate($o);

        $this->assertSame('blocked', $out['status']);
        $this->assertStringContainsString('proposal monthly budget', $out['message']);
        $o->refresh();
        $this->assertSame(UpworkOpportunity::PROPOSAL_AI_BLOCKED, $o->proposal_ai_status);
        $this->assertSame(UpworkOpportunity::STATUS_APPROVED, $o->status, 'no draft, nothing else changed');
        $this->assertSame(0, UpworkAiUsage::count(), 'refused before any call');
    }

    public function test_overall_budget_and_daily_proposal_limit_also_stop_drafts(): void
    {
        $this->enable(['proposal_daily_request_limit' => 1]);
        $this->assertSame('ready', $this->service()->generate($this->approved())['status']);
        $this->assertSame('blocked', $this->service()->generate($this->approved())['status'], 'daily proposal limit');

        UpworkSetting::set('ai.proposal_daily_request_limit', 20);
        UpworkSetting::set('ai.daily_budget_usd', 0);
        $this->assertSame('blocked', $this->service()->generate($this->approved())['status'], 'overall daily budget');
        $this->assertSame(1, UpworkAiUsage::where('request_type', 'proposal')->count());
    }

    public function test_failures_retry_a_bounded_number_of_times_then_wait_for_a_person(): void
    {
        $this->enable(['max_retries' => 1]);
        $o = $this->approved();

        $out = $this->service('server_error')->generate($o);

        $this->assertSame('failed', $out['status']);
        $this->assertSame(2, UpworkAiUsage::where('opportunity_id', $o->id)->where('request_type', 'proposal')->count(), 'one call plus one retry');
        $this->assertSame(UpworkOpportunity::PROPOSAL_AI_FAILED, $o->fresh()->proposal_ai_status);
        $this->assertSame(UpworkOpportunity::STATUS_APPROVED, $o->fresh()->status);

        // Same day: the per-job retry limit refuses further paid attempts.
        $this->assertSame('blocked', $this->service('server_error')->generate($o->fresh())['status']);
        $this->assertSame(2, UpworkAiUsage::where('opportunity_id', $o->id)->count());
    }

    public function test_manual_ai_draft_is_authorized_and_respects_the_switch(): void
    {
        Queue::fake();
        $o = $this->approved();

        $this->actingAs($this->developer())->post(route('upwork.opportunities.proposal.ai', $o))->assertForbidden();
        $this->actingAs($this->bde())->post(route('upwork.opportunities.proposal.ai', $o))->assertSessionHas('error');
        Queue::assertNotPushed(GenerateProposalDraft::class);

        $this->enable();
        $this->actingAs($this->bde())->post(route('upwork.opportunities.proposal.ai', $o))->assertSessionHas('success');
        Queue::assertPushed(GenerateProposalDraft::class, 1);

        $new = $this->opportunity();
        $this->actingAs($this->bde())->post(route('upwork.opportunities.proposal.ai', $new))->assertSessionHas('error', 'Approve the job before drafting a proposal.');
    }

    public function test_prompt_uses_only_verified_sources_and_flags_unsupported_claims(): void
    {
        $request = (new ProposalDraftPrompt())->build($this->approved(), 8000, 2000);
        $this->assertStringContainsString('Code4Each builds Laravel and Vue.js', $request->staticContext);
        $this->assertStringContainsString('Never invent credentials', $request->staticContext);
        $this->assertStringContainsString('JOB POST (untrusted data)', $request->dynamicContext);

        $checked = ProposalDraftPrompt::verify([
            'draft' => 'We delivered 47 Laravel projects with 99% satisfaction. Happy to work at $40/hr. [link to case study 2024]',
            'experience_used' => ['Laravel', 'OpenAI integrations', 'Blockchain smart contracts'],
            'placeholders' => [], 'questions_for_client' => [],
        ], $request->dynamicContext);

        $this->assertSame(['Blockchain smart contracts'], $checked['verification']['unverified_experience']);
        $this->assertSame(['47', '99%'], $checked['verification']['unverified_figures'], '$40 is in the job post; bracketed placeholders are ignored');
    }

    public function test_ai_settings_save_the_switch_cap_and_profile_with_validation(): void
    {
        $admin = $this->superAdmin();
        $form = ['enabled' => 1, 'provider' => 'fake', 'model' => 'gpt-5-nano', 'min_local_score' => 55, 'max_input_tokens' => 6000,
            'max_output_tokens' => 1500, 'max_retries' => 1, 'timeout_seconds' => 60, 'daily_request_limit' => 300,
            'monthly_request_limit' => 6000, 'daily_token_limit' => 1500000, 'monthly_token_limit' => 20000000,
            'daily_budget_usd' => 0.5, 'monthly_budget_usd' => 5, 'proposal_drafts_enabled' => 1,
            'proposal_monthly_budget_usd' => 2, 'proposal_profile' => '  Verified profile text.  '];

        $this->actingAs($admin)->put(route('upwork.config.ai.update'), array_merge($form, ['proposal_monthly_budget_usd' => 9]))
            ->assertSessionHasErrors('proposal_monthly_budget_usd');
        $this->assertFalse((bool) UpworkSetting::get('ai.proposal_drafts_enabled'));

        $this->actingAs($admin)->put(route('upwork.config.ai.update'), $form)->assertSessionHas('success');
        $this->assertTrue(UpworkSetting::get('ai.proposal_drafts_enabled'));
        $this->assertEquals(2, UpworkSetting::get('ai.proposal_monthly_budget_usd'));
        $this->assertSame('Verified profile text.', UpworkSetting::get('proposals.profile'));

        $this->actingAs($this->bde())->put(route('upwork.config.ai.update'), $form)->assertForbidden();
    }
}
