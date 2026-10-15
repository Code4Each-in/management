<?php

namespace Tests\Feature\Upwork;

use App\Jobs\Upwork\GenerateProposalDraft;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\Proposal\ProposalDraftService;
use App\Services\Upwork\Eligibility\LocationEligibility;
use App\Services\Upwork\RadarService;
use App\Services\Upwork\Review\ReviewSlaService;
use App\Services\Upwork\UpworkOpportunityService;
use App\Services\Upwork\UpworkProposalService;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Location eligibility: jobs India-based freelancers cannot apply to are held
 * at SKIP (status untouched) and kept out of review, reminders, notifications
 * and proposals; unclear ones stay in review, flagged. Nothing here contacts Upwork.
 */
class UpworkLocationEligibilityTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        UpworkSetting::set('review.reminders_enabled', true);
        UpworkSetting::set('notifications.channels', ['log']);
    }

    private function service(): UpworkOpportunityService
    {
        return app(UpworkOpportunityService::class);
    }

    /** Agent payload (scored 86 / APPLY) whose posting requires a freelancer location. */
    private function located(?string $location, array $overrides = []): array
    {
        $raw = ['source' => 'test'];
        if ($location !== null) {
            $raw['preferred_qualifications'] = ['location' => $location];
        }

        return $this->payload(array_merge(['raw_data' => $raw], $overrides));
    }

    private function reminders(UpworkOpportunity $o): int
    {
        return $o->logs()->where('action', 'review_reminder')->count();
    }

    // ---- Rules --------------------------------------------------------------------------

    /** @dataProvider locations */
    public function test_location_rules(?string $text, string $expected, string $rule): void
    {
        $result = LocationEligibility::classify($text);

        $this->assertSame([$expected, $rule], [$result['eligibility'], $result['rule']], var_export($text, true));
    }

    public function locations(): array
    {
        return [
            // Restricted: US, the Americas, other countries, residence requirements, India excluded.
            'United States' => ['United States', 'restricted', 'restricted_us'],
            'US only' => ['US only', 'restricted', 'restricted_us'],
            'Remote - USA' => ['Remote - USA', 'restricted', 'restricted_us'],
            'U.S. only' => ['U.S. only', 'restricted', 'restricted_us'],
            'Must be located in the US' => ['Must be located in the United States', 'restricted', 'restricted_us'],
            'Americas only' => ['Americas only', 'restricted', 'restricted_americas'],
            'North America only' => ['North America only', 'restricted', 'restricted_americas'],
            'Must reside in Canada' => ['Applicants must reside in Canada', 'restricted', 'restricted_americas'],
            'UK residents only' => ['UK residents only', 'restricted', 'restricted_region'],
            'Southeast Asia only' => ['Southeast Asia only', 'restricted', 'restricted_region'],
            'Worldwide except India' => ['Worldwide except India', 'restricted', 'india_excluded'],
            'Asia excluding India' => ['Asia (excluding India)', 'restricted', 'india_excluded'],
            'APAC except India' => ['APAC except India', 'restricted', 'india_excluded'],
            'No applicants from India' => ['No applicants from India', 'restricted', 'india_excluded'],
            'India not eligible' => ['India not eligible', 'restricted', 'india_excluded'],
            // Eligible: India, regions with India, worldwide, blank.
            'India' => ['India', 'eligible', 'india_included'],
            'Remote - India' => ['Remote - India', 'eligible', 'india_included'],
            'Worldwide incl. India' => ['Worldwide, including India', 'eligible', 'india_included'],
            'Open to India' => ['Open to freelancers in India', 'eligible', 'india_included'],
            'USA or India' => ['USA, India', 'eligible', 'india_included'],
            'Asia' => ['Asia', 'eligible', 'india_region'],
            'South Asia' => ['South Asia', 'eligible', 'india_region'],
            'APAC' => ['APAC', 'eligible', 'india_region'],
            'Worldwide' => ['Worldwide', 'eligible', 'worldwide'],
            'Remote - Worldwide' => ['Remote - Worldwide', 'eligible', 'worldwide'],
            'Anywhere' => ['Anywhere', 'eligible', 'worldwide'],
            'blank' => ['', 'eligible', 'blank'],
            'whitespace' => ['   ', 'eligible', 'blank'],
            'null' => [null, 'eligible', 'blank'],
            'Remote' => ['Remote', 'eligible', 'no_restriction_stated'],
            // Client location is not a freelancer requirement.
            'US client, worldwide' => ['US-based client, open to freelancers worldwide', 'eligible', 'worldwide'],
            'client in US only stated' => ['Client based in United States', 'eligible', 'client_location_only'],
            'client in US, must be in US' => ['Client based in United States, must be located in the US', 'restricted', 'restricted_us'],
            // Unclear: kept for manual review.
            'preference' => ['US preferred', 'unknown', 'unrecognized'],
            'time zone' => ['EST time zone overlap', 'unknown', 'unrecognized'],
            'Indiana is not India' => ['Indiana only', 'unknown', 'unrecognized'],
            'us the pronoun' => ['Work with us remotely', 'unknown', 'unrecognized'],
            'worldwide and US only' => ['Worldwide; US only', 'unknown', 'worldwide_conflict'],
            'Asia and Japan only' => ['Asia, must be located in Japan', 'unknown', 'india_region_conflict'],
            'excluded and welcomed' => ['Except Pakistan, India welcome', 'unknown', 'india_conflict'],
        ];
    }

    /** Upwork's structured list (find_jobs get: preferred_locations), as on the job reported in production. */
    public function test_upwork_location_list_and_its_required_flag(): void
    {
        $eval = fn(array $block) => LocationEligibility::evaluate(['preferred_qualifications' => ['english_proficiency' => 'Any'], 'preferred_locations' => $block]);

        $preferred = $eval(['countries' => ['Philippines', 'Argentina', 'Spain'], 'location_required' => false]);
        $this->assertSame(['unknown', 'location_preferred', 'preferred_locations'], [$preferred['eligibility'], $preferred['rule'], $preferred['source']]);
        $this->assertSame('Philippines, Argentina, Spain (preferred, not required)', $preferred['text']);
        $this->assertSame(LocationEligibility::PREFERRED_REASON, $preferred['reason']);

        $required = $eval(['countries' => ['Philippines', 'Argentina', 'Spain'], 'regions' => ['Europe'], 'location_required' => true]);
        $this->assertSame(['restricted', 'location_required'], [$required['eligibility'], $required['rule']]);
        $this->assertSame('Philippines, Argentina, Spain, Europe (required)', $required['text']);
        $this->assertSame(LocationEligibility::SKIP_REASON, $required['reason']);

        // Any country works for a required list: no dictionary needed.
        $this->assertSame('restricted', $eval(['countries' => ['Bolivia'], 'location_required' => true])['eligibility']);
        $this->assertSame(['eligible', 'india_listed'], array_values(array_intersect_key($eval(['countries' => ['Spain', 'India'], 'location_required' => true]), array_flip(['eligibility', 'rule']))));
        $this->assertSame('india_region', $eval(['regions' => ['Asia'], 'location_required' => true])['rule']);
        $this->assertSame('restricted', $eval(['regions' => ['Southeast Asia'], 'location_required' => true])['eligibility']);
        $this->assertSame('location_list_unflagged', $eval(['countries' => ['Spain']])['rule']);
        // An empty block means "details fetched, no location given".
        $this->assertSame(['eligible', 'blank'], array_values(array_intersect_key($eval([]), array_flip(['eligibility', 'rule']))));
        $this->assertSame(['eligible', 'blank'], array_values(array_intersect_key($eval(['countries' => [], 'location_required' => false]), array_flip(['eligibility', 'rule']))));

        config(['upwork.location.preferred_is_restriction' => true]);
        $this->assertSame(['restricted', LocationEligibility::SKIP_REASON], array_values(array_intersect_key($eval(['countries' => ['Spain'], 'location_required' => false]), array_flip(['eligibility', 'reason']))));
    }

    public function test_location_comes_from_the_freelancer_requirement_never_the_client_country(): void
    {
        $this->assertSame([null, null], LocationEligibility::extract(['source' => 'x']));
        $this->assertSame(['United States', 'preferred_qualifications.location'], LocationEligibility::extract(['preferred_qualifications' => ['location' => 'United States', 'english_proficiency' => 'Any']]));
        $this->assertSame(['United States, Canada', 'preferred_qualifications.countries'], LocationEligibility::extract(['preferred_qualifications' => ['countries' => ['United States', 'Canada']]]));
        $this->assertSame(['Europe, Germany', 'preferred_qualifications.location'], LocationEligibility::extract(['preferred_qualifications' => ['location' => ['regions' => ['Europe'], 'countries' => ['Germany'], 'local_check' => true]]]));
        $this->assertSame(['India', 'freelancer_location'], LocationEligibility::extract(['freelancer_location' => 'India']));

        // A US client with no freelancer restriction: eligible, and the client's country is not read.
        [$o] = $this->service()->ingest($this->located(null, ['client_country' => 'United States']));
        $this->assertSame(['eligible', 'blank', null], [$o->location_eligibility, $o->location_rule, $o->location_text]);
        $this->assertSame('apply', $o->recommendation);

        // A US client open to worldwide freelancers: not restricted.
        [$o] = $this->service()->ingest($this->located('Worldwide', ['upwork_job_id' => '~01worldwide', 'client_country' => 'United States']));
        $this->assertSame(['eligible', 'worldwide'], [$o->location_eligibility, $o->location_rule]);
        $this->assertSame('apply', $o->recommendation);
    }

    // ---- Workflow ---------------------------------------------------------------------

    public function test_a_high_score_never_overrides_a_location_restriction(): void
    {
        [$o] = $this->service()->ingest($this->located('United States only'));
        $o->refresh();

        $this->assertSame(86.0, $o->score, 'the quality score is kept, separately');
        $this->assertSame('skip', $o->recommendation);
        $this->assertSame('apply', $o->location_prior_recommendation);
        $this->assertSame(UpworkOpportunity::STATUS_NEW, $o->status, 'status is human-owned and never changed');
        $this->assertSame(['restricted', 'restricted', 'restricted_us'], [$o->location_eligibility, $o->location_auto_eligibility, $o->location_rule]);
        $this->assertSame(LocationEligibility::SKIP_REASON, $o->location_reason);
        $this->assertSame('United States only', $o->location_text);
        $this->assertSame('preferred_qualifications.location', $o->location_source);
        $this->assertNotNull($o->location_classified_at);
        $this->assertContains(LocationEligibility::SKIP_REASON, $o->hard_filters);
        $this->assertSame(1, $o->logs()->where('action', 'location_classified')->count());

        $this->assertNull($o->review_due_at, 'no 30-minute SLA');
        $this->assertNull($o->notified_at, 'no new-job notification');
        $this->assertFalse(ReviewSlaService::isEligible($o));
        $this->assertSame(0, UpworkOpportunity::reviewEligible()->count());
        $this->assertSame(0, RadarService::view('new_matches')->count());
        $this->assertSame(0, RadarService::view('eligible')->count());
        $this->assertSame(1, RadarService::view('location_restricted')->count());
    }

    public function test_pms_scored_jobs_are_held_at_skip_after_scoring(): void
    {
        (new UpworkIntelligenceSeeder)->run();
        $payload = \Illuminate\Support\Arr::except($this->located('Remote - USA'), ['score', 'recommendation', 'technical_fit_score']);

        [$o] = $this->service()->ingest($payload);
        $o->refresh();

        $this->assertNotNull($o->ruleset_version, 'scored by the PMS engine');
        $this->assertNotNull($o->score);
        $this->assertSame('skip', $o->recommendation);
        $this->assertSame('restricted', $o->location_eligibility);
        $this->assertNull($o->review_due_at);

        // A re-score (e.g. after AI analysis) cannot lift it.
        $this->service()->rescore($o);
        $this->assertSame('skip', $o->fresh()->recommendation);
    }

    public function test_unclear_locations_stay_in_review_flagged_and_logged(): void
    {
        Log::spy();

        [$o] = $this->service()->ingest($this->located('US preferred, EST overlap'));
        $o->refresh();

        $this->assertSame(['unknown', 'unrecognized'], [$o->location_eligibility, $o->location_rule]);
        $this->assertSame(LocationEligibility::UNKNOWN_REASON, $o->location_reason);
        $this->assertSame('apply', $o->recommendation, 'never skipped silently');
        $this->assertNotContains(LocationEligibility::SKIP_REASON, (array) $o->hard_filters);
        $this->assertNotNull($o->review_due_at, 'normal review SLA');
        $this->assertNotNull($o->notified_at);
        $this->assertSame(1, RadarService::view('location_unknown')->count());
        $this->assertSame(1, RadarService::view('eligible')->count());
        Log::shouldHaveReceived('info')->withArgs(fn($message, $context) => $message === '[upwork] location restriction unclear'
            && $context['location_text'] === 'US preferred, EST overlap' && $context['opportunity_id'] === $o->id)->once();

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))->assertOk()
            ->assertSee('Location restriction unclear — manual review required')->assertSee('US preferred, EST overlap');
    }

    public function test_restricted_jobs_never_get_reminders_and_a_running_sla_stops(): void
    {
        [$restricted] = $this->service()->ingest($this->located('UK residents only'));
        // An eligible job, then Upwork adds a US-only requirement after its clock started.
        [$later] = $this->service()->ingest($this->located('Worldwide', ['upwork_job_id' => '~01later']));
        $this->assertNotNull($later->fresh()->review_due_at);
        $this->service()->ingest($this->located('US only', ['upwork_job_id' => '~01later']));

        for ($i = 0; $i < 8; $i++) {
            $this->travel(10)->minutes();
            app(ReviewSlaService::class)->run();
        }

        $this->assertSame(0, $this->reminders($restricted->fresh()));
        $this->assertSame(0, $this->reminders($later->fresh()));
        $this->assertNull($later->fresh()->review_overdue_at);
        $this->assertSame(0, UpworkOpportunity::reviewOverdue()->count());
        // The atomic claim refuses too, even if a stale copy reached it.
        $this->assertFalse(app(ReviewSlaService::class)->claimReminder($later->fresh(), 9, now()));
    }

    public function test_restricted_jobs_cannot_be_approved_or_get_any_proposal(): void
    {
        UpworkSetting::set('ai.enabled', true);
        UpworkSetting::set('ai.proposal_drafts_enabled', true);
        UpworkSetting::set('ai.provider', 'fake');
        [$o] = $this->service()->ingest($this->located('North America only'));
        $bde = $this->bde();

        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $o))->assertSessionHas('error');
        $this->assertSame('new', $o->fresh()->status);
        $this->actingAs($bde)->get(route('upwork.opportunities.show', $o))->assertOk()->assertSee('Not approvable');

        // Approved before the restriction was known (e.g. by the backfill): no draft of any kind.
        $approved = $this->opportunity(['status' => 'approved', 'raw_data' => ['preferred_qualifications' => ['location' => 'US only']]]);
        $this->service()->classifyLocation($approved, true);
        $approved->refresh();
        $this->assertSame('approved', $approved->status);

        $this->assertStringContainsString('India-based applicants are not eligible', app(ProposalDraftService::class)->request($approved, $bde->id));
        $this->assertFalse(app(ProposalDraftService::class)->queueAfterApproval($approved, $bde->id));
        (new GenerateProposalDraft($approved->id, null))->handle(app(ProposalDraftService::class));
        $this->assertSame(UpworkOpportunity::PROPOSAL_AI_SKIPPED, $approved->fresh()->proposal_ai_status);
        $this->assertSame(0, \App\Models\UpworkAiUsage::count(), 'no AI request was paid for');

        $this->actingAs($bde)->post(route('upwork.opportunities.proposal.generate', $approved))->assertSessionHas('error');
        $this->expectException(\DomainException::class);
        app(UpworkProposalService::class)->storeAgentDraft($approved->fresh(), ['proposal_draft' => 'Hi'], $bde->id);
    }

    public function test_existing_statuses_are_preserved_on_rediscovery_and_backfill(): void
    {
        $bde = $this->bde();
        $ids = [];
        foreach (['approved', 'rejected', 'submitted', 'won'] as $i => $status) {
            [$o] = $this->service()->ingest($this->located(null, ['upwork_job_id' => "~01status{$i}"]));
            $o->forceFill(['status' => $status, 'approved_by' => $bde->id, 'approved_at' => now()])->save();
            $ids[$status] = $o->id;
        }
        [$archived] = $this->service()->ingest($this->located(null, ['upwork_job_id' => '~01statusarch']));
        $this->service()->archive($archived, $bde->id);
        $ids['archived'] = $archived->id;

        // Upwork now shows a US-only requirement on all of them.
        foreach (array_keys($ids) as $i => $status) {
            $key = $status === 'archived' ? 'arch' : $i;
            $this->service()->ingest($this->located('US only', ['upwork_job_id' => "~01status{$key}"]));
        }
        Artisan::call('upwork:classify-locations', ['--apply' => true]);

        foreach ($ids as $status => $id) {
            $o = UpworkOpportunity::find($id);
            $this->assertSame($status, $o->status, "{$status} is kept");
            $this->assertSame('restricted', $o->location_eligibility);
        }
        $this->assertSame('new', UpworkOpportunity::find($ids['archived'])->archived_from_status);
        $this->assertNotNull(UpworkOpportunity::find($ids['approved'])->approved_at);
    }

    public function test_duplicate_discovery_creates_no_new_record_and_keeps_review_history(): void
    {
        $bde = $this->bde();
        [$o] = $this->service()->ingest($this->located('Remote - India'));
        $this->service()->reject($o->fresh(), $bde->id, 'Not our stack');
        $logs = $o->logs()->count();

        // Seen again by search and full details, under both id forms, unchanged.
        $this->travel(5)->minutes();
        $this->service()->ingestSighting($this->located('Remote - India'));
        $this->service()->ingest($this->located('Remote - India', ['upwork_job_id' => '01abc123def456']));
        $this->service()->ingest($this->located('Remote - India'));

        $this->assertSame(1, UpworkOpportunity::count());
        $o->refresh();
        $this->assertSame(['rejected', 'Not our stack', $bde->id], [$o->status, $o->rejection_reason, $o->rejected_by]);
        $this->assertSame($logs, $o->logs()->count(), 'unchanged rediscovery logs nothing');
        $this->assertSame(1, $o->logs()->where('action', 'location_classified')->count());

        // A rediscovered restricted job is not reclassified or re-logged either.
        [$r] = $this->service()->ingest($this->located('US only', ['upwork_job_id' => '~01dupe']));
        $classifiedAt = $r->fresh()->location_classified_at;
        $this->travel(5)->minutes();
        $this->service()->ingest($this->located('US only', ['upwork_job_id' => '~01dupe']));
        $this->assertEquals($classifiedAt, $r->fresh()->location_classified_at);
        $this->assertSame(1, $r->logs()->where('action', 'location_classified')->count());
        $this->assertSame(2, UpworkOpportunity::count());
    }

    // ---- Manual override ----------------------------------------------------------------

    public function test_manual_override_is_persisted_audited_and_keeps_the_original(): void
    {
        [$o] = $this->service()->ingest($this->located('United States'));
        $bde = $this->bde();

        $this->actingAs($bde)->post(route('upwork.opportunities.location', $o), ['classification' => 'eligible'])
            ->assertSessionHasErrors('note');
        $this->actingAs($this->developer())->post(route('upwork.opportunities.location', $o), ['classification' => 'eligible', 'note' => 'x'])
            ->assertForbidden();

        $this->actingAs($bde)->post(route('upwork.opportunities.location', $o), ['classification' => 'eligible', 'note' => 'Client confirmed India is fine'])
            ->assertSessionHas('success');
        $o->refresh();
        $this->assertSame(['eligible', 'eligible', $bde->id, 'Client confirmed India is fine'],
            [$o->location_eligibility, $o->location_override, $o->location_override_by, $o->location_override_note]);
        $this->assertSame(['United States', 'restricted', 'restricted_us'], [$o->location_text, $o->location_auto_eligibility, $o->location_rule], 'original kept');
        $this->assertSame('apply', $o->recommendation, "scoring's recommendation comes back");
        $this->assertNotContains(LocationEligibility::SKIP_REASON, (array) $o->hard_filters);
        $this->assertNotNull($o->review_due_at, 'back in review with a deadline');
        $log = $o->logs()->where('action', 'location_override')->first();
        $this->assertSame($bde->id, $log->user_id);
        $this->assertStringContainsString('Client confirmed India is fine', $log->note);
        $this->assertStringContainsString('Before: Restricted', $log->note);

        // Rediscovery keeps the reviewer's decision; approval now works.
        $this->service()->ingest($this->located('United States'));
        $this->assertSame('eligible', $o->fresh()->location_eligibility);
        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $o))->assertSessionHas('success');
        $this->assertSame('approved', $o->fresh()->status);

        // Removing the override restores the rules' answer (status untouched).
        $this->actingAs($bde)->post(route('upwork.opportunities.location', $o), ['classification' => 'rules'])->assertSessionHas('success');
        $o->refresh();
        $this->assertSame(['restricted', null, 'approved', 'skip'], [$o->location_eligibility, $o->location_override, $o->status, $o->recommendation]);
        $this->assertSame(2, $o->logs()->where('action', 'location_override')->count());
    }

    public function test_override_can_restrict_a_job_the_rules_let_through(): void
    {
        [$o] = $this->service()->ingest($this->located('EST hours, local candidates'));
        $this->assertSame('unknown', $o->fresh()->location_eligibility);

        $this->service()->overrideLocation($o, 'restricted', $this->bde()->id, 'Posting says US citizens only');

        $o->refresh();
        $this->assertSame(['restricted', 'unknown', 'skip'], [$o->location_eligibility, $o->location_auto_eligibility, $o->recommendation]);
        $this->assertSame(0, UpworkOpportunity::reviewEligible()->count());
    }

    // ---- Backfill -----------------------------------------------------------------------

    public function test_backfill_dry_run_changes_nothing_and_apply_is_idempotent(): void
    {
        $restricted = $this->opportunity(['title' => 'US-only CRM build', 'recommendation' => 'apply', 'score' => 90,
            'raw_data' => ['preferred_qualifications' => ['location' => 'US only']]]);
        $unclear = $this->opportunity(['title' => 'Unclear one', 'raw_data' => ['preferred_qualifications' => ['location' => 'Must overlap EST']]]);
        $blank = $this->opportunity(['title' => 'No location']);
        $already = $this->opportunity(['title' => 'Already done']);
        $this->service()->classifyLocation($already, true);
        $snapshot = fn() => UpworkOpportunity::orderBy('id')->get()->map(fn($o) => $o->getAttributes())->all();
        $before = $snapshot();
        $logs = \App\Models\UpworkOpportunityLog::count();

        Artisan::call('upwork:classify-locations');
        $out = Artisan::output();

        $this->assertSame($before, $snapshot(), 'dry run saves nothing');
        $this->assertSame($logs, \App\Models\UpworkOpportunityLog::count());
        $this->assertStringContainsString('Dry run: 3 of 4 job(s) would change', $out);
        foreach (['US-only CRM build', $restricted->upwork_job_id, 'US only', 'skip: apply -> skip (status new unchanged)', LocationEligibility::SKIP_REASON, 'flag for manual review'] as $expected) {
            $this->assertStringContainsString($expected, $out);
        }
        $this->assertStringNotContainsString('Already done', $out, 'unchanged rows are not listed by default');

        Artisan::call('upwork:classify-locations', ['--apply' => true]);
        $this->assertStringContainsString('Applied: 3 of 4', Artisan::output());
        $this->assertSame(['restricted', 'skip', 'new'], [$restricted->fresh()->location_eligibility, $restricted->fresh()->recommendation, $restricted->fresh()->status]);
        $this->assertSame(['unknown', 'review'], [$unclear->fresh()->location_eligibility, $unclear->fresh()->recommendation]);
        $this->assertSame('eligible', $blank->fresh()->location_eligibility);
        $this->assertSame(4, UpworkOpportunity::count());
        $this->assertNull($restricted->fresh()->notified_at, 'the backfill sends nothing');

        $after = $snapshot();
        Artisan::call('upwork:classify-locations', ['--apply' => true]);
        $this->assertStringContainsString('Applied: 0 of 4', Artisan::output());
        $this->assertSame($after, $snapshot(), 'second run is a no-op');
    }

    // ---- Dashboard ----------------------------------------------------------------------

    public function test_list_filters_counts_and_location_display(): void
    {
        $this->service()->ingest($this->located('US only', ['title' => 'Restricted job']));
        $this->service()->ingest($this->located('India', ['upwork_job_id' => '~01india', 'title' => 'India job']));
        $this->service()->ingest($this->located('US preferred', ['upwork_job_id' => '~01unclear', 'title' => 'Unclear job']));
        [$archived] = $this->service()->ingest($this->located(null, ['upwork_job_id' => '~01arch', 'title' => 'Archived job']));
        $bde = $this->bde();
        $this->service()->archive($archived, $bde->id);

        $stats = $this->service()->stats();
        $this->assertSame([4, 1, 1, 1, 0, 1], [$stats['all'], $stats['location_eligible'], $stats['location_restricted'], $stats['location_unknown'], $stats['location_unclassified'], $stats['archived']]);
        $this->assertSame([4, 2, 1, 1, 1], [RadarService::view('all_jobs')->count(), RadarService::view('eligible')->count(),
            RadarService::view('location_restricted')->count(), RadarService::view('location_unknown')->count(), RadarService::view('archived')->count()]);

        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['view' => 'location_restricted']))->assertOk()
            ->assertSee('Restricted job')->assertDontSee('India job')->assertSee(LocationEligibility::SKIP_REASON)->assertSee('Skipped: location');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['view' => 'eligible']))->assertOk()
            ->assertSee('India job')->assertSee('Unclear job')->assertDontSee('Restricted job');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['location' => 'restricted']))->assertOk()
            ->assertSee('Restricted job')->assertDontSee('Unclear job');
        $this->actingAs($bde)->get(route('upwork.opportunities.index'))->assertOk()
            ->assertDontSee('Restricted job')->assertSee('Unclear job')->assertSee('Location restriction unclear');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['view' => 'all_jobs']))->assertOk()
            ->assertSee('Restricted job')->assertSee('Archived job');
    }
}
