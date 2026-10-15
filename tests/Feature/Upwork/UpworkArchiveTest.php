<?php

namespace Tests\Feature\Upwork;

use App\Jobs\Upwork\GenerateProposalDraft;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\Proposal\ProposalDraftService;
use App\Services\Upwork\Notifications\NotificationService;
use App\Services\Upwork\RadarService;
use App\Services\Upwork\Review\ReviewSlaService;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Support\Facades\Http;

/**
 * Archiving: a job leaves the review queue and every reminder path without
 * being approved, rejected or deleted; restore returns it to its previous
 * status on the same record with a fresh deadline (no reminder burst).
 * Nothing here contacts Upwork.
 */
class UpworkArchiveTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        UpworkSetting::set('review.reminders_enabled', true);
        UpworkSetting::set('notifications.channels', ['log']);
    }

    private function sla(): ReviewSlaService
    {
        return app(ReviewSlaService::class);
    }

    private function service(): UpworkOpportunityService
    {
        return app(UpworkOpportunityService::class);
    }

    /** An enriched APPLY job with its review clock running (first seen now, or $minutesAgo). */
    private function eligible(array $attributes = [], int $minutesAgo = 0): UpworkOpportunity
    {
        $o = $this->opportunity(array_merge(['recommendation' => 'apply', 'score' => 86], $attributes));
        $o->forceFill(['stage' => UpworkOpportunity::STAGE_ENRICHED, 'first_seen_at' => now()->subMinutes($minutesAgo)])->save();
        $this->sla()->startIfEligible($o);

        return $o->fresh();
    }

    private function reminders(UpworkOpportunity $o): int
    {
        return $o->logs()->where('action', 'review_reminder')->count();
    }

    // ---- Single archive ---------------------------------------------------------------

    public function test_archiving_one_job_keeps_everything_and_is_not_a_review_decision(): void
    {
        $o = $this->eligible(['title' => 'Keep my data', 'description' => 'Original text']);
        $this->travel(12)->minutes();
        $this->sla()->run(); // first reminder
        $before = $o->fresh()->only(['upwork_job_id', 'title', 'description', 'score', 'recommendation', 'first_seen_at', 'created_at', 'review_due_at', 'review_reminder_stage']);
        $bde = $this->bde();

        $this->actingAs($bde)->post(route('upwork.opportunities.archive', $o))->assertSessionHas('success');

        $o->refresh();
        $this->assertSame(UpworkOpportunity::STATUS_ARCHIVED, $o->status);
        $this->assertSame(['new', $bde->id], [$o->archived_from_status, $o->archived_by]);
        $this->assertNotNull($o->archived_at);
        $this->assertEquals($before, $o->only(array_keys($before)), 'job data, score, discovery time and SLA record are untouched');
        $this->assertNull($o->reviewed_at, 'archiving is not a review decision');
        $this->assertNull($o->approved_at);
        $this->assertNull($o->rejected_at);
        $this->assertNull($o->rejection_reason);

        $log = $o->logs()->where('action', 'archived')->first();
        $this->assertSame(['new', 'archived', $bde->id], [$log->old_status, $log->new_status, $log->user_id]);
        $this->assertStringContainsString('Not an approval or rejection', $log->note);
        $this->assertStringContainsString('last reminder #1', $log->note, 'SLA state recorded in the audit log');
        $this->assertTrue($o->logs()->where('action', 'review_reminder')->exists(), 'earlier history kept');

        $this->actingAs($bde)->get(route('upwork.opportunities.show', $o))->assertOk()
            ->assertSee('Archived by')->assertSee('was New')->assertSee('Restore')->assertDontSee('>Approve<', false);
        Http::assertNothingSent();
    }

    public function test_only_review_stage_jobs_can_be_archived_and_twice_is_refused(): void
    {
        $sent = $this->opportunity(['status' => 'submitted']);
        $this->actingAs($this->bde())->post(route('upwork.opportunities.archive', $sent))->assertSessionHas('error');
        $this->assertSame('submitted', $sent->fresh()->status);

        $o = $this->opportunity();
        $this->service()->archive($o, $this->bde()->id);
        $this->expectException(\DomainException::class);
        $this->service()->archive($o->fresh(), $this->bde()->id);
    }

    public function test_archived_jobs_cannot_be_approved_rejected_or_snoozed_until_restored(): void
    {
        $o = $this->eligible();
        $this->service()->archive($o, $this->bde()->id);
        $bde = $this->bde();

        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $o))->assertSessionHas('error');
        $this->actingAs($bde)->post(route('upwork.opportunities.reject', $o))->assertSessionHas('error');
        $this->actingAs($bde)->post(route('upwork.opportunities.snooze', $o), ['preset' => '1h'])->assertSessionHas('error');
        $this->assertSame('archived', $o->fresh()->status);
        $this->assertNull($o->fresh()->review_snoozed_until);
    }

    // ---- Reminders: archive is authoritative ---------------------------------------------

    public function test_archived_jobs_never_get_reminders_over_many_scheduler_runs(): void
    {
        $archived = $this->eligible(['title' => 'Archived']);
        $active = $this->eligible(['title' => 'Active']);
        $this->service()->archive($archived, $this->bde()->id);

        // 90 minutes of minute ticks: past every reminder, overdue and many repeats.
        for ($i = 0; $i < 90; $i++) {
            $this->travel(1)->minutes();
            $this->sla()->run();
            $this->artisan('upwork:review-reminders')->assertExitCode(0);
        }

        $archived->refresh();
        $this->assertSame(0, $this->reminders($archived));
        $this->assertNull($archived->review_overdue_at, 'not even marked overdue');
        $this->assertSame(0, $archived->logs()->where('action', 'review_overdue')->count());
        $this->assertSame('archived', $archived->status);
        $this->assertGreaterThan(5, $this->reminders($active), 'the scheduler itself kept working');
    }

    public function test_a_reminder_already_picked_up_is_not_sent_once_the_job_is_archived(): void
    {
        $o = $this->eligible();
        $this->travel(12)->minutes();
        // A runner loaded the job as a candidate ...
        $candidate = UpworkOpportunity::find($o->id);
        $stage = $this->sla()->stageAt($candidate, now());
        $this->assertSame(1, $stage);

        // ... then the job is archived before that runner claims the reminder.
        $this->service()->archive($o, $this->bde()->id);

        $this->assertFalse($this->sla()->claimReminder($candidate, $stage, now()), 'the claim re-checks the database and skips it');
        $this->assertNull($o->fresh()->review_reminder_stage);

        // Retries of the same tick and later ticks send nothing either.
        $this->assertSame(0, $this->sla()->run()['sent']);
        $this->assertSame(0, $this->sla()->run()['sent']);
        $this->assertSame(0, $this->reminders($o));
    }

    public function test_archived_jobs_are_out_of_every_active_and_notification_query(): void
    {
        $o = $this->eligible(['title' => 'Gone from queues']);
        $this->service()->archive($o, $this->bde()->id);
        $o->refresh();

        $this->assertFalse(ReviewSlaService::isEligible($o));
        $this->assertSame(0, UpworkOpportunity::visible()->whereKey($o->id)->count());
        foreach (['new_matches', 'review', 'apply', 'approved', 'rejected', 'proposal_drafts', 'ready_to_submit'] as $view) {
            $this->assertSame(0, RadarService::view($view)->whereKey($o->id)->count(), $view);
        }
        $this->assertSame(1, RadarService::view('archived')->whereKey($o->id)->count());
        $this->assertNull(app(NotificationService::class)->maybeNotify($o), 'no new-job notification');
        $this->assertFalse($this->sla()->startIfEligible($o));

        $bde = $this->bde();
        $this->actingAs($bde)->get(route('upwork.opportunities.index'))->assertOk()->assertDontSee('Gone from queues');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['show' => 'all']))->assertOk()->assertDontSee('Gone from queues');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['view' => 'archived']))->assertOk()->assertSee('Gone from queues');
        $this->actingAs($bde)->get(route('upwork.radar'))->assertOk()->assertDontSee('Gone from queues');
    }

    public function test_archived_job_stays_archived_after_rediscovery_and_dedup(): void
    {
        [$o] = $this->service()->ingest($this->payload());
        $this->service()->archive($o->fresh(), $this->bde()->id);

        // Seen again by search and by full details, under both id forms.
        $this->travel(5)->minutes();
        $this->service()->ingestSighting($this->payload(['proposals_count' => 30]));
        $this->service()->ingest($this->payload(['upwork_job_id' => '01abc123def456']));

        $this->assertSame(1, UpworkOpportunity::count());
        $o->refresh();
        $this->assertSame('archived', $o->status);
        $this->assertSame(0, $this->reminders($o));
    }

    public function test_a_queued_ai_draft_for_an_archived_job_is_skipped(): void
    {
        UpworkSetting::set('ai.enabled', true);
        UpworkSetting::set('ai.proposal_drafts_enabled', true);
        UpworkSetting::set('ai.provider', 'fake');
        $o = $this->opportunity(['status' => 'approved']);
        $this->service()->archive($o, $this->bde()->id);

        (new GenerateProposalDraft($o->id, null))->handle(app(ProposalDraftService::class));

        $this->assertSame(UpworkOpportunity::PROPOSAL_AI_SKIPPED, $o->fresh()->proposal_ai_status);
        $this->assertNull($o->fresh()->proposal_draft);
    }

    // ---- Restore ------------------------------------------------------------------------

    public function test_restore_returns_to_review_with_a_fresh_deadline_and_no_reminder_burst(): void
    {
        $o = $this->eligible([], 0);
        $firstSeen = $o->first_seen_at;
        $this->travel(40)->minutes();
        $this->sla()->run(); // overdue reminder sent
        $this->assertNotNull($o->fresh()->review_overdue_at);
        $this->service()->archive($o->fresh(), $this->bde()->id);

        // Archived for two days.
        $this->travel(2)->days();
        $this->actingAs($this->bde())->post(route('upwork.opportunities.restore', $o))->assertSessionHas('success');

        $o->refresh();
        $this->assertSame('new', $o->status);
        $this->assertNull($o->archived_at);
        $this->assertSame($firstSeen->timestamp, $o->first_seen_at->timestamp, 'discovery time preserved');
        $this->assertSame(now()->addMinutes(30)->timestamp, $o->review_due_at->timestamp, 'fresh deadline');
        $this->assertNull($o->review_overdue_at);
        $this->assertNull($o->review_reminder_stage);
        $note = $o->logs()->where('action', 'review_sla_restarted')->value('note');
        $this->assertStringContainsString('previous deadline', $note);
        $this->assertStringContainsString('overdue since', $note, 'old SLA record kept in the log');
        $this->assertSame(1, $o->logs()->where('action', 'review_overdue')->count(), 'historical overdue entry kept');
        $remindersBefore = $this->reminders($o);

        // Right after restore: nothing. Then the normal schedule, one level at a time.
        $this->assertSame(0, $this->sla()->run()['sent']);
        $this->travel(9)->minutes();
        $this->assertSame(0, $this->sla()->run()['sent']);
        $this->travel(2)->minutes();
        $this->assertSame(1, $this->sla()->run()['sent']);
        $this->assertSame(0, $this->sla()->run()['sent']);
        $this->assertSame($remindersBefore + 1, $this->reminders($o));
        $this->assertSame(1, UpworkOpportunity::count(), 'same record, no duplicate');
    }

    public function test_restore_returns_each_job_to_the_status_it_was_archived_from(): void
    {
        $approved = $this->opportunity(['status' => 'approved']);
        $rejected = $this->opportunity(['status' => 'rejected']);
        $skip = $this->opportunity(['recommendation' => 'skip', 'score' => 20]);
        foreach ([$approved, $rejected, $skip] as $o) {
            $this->service()->archive($o, $this->bde()->id);
        }

        $this->assertSame('approved', $this->service()->restore($approved->fresh(), $this->bde()->id));
        $this->assertSame('rejected', $this->service()->restore($rejected->fresh(), $this->bde()->id));
        $this->assertSame('new', $this->service()->restore($skip->fresh(), $this->bde()->id));
        $this->assertNull($skip->fresh()->review_due_at, 'an ineligible job gets no deadline');
        $this->assertSame(1, $approved->logs()->where('action', 'restored')->count());
        $this->assertNull($approved->fresh()->review_due_at, 'only jobs back in review get a deadline');

        $this->expectException(\DomainException::class);
        $this->service()->restore($approved->fresh(), $this->bde()->id);
    }

    // ---- Bulk -----------------------------------------------------------------------------

    public function test_bulk_archive_of_selected_ids_touches_only_those_and_reports_failures(): void
    {
        $a = $this->opportunity(['title' => 'A']);
        $b = $this->opportunity(['title' => 'B']);
        $c = $this->opportunity(['title' => 'C not selected']);
        $won = $this->opportunity(['title' => 'Won', 'status' => 'won']);

        $this->actingAs($this->bde())->post(route('upwork.opportunities.bulk'), [
            'action' => 'archive', 'mode' => 'ids', 'ids' => [$a->id, $b->id, $won->id, 999999],
        ])->assertSessionHas('success', 'Archived 2 of 4 selected job(s).')->assertSessionHas('bulk_failures');

        $this->assertSame(['archived', 'archived', 'new', 'won'], [$a->fresh()->status, $b->fresh()->status, $c->fresh()->status, $won->fresh()->status]);
        $this->assertStringContainsString("#999999: Not found.", session('bulk_failures'));
    }

    public function test_select_all_on_a_page_is_ids_but_select_all_matching_uses_the_filter(): void
    {
        config(['upwork.per_page' => 2]);
        foreach (range(1, 5) as $i) {
            $this->opportunity(['title' => "Laravel job {$i}"]);
        }
        $other = $this->opportunity(['title' => 'WordPress job']);
        $bde = $this->bde();

        // The page shows 2 rows but offers "all 5 matching".
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['search' => 'Laravel']))->assertOk()
            ->assertSee('Select all 5 jobs matching this filter')->assertSee('name="filter[search]" value="Laravel"', false);

        // Select-all-on-page posts the page's ids only.
        $page = UpworkOpportunity::where('title', 'like', 'Laravel%')->orderByDesc('id')->limit(2)->pluck('id')->all();
        $this->actingAs($bde)->post(route('upwork.opportunities.bulk'), ['action' => 'archive', 'mode' => 'ids', 'ids' => $page])
            ->assertSessionHas('success', 'Archived 2 of 2 selected job(s).');
        $this->assertSame(3, UpworkOpportunity::where('title', 'like', 'Laravel%')->where('status', 'new')->count());

        // Select-all-matching re-applies the filter on the server: the 3 remaining Laravel jobs, never the WordPress one.
        $this->actingAs($bde)->post(route('upwork.opportunities.bulk'), ['action' => 'archive', 'mode' => 'filter', 'filter' => ['search' => 'Laravel'], 'expected' => 3])
            ->assertSessionHas('success', 'Archived 3 of 3 selected job(s).');
        $this->assertSame(0, UpworkOpportunity::where('title', 'like', 'Laravel%')->where('status', 'new')->count());
        $this->assertSame('new', $other->fresh()->status);
    }

    public function test_filter_bulk_refuses_when_the_matching_count_changed(): void
    {
        $this->opportunity(['title' => 'Laravel one']);
        $this->opportunity(['title' => 'Laravel two']);

        // Confirmed 1, but 2 match now (e.g. discovery added one meanwhile): nothing changes.
        $this->actingAs($this->bde())->post(route('upwork.opportunities.bulk'), ['action' => 'archive', 'mode' => 'filter', 'filter' => ['search' => 'Laravel'], 'expected' => 1])
            ->assertSessionHas('error', 'The filter now matches 2 jobs, not the 1 you confirmed. Nothing was changed; check the list and try again.');
        $this->assertSame(0, UpworkOpportunity::where('status', 'archived')->count());
    }

    public function test_bulk_restore_from_the_archived_tab_with_partial_failure(): void
    {
        $a = $this->eligible(['title' => 'Restore A']);
        $b = $this->eligible(['title' => 'Restore B']);
        $notArchived = $this->opportunity(['title' => 'Still new']);
        $this->service()->bulk('archive', [$a->id, $b->id], $this->bde()->id);
        $bde = $this->bde();

        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['view' => 'archived', 'search' => 'Restore']))->assertOk()
            ->assertSee('Restore Selected')->assertSee('Restore A')->assertSee('Restore B')->assertDontSee('Still new');

        $this->actingAs($bde)->post(route('upwork.opportunities.bulk'), ['action' => 'restore', 'mode' => 'ids', 'ids' => [$a->id, $b->id, $notArchived->id]])
            ->assertSessionHas('success', 'Restored 2 of 3 selected job(s).')->assertSessionHas('bulk_failures');
        $this->assertSame(['new', 'new', 'new'], [$a->fresh()->status, $b->fresh()->status, $notArchived->fresh()->status]);
        $this->assertSame(3, UpworkOpportunity::count());

        // Restore all matching in the archived tab.
        $this->service()->bulk('archive', [$a->id, $b->id], $bde->id);
        $this->actingAs($bde)->post(route('upwork.opportunities.bulk'), ['action' => 'restore', 'mode' => 'filter', 'filter' => ['view' => 'archived'], 'expected' => 2])
            ->assertSessionHas('success', 'Restored 2 of 2 selected job(s).');
        $this->assertSame(0, UpworkOpportunity::where('status', 'archived')->count());
    }

    // ---- Authorization and input safety ------------------------------------------------------

    public function test_archive_and_bulk_need_upwork_access_and_valid_input(): void
    {
        $o = $this->opportunity();

        $this->post(route('upwork.opportunities.archive', $o))->assertRedirect(route('login'));
        $this->post(route('upwork.opportunities.bulk'), ['action' => 'archive', 'mode' => 'ids', 'ids' => [$o->id]])->assertRedirect(route('login'));

        $dev = $this->developer();
        $this->actingAs($dev)->post(route('upwork.opportunities.archive', $o))->assertForbidden();
        $this->actingAs($dev)->post(route('upwork.opportunities.restore', $o))->assertForbidden();
        $this->actingAs($dev)->post(route('upwork.opportunities.bulk'), ['action' => 'archive', 'mode' => 'ids', 'ids' => [$o->id]])->assertForbidden();
        $this->assertSame('new', $o->fresh()->status);

        $bde = $this->bde();
        $this->actingAs($bde)->post(route('upwork.opportunities.bulk'), ['action' => 'delete', 'mode' => 'ids', 'ids' => [$o->id]])->assertSessionHasErrors('action');
        $this->actingAs($bde)->post(route('upwork.opportunities.bulk'), ['action' => 'archive', 'mode' => 'ids', 'ids' => ['1 OR 1=1']])->assertSessionHasErrors('ids.0');
        $this->actingAs($bde)->post(route('upwork.opportunities.bulk'), ['action' => 'archive', 'mode' => 'ids'])->assertSessionHasErrors('ids');
        $this->actingAs($bde)->post(route('upwork.opportunities.bulk'), ['action' => 'archive', 'mode' => 'filter', 'filter' => []])->assertSessionHasErrors('expected');
        $this->actingAs($bde)->post(route('upwork.opportunities.archive', 999999))->assertNotFound();
        $this->assertSame('new', $o->fresh()->status);
        $this->assertSame(1, UpworkOpportunity::count(), 'nothing is ever deleted');
    }

    // ---- Existing workflow unaffected ----------------------------------------------------------

    public function test_approve_reject_and_snooze_still_work_for_active_jobs(): void
    {
        $bde = $this->bde();
        $approve = $this->eligible();
        $reject = $this->eligible();
        $snooze = $this->eligible();

        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $approve))->assertSessionHas('success');
        $this->actingAs($bde)->post(route('upwork.opportunities.reject', $reject), ['rejection_reason' => 'Too small'])->assertSessionHas('success');
        $this->actingAs($bde)->post(route('upwork.opportunities.snooze', $snooze), ['preset' => '1h'])->assertSessionHas('success');

        $this->assertSame(['approved', 'rejected', 'new'], [$approve->fresh()->status, $reject->fresh()->status, $snooze->fresh()->status]);
        $this->assertNotNull($approve->fresh()->reviewed_at);
        $this->assertNotNull($snooze->fresh()->review_snoozed_until);

        // Approved and rejected jobs can still be archived later, and come back as they were.
        $this->service()->archive($reject->fresh(), $bde->id);
        $this->assertSame('rejected', $this->service()->restore($reject->fresh(), $bde->id));
        $this->assertSame('Too small', $reject->fresh()->rejection_reason);
    }
}
