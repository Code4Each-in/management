<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Discovery\ServerDiscoveryRunner;
use App\Services\Upwork\Review\ReviewSlaService;
use App\Services\Upwork\UpworkOpportunityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * Thirty-minute review SLA: deadline set once, reminders at 10/20/25 min,
 * overdue at 30, every 10 min after; per-job snooze; nothing automatic
 * beyond reminders. Plus hourly discovery scheduling and status.
 */
class UpworkReviewSlaTest extends UpworkTestCase
{
    private function sla(): ReviewSlaService
    {
        return app(ReviewSlaService::class);
    }

    /** An enriched APPLY job, first seen now (or at $firstSeen), with its review clock started. */
    private function eligible(array $attributes = [], ?Carbon $firstSeen = null): UpworkOpportunity
    {
        $o = $this->opportunity(array_merge(['recommendation' => 'apply', 'score' => 86], $attributes));
        $o->forceFill(['stage' => UpworkOpportunity::STAGE_ENRICHED, 'first_seen_at' => $firstSeen ?? now()])->save();
        $this->sla()->startIfEligible($o);

        return $o->fresh();
    }

    private function reminders(UpworkOpportunity $o): int
    {
        return $o->logs()->where('action', 'review_reminder')->count();
    }

    private function remindersOn(): void
    {
        UpworkSetting::set('review.reminders_enabled', true);
        UpworkSetting::set('notifications.channels', ['log']);
    }

    // ---- Deadline -------------------------------------------------------------------------------

    public function test_first_seen_and_deadline_are_set_once_when_an_eligible_job_is_stored(): void
    {
        $service = app(UpworkOpportunityService::class);
        [$o, $created] = $service->ingest($this->payload());
        $this->assertTrue($created);
        $o->refresh();

        $this->assertSame(now()->timestamp, $o->first_seen_at->timestamp);
        $this->assertSame(now()->addMinutes(30)->timestamp, $o->review_due_at->timestamp);
        $this->assertSame(1, $o->logs()->where('action', 'review_sla_started')->count());

        // Seen again later with changes: same row, same first_seen_at, same deadline.
        $this->travel(12)->minutes();
        [$again, $createdAgain] = $service->ingest($this->payload(['upwork_job_id' => '01abc123def456', 'proposals_count' => 20]));
        $again->refresh();

        $this->assertFalse($createdAgain);
        $this->assertSame(1, UpworkOpportunity::count(), 'deduplicated by job id');
        $this->assertSame($o->first_seen_at->timestamp, $again->first_seen_at->timestamp);
        $this->assertSame($o->review_due_at->timestamp, $again->review_due_at->timestamp);
        $this->assertSame(1, $again->logs()->where('action', 'review_sla_started')->count());
    }

    public function test_numeric_and_ciphertext_ids_are_one_job_with_one_deadline(): void
    {
        $service = app(UpworkOpportunityService::class);
        $service->ingest($this->payload(['upwork_job_id' => '~022107561771980939922']));
        $this->travel(3)->minutes();
        $service->ingest($this->payload(['upwork_job_id' => '2107561771980939922']));

        $this->assertSame(1, UpworkOpportunity::count());
        $this->assertSame(now()->subMinutes(3)->addMinutes(30)->timestamp, UpworkOpportunity::first()->review_due_at->timestamp);
    }

    public function test_ineligible_jobs_get_no_deadline(): void
    {
        $this->assertNull($this->eligible(['recommendation' => 'skip'])->review_due_at);
        $this->assertNull($this->eligible(['hard_filters' => ['Client wants individuals only']])->review_due_at);
        $this->assertNull($this->eligible(['status' => 'approved'])->review_due_at);

        $discovered = $this->opportunity(['recommendation' => 'apply']);
        $discovered->forceFill(['stage' => UpworkOpportunity::STAGE_DISCOVERED, 'first_seen_at' => now()])->save();
        $this->assertFalse($this->sla()->startIfEligible($discovered), 'search rows wait for full details');
    }

    public function test_an_old_job_that_becomes_eligible_late_does_not_arrive_overdue(): void
    {
        $o = $this->eligible([], now()->subHours(3));

        $this->assertSame(now()->addMinutes(30)->timestamp, $o->review_due_at->timestamp);
    }

    // ---- Reminders ------------------------------------------------------------------------------

    public function test_reminders_follow_the_schedule_and_overdue_never_changes_the_job(): void
    {
        $this->remindersOn();
        $start = now()->copy();
        $o = $this->eligible();

        $expected = [5 => 0, 10 => 1, 15 => 1, 20 => 2, 25 => 3, 29 => 3, 30 => 4, 35 => 4, 40 => 5, 50 => 6];
        foreach ($expected as $minute => $count) {
            $this->travelTo($start->copy()->addMinutes($minute)->addSeconds(5));
            $this->sla()->run();
            $this->assertSame($count, $this->reminders($o), "after {$minute} minutes");
        }

        $o->refresh();
        $this->assertSame(UpworkOpportunity::STATUS_NEW, $o->status, 'overdue jobs are never approved, rejected or archived');
        $this->assertNull($o->approved_at);
        $this->assertNull($o->rejected_at);
        $this->assertNull($o->proposal_draft);
        $this->assertSame($start->copy()->addMinutes(30)->timestamp, $o->review_due_at->timestamp, 'deadline never moves');
        $this->assertSame($start->copy()->addMinutes(30)->timestamp, $o->review_overdue_at->copy()->second(0)->timestamp);
        $this->assertSame(1, $o->logs()->where('action', 'review_overdue')->count());
        $this->assertSame(6, $o->review_reminder_stage);
    }

    public function test_each_reminder_is_sent_once_and_missed_ticks_do_not_burst(): void
    {
        $this->remindersOn();
        $o = $this->eligible();

        $this->travel(11)->minutes();
        $this->assertSame(1, $this->sla()->run()['sent']);
        $this->assertSame(0, $this->sla()->run()['sent'], 'a second runner in the same minute sends nothing');
        $this->assertSame(0, app(ReviewSlaService::class)->run()['sent']);

        // Scheduler down for half an hour: one catch-up reminder, not five.
        $this->travel(34)->minutes();
        $this->assertSame(1, $this->sla()->run()['sent']);
        $this->assertSame(2, $this->reminders($o));
        $this->assertSame(5, $o->fresh()->review_reminder_stage);
    }

    public function test_reminders_stop_only_after_an_explicit_approval_or_rejection(): void
    {
        $this->remindersOn();
        $bde = $this->bde();
        $approved = $this->eligible();
        $rejected = $this->eligible();

        $this->travel(11)->minutes();
        $this->sla()->run();
        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $approved))->assertSessionHas('success');
        $this->actingAs($bde)->post(route('upwork.opportunities.reject', $rejected), ['rejection_reason' => 'Not our stack'])->assertSessionHas('success');

        $this->travel(40)->minutes();
        $this->sla()->run();

        foreach ([$approved, $rejected] as $o) {
            $o->refresh();
            $this->assertSame(1, $this->reminders($o));
            $this->assertNotNull($o->reviewed_at);
            $this->assertNull($o->review_overdue_at, 'reviewed in time');
        }
    }

    public function test_reminders_are_off_by_default_but_overdue_is_still_tracked(): void
    {
        $o = $this->eligible();
        $this->travel(31)->minutes();
        $out = $this->sla()->run();

        $this->assertFalse($out['enabled']);
        $this->assertSame(0, $out['sent']);
        $this->assertSame(0, $this->reminders($o));
        $this->assertNotNull($o->fresh()->review_overdue_at);
        $this->assertNull($o->fresh()->review_reminder_stage);
    }

    public function test_a_job_rescored_to_skip_stops_reminding(): void
    {
        $this->remindersOn();
        $o = $this->eligible();
        $this->travel(11)->minutes();
        $this->sla()->run();

        $o->forceFill(['recommendation' => 'skip'])->save();
        $this->travel(30)->minutes();
        $this->sla()->run();

        $this->assertSame(1, $this->reminders($o));
        $this->assertSame('new', $o->fresh()->status);
    }

    public function test_reminder_email_links_to_get_confirmation_pages(): void
    {
        config(['mail.default' => 'array', 'mail.from.address' => 'pms@example.test']);
        UpworkSetting::set('review.reminders_enabled', true);
        UpworkSetting::set('notifications.channels', ['mail']);
        UpworkSetting::set('notifications.mail_to', ['bde@example.test']);
        $o = $this->eligible(['title' => 'Laravel SaaS dashboard']);

        $this->travel(31)->minutes();
        $this->sla()->run();

        $messages = app('mailer')->getSwiftMailer()->getTransport()->messages();
        $this->assertCount(1, $messages, 'one email per due reminder; the overdue level replaces the missed ones');
        $message = $messages->first();
        $body = $message->getBody();
        $this->assertStringContainsString('Upwork review overdue', $message->getSubject());
        foreach (['1h', '4h', 'tomorrow', 'custom'] as $preset) {
            $this->assertStringContainsString(route('upwork.opportunities.snooze.confirm', [$o, 'preset' => $preset]), $body);
        }
        $this->assertStringContainsString('nothing changes until you confirm', $body);
        $this->assertStringContainsString('Nothing has been sent to Upwork', $body);
    }

    // ---- Snooze ---------------------------------------------------------------------------------

    public function test_snoozing_one_job_does_not_silence_others_and_resumes_after_expiry(): void
    {
        $this->remindersOn();
        $bde = $this->bde();
        $start = now()->copy();
        $a = $this->eligible();
        $b = $this->eligible();

        $this->travel(5)->minutes();
        $this->actingAs($bde)->post(route('upwork.opportunities.snooze', $a), ['preset' => '1h'])
            ->assertRedirect(route('upwork.opportunities.show', $a))->assertSessionHas('success');

        foreach ([10, 20, 25, 30, 40, 50, 60] as $minute) {
            $this->travelTo($start->copy()->addMinutes($minute)->addSeconds(5));
            $this->sla()->run();
        }
        $this->assertSame(0, $this->reminders($a), 'snoozed');
        $this->assertSame(7, $this->reminders($b), 'other jobs keep reminding');
        $this->assertSame($start->copy()->addMinutes(30)->timestamp, $a->fresh()->review_due_at->timestamp, 'deadline preserved');
        $this->assertNotNull($a->fresh()->review_overdue_at, 'still marked overdue while snoozed');

        // Snooze ends at +65: one reminder at the current level, then every 10 minutes again.
        $this->travelTo($start->copy()->addMinutes(66));
        $this->sla()->run();
        $this->assertSame(1, $this->reminders($a));
        $this->travelTo($start->copy()->addMinutes(70)->addSeconds(5));
        $this->sla()->run();
        $this->assertSame(2, $this->reminders($a));
    }

    public function test_snooze_presets_use_the_review_timezone(): void
    {
        $sla = $this->sla();
        // Fixed clock: 2026-10-08 12:00 UTC = 17:30 IST.
        config(['upwork.review.timezone' => 'Asia/Kolkata']);
        $this->assertSame('2026-10-08 13:00:00', $sla->snoozeUntil('1h')->utc()->toDateTimeString());
        $this->assertSame('2026-10-08 16:00:00', $sla->snoozeUntil('4h')->utc()->toDateTimeString());
        $this->assertSame('2026-10-09 03:30:00', $sla->snoozeUntil('tomorrow')->utc()->toDateTimeString(), '09:00 IST');
        $this->assertSame('2026-10-08 14:30:00', $sla->snoozeUntil('custom', '2026-10-08T20:00')->utc()->toDateTimeString(), '20:00 IST');

        config(['upwork.review.timezone' => 'America/New_York']);
        $this->assertSame('2026-10-09 13:00:00', $sla->snoozeUntil('tomorrow')->utc()->toDateTimeString(), '09:00 EDT');
        // Across the end of daylight saving time (1 Nov 2026): 09:00 EST is 14:00 UTC.
        $this->travelTo(Carbon::parse('2026-10-31 16:00:00', 'UTC'));
        $this->assertSame('2026-11-01 14:00:00', $sla->snoozeUntil('tomorrow')->utc()->toDateTimeString());

        $this->assertStringContainsString('EST', ReviewSlaService::format(Carbon::parse('2026-11-01 14:00:00', 'UTC')));
    }

    public function test_custom_snooze_times_are_validated(): void
    {
        foreach (['', 'tomorrow', '2026-10-08 20:00', '2026-10-08T10:00', '2026-11-30T10:00'] as $bad) {
            try {
                $this->sla()->snoozeUntil('custom', $bad);
                $this->fail("accepted '{$bad}'");
            } catch (\DomainException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
        $this->expectException(\DomainException::class);
        $this->sla()->snoozeUntil('forever');
    }

    public function test_email_links_never_change_state_on_get(): void
    {
        $bde = $this->bde();
        $o = $this->eligible();

        foreach (['1h', '4h', 'tomorrow', 'custom', 'bogus'] as $preset) {
            $this->actingAs($bde)->get(route('upwork.opportunities.snooze.confirm', [$o, 'preset' => $preset]))
                ->assertOk()->assertSee('Nothing has changed yet')->assertSee('this job only', false);
        }

        $o->refresh();
        $this->assertNull($o->review_snoozed_until);
        $this->assertSame(0, $o->logs()->where('action', 'review_snoozed')->count());
    }

    public function test_snooze_is_post_only_csrf_protected_authorized_and_audited(): void
    {
        $o = $this->eligible();

        $route = Route::getRoutes()->getByName('upwork.opportunities.snooze');
        $this->assertSame(['POST'], $route->methods());
        $this->assertContains('web', $route->gatherMiddleware(), 'web group: session + CSRF');
        $this->assertContains('auth', $route->gatherMiddleware());

        $this->post(route('upwork.opportunities.snooze', $o), ['preset' => '1h'])->assertRedirect();
        $this->assertNull($o->fresh()->review_snoozed_until, 'guests are sent to log in');

        $developer = $this->developer();
        $this->actingAs($developer)->get(route('upwork.opportunities.snooze.confirm', $o))->assertForbidden();
        $this->actingAs($developer)->post(route('upwork.opportunities.snooze', $o), ['preset' => '1h'])->assertForbidden();
        $this->assertNull($o->fresh()->review_snoozed_until);

        $bde = $this->bde();
        $this->actingAs($bde)->post(route('upwork.opportunities.snooze', $o), ['preset' => 'never'])->assertSessionHasErrors('preset');
        $this->actingAs($bde)->post(route('upwork.opportunities.snooze', $o), ['preset' => 'custom'])->assertSessionHasErrors('until');
        $this->actingAs($bde)->post(route('upwork.opportunities.snooze', $o), ['preset' => 'custom', 'until' => '2020-01-01T09:00'])->assertSessionHas('error');
        $this->assertNull($o->fresh()->review_snoozed_until);

        $this->actingAs($bde)->post(route('upwork.opportunities.snooze', $o), ['preset' => '4h'])->assertSessionHas('success');
        $o->refresh();
        $this->assertSame(now()->addHours(4)->timestamp, $o->review_snoozed_until->timestamp);
        $this->assertSame($bde->id, $o->review_snoozed_by);
        $log = $o->logs()->where('action', 'review_snoozed')->first();
        $this->assertSame($bde->id, $log->user_id);
        $this->assertStringContainsString('deadline unchanged', $log->note);

        $this->actingAs($bde)->post(route('upwork.opportunities.unsnooze', $o))->assertSessionHas('success');
        $this->assertNull($o->fresh()->review_snoozed_until);
        $this->assertSame(1, $o->logs()->where('action', 'review_unsnoozed')->where('user_id', $bde->id)->count());

        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $o));
        $this->actingAs($bde)->post(route('upwork.opportunities.snooze', $o), ['preset' => '1h'])->assertSessionHas('error');
        $this->assertNull($o->fresh()->review_snoozed_until, 'reviewed jobs cannot be snoozed');
    }

    public function test_job_page_shows_deadline_and_snooze_controls(): void
    {
        $bde = $this->bde();
        $o = $this->eligible();

        $this->actingAs($bde)->get(route('upwork.opportunities.show', $o))
            ->assertOk()->assertSee('Review due')->assertSee('Snooze 1 hour')->assertSee(route('upwork.opportunities.snooze', $o));

        $this->travel(31)->minutes();
        $this->actingAs($bde)->get(route('upwork.opportunities.show', $o))->assertSee('Review overdue');
    }

    // ---- Hourly discovery -----------------------------------------------------------------------

    public function test_scheduled_discovery_is_off_by_default_and_hourly_when_on(): void
    {
        $this->assertFalse((bool) config('upwork.scheduler.enabled'));
        $this->assertFalse((bool) UpworkSetting::get('agent.enabled'));
        $this->assertSame(60, ServerDiscoveryRunner::minutesBetweenScheduledRuns());
        $this->assertStringContainsString('switched off', app(ServerDiscoveryRunner::class)->run('scheduled')['message']);
        $this->assertSame(0, UpworkDiscoveryRun::count());
    }

    public function test_failed_scheduled_runs_retry_sooner_with_a_doubling_bounded_delay(): void
    {
        $run = fn(string $status, int $minutesAgo) => UpworkDiscoveryRun::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => $status, 'trigger' => 'scheduled',
            'started_at' => now()->subMinutes($minutesAgo), 'finished_at' => now()->subMinutes($minutesAgo),
        ]);

        $run('completed', 200);
        $this->assertSame(60, ServerDiscoveryRunner::minutesBetweenScheduledRuns());
        $run('failed', 150);
        $this->assertSame(10, ServerDiscoveryRunner::minutesBetweenScheduledRuns());
        $run('abandoned', 100);
        $this->assertSame(20, ServerDiscoveryRunner::minutesBetweenScheduledRuns());
        $run('failed', 50);
        $this->assertSame(40, ServerDiscoveryRunner::minutesBetweenScheduledRuns());
        $run('failed', 5);
        $this->assertSame(60, ServerDiscoveryRunner::minutesBetweenScheduledRuns(), 'never later than the interval');
        $this->assertSame(4, ServerDiscoveryRunner::consecutiveScheduledFailures());

        UpworkSetting::set('agent.enabled', true);
        $this->assertStringContainsString('Not due', app(ServerDiscoveryRunner::class)->run('scheduled')['message']);

        $run('completed', 1);
        $this->assertSame(60, ServerDiscoveryRunner::minutesBetweenScheduledRuns(), 'a completed run resets the backoff');
    }

    public function test_status_shows_last_success_and_a_scrubbed_failure_in_the_pms(): void
    {
        UpworkDiscoveryRun::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 'completed', 'trigger' => 'manual',
            'started_at' => now()->subHours(2), 'finished_at' => now()->subHours(2)]);
        UpworkDiscoveryRun::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'status' => 'failed', 'trigger' => 'scheduled',
            'started_at' => now()->subMinutes(30), 'finished_at' => now()->subMinutes(29),
            'error' => 'HTTP 401 with Bearer eyJhbGciOiJIUzI1NiJ9.secretpayload.signature access_token=abc123']);

        $status = ServerDiscoveryRunner::status();
        $this->assertSame('completed', $status['last_success']->status);
        $this->assertSame(1, $status['consecutive_failures']);
        $this->assertStringNotContainsString('secretpayload', $status['last_failure_error']);
        $this->assertStringNotContainsString('abc123', $status['last_failure_error']);

        $this->actingAs($this->superAdmin())->get(route('upwork.config.index'))->assertOk()
            ->assertSee('Last successful run')->assertSee('Last failure')->assertDontSee('secretpayload')->assertDontSee('abc123');
    }

    public function test_reminder_switch_is_saved_and_audited_from_settings(): void
    {
        $admin = $this->superAdmin();
        $form = ['agent_enabled' => 0, 'agent_interval_minutes' => 60, 'agent_lease_minutes' => 15, 'agent_max_backoff_minutes' => 240,
            'agent_enrich_limit_per_run' => 15, 'discovery_max_queries_per_profile' => 4, 'discovery_max_queries_per_run' => 15,
            'discovery_max_searches_per_day' => 400, 'discovery_enrich_min_score' => 55, 'discovery_known_ids_per_profile' => 50,
            'review_reminders_enabled' => 1, 'discovery_retry_after_failure_minutes' => 15];

        $this->actingAs($admin)->put(route('upwork.config.settings.update'), $form)->assertSessionHas('success');

        $this->assertTrue(UpworkSetting::get('review.reminders_enabled'));
        $this->assertSame(15, UpworkSetting::get('discovery.retry_after_failure_minutes'));
        $changes = \App\Models\UpworkConfigLog::latest('id')->first()->changes;
        $this->assertSame([false, true], $changes['review.reminders_enabled']);
        $this->actingAs($this->bde())->put(route('upwork.config.settings.update'), $form)->assertForbidden();
    }
}
