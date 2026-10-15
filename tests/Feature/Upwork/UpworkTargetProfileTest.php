<?php

namespace Tests\Feature\Upwork;

use App\Jobs\Upwork\GenerateProposalDraft;
use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\Proposal\ProposalDraftPrompt;
use App\Services\Upwork\Scoring\ScoringEngine;
use App\Services\Upwork\Targeting\TargetProfiles;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * Dual-track targeting: agency profile vs individual freelancer profiles.
 * Classification is additive: scores, recommendations, hard filters (incl.
 * agency mode), deduplication and the review SLA behave exactly as before,
 * and nothing selects, switches or submits from an Upwork account.
 */
class UpworkTargetProfileTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        (new UpworkIntelligenceSeeder)->run();
    }

    /** Production runs with the agency-only filter switched off; mirror that. */
    private function agencyModeOff(): UpworkRuleset
    {
        $draft = UpworkRuleset::draftOrCreate();
        $draft->update(['settings' => array_merge($draft->settings, ['agency_mode' => false])]);
        $draft->publish(null);

        return UpworkRuleset::current()->load('rules');
    }

    private function job(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Laravel developer for SaaS dashboard',
            'description' => 'Extend our Laravel SaaS platform with a reporting dashboard and Stripe billing.',
            'job_type' => 'hourly', 'hourly_min' => 35, 'hourly_max' => 60,
            'duration' => '1 to 3 months', 'workload' => 'part_time', 'contractor_type' => 'All freelancers',
            'client_rating' => 4.9, 'client_reviews_count' => 30, 'client_total_spent' => 80000, 'client_hire_rate' => 80,
            'client_jobs_posted' => 40, 'client_jobs_hired' => 32, 'payment_verified' => true,
            'proposals_tier' => '5 to 10',
        ], $overrides);
    }

    /** Engine/reviewer-owned columns are not mass-assignable, so set them directly. */
    private function stored(array $attributes, array $owned): UpworkOpportunity
    {
        $o = $this->opportunity($attributes);
        $o->forceFill($owned)->save();

        return $o;
    }

    private function score(array $job, ?UpworkRuleset $ruleset = null): array
    {
        return (new ScoringEngine())->score($job, ($ruleset ?? UpworkRuleset::current())->load('rules'));
    }

    private function teamJob(array $overrides = []): array
    {
        return $this->job(array_merge([
            'title' => 'Dedicated team to build our booking platform',
            'description' => 'We need a dedicated team: a project manager, React frontend, Laravel backend, Flutter app and AWS devops. Long-term, ongoing work.',
            'duration' => 'More than 6 months', 'workload' => 'full_time', 'hourly_max' => 70,
            'raw_data' => ['freelancers_to_hire' => 3],
        ], $overrides));
    }

    private function smallJob(array $overrides = []): array
    {
        return $this->job(array_merge([
            'title' => 'Fix a WordPress plugin bug',
            'description' => 'Our WordPress plugin throws an error on checkout. Need it fixed this week.',
            'job_type' => 'fixed', 'fixed_budget' => 400, 'hourly_min' => null, 'hourly_max' => null,
            'duration' => 'Less than 1 month', 'workload' => null,
        ], $overrides));
    }

    // ---- Classification ---------------------------------------------------------

    public function test_agency_only_posting_targets_the_agency_and_rules_out_individuals(): void
    {
        $result = $this->score($this->job(['contractor_type' => 'Agencies only']));
        $t = $result['target'];

        $this->assertSame('agency', $t['target']);
        $this->assertSame('high', $t['confidence']);
        $this->assertFalse($t['ambiguous']);
        $this->assertSame(['track' => 'agency', 'source' => 'contractor_type'], array_intersect_key($t['restriction'], ['track' => 1, 'source' => 1]));
        $this->assertFalse($t['tracks']['individual']['eligible']);
        $this->assertNull($t['tracks']['individual']['score']);
        $this->assertSame('agency', $t['suggested_profile']);
        $this->assertStringContainsString('Upwork contractor type: Agencies only', $t['summary']);
        // The agency track re-weights the engine's own components with the ruleset weights, so its base IS the score.
        $this->assertEqualsWithDelta($result['score'], $t['tracks']['agency']['base'], 0.05);
    }

    public function test_individual_only_posting_is_kept_scored_and_unchanged_when_agency_mode_is_off(): void
    {
        $ruleset = $this->agencyModeOff();
        $open = $this->score($this->job(), $ruleset);
        $individualOnly = $this->score($this->job(['contractor_type' => 'Individuals only']), $ruleset);

        // Not excluded merely for not being agency-only: same score and recommendation as an open posting.
        $this->assertSame([], $individualOnly['hard_filters']);
        $this->assertSame($open['score'], $individualOnly['score']);
        $this->assertSame($open['recommendation'], $individualOnly['recommendation']);

        $t = $individualOnly['target'];
        $this->assertSame('individual', $t['target']);
        $this->assertSame('high', $t['confidence']);
        $this->assertFalse($t['tracks']['agency']['eligible']);
        $this->assertNotNull($t['tracks']['individual']['score']);
        $this->assertSame(TargetProfiles::GENERIC_INDIVIDUAL, $t['suggested_profile'], 'no individual profiles configured yet');
    }

    public function test_agency_mode_filter_behaviour_is_unchanged_and_the_job_is_still_labelled(): void
    {
        // Seeded default: agency mode on. The hard filter still fires exactly as before.
        $result = $this->score($this->job(['contractor_type' => 'Individuals only']));

        $this->assertSame(['individual_only'], array_column($result['hard_filters'], 'key'));
        $this->assertNull($result['score']);
        $this->assertSame('skip', $result['recommendation']);
        $this->assertSame('individual', $result['target']['target'], 'still classified, from signals only');
        $this->assertNull($result['target']['tracks']['individual']['score']);

        // Stored and shown the same way: hard-rejected, labelled, no review deadline.
        [$o] = app(UpworkOpportunityService::class)->ingest($this->job(['contractor_type' => 'Individuals only', 'upwork_job_id' => '02hard', 'upwork_url' => 'https://www.upwork.com/jobs/~02hard']));
        $o->refresh();
        $this->assertSame('skip', $o->recommendation);
        $this->assertNull($o->review_due_at);
        $this->assertSame('individual', $o->target_profile);
        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))->assertOk()
            ->assertSee('Hard filters triggered')->assertSee('signals only');
    }

    public function test_large_multi_discipline_team_job_suits_the_agency(): void
    {
        $t = $this->score($this->teamJob(), $this->agencyModeOff())['target'];

        $this->assertSame('agency', $t['target']);
        $this->assertFalse($t['ambiguous']);
        $this->assertSame('medium', $t['confidence']);
        $keys = array_column(array_filter($t['signals'], fn($s) => $s['track'] === 'agency' && $s['points'] > 0), 'key');
        $this->assertEqualsCanonicalizing(['scope', 'multiple_hires', 'team_wording', 'multi_discipline'], $keys);
        $reasons = implode("\n", $t['reasons']);
        $this->assertStringContainsString('Client wants to hire 3 freelancers', $reasons);
        $this->assertStringContainsString('Posting asks for a team ("dedicated team")', $reasons);
        $this->assertStringContainsString('Verified agency experience (PROVEN): ', $reasons);
        $this->assertGreaterThan($t['tracks']['individual']['score'], $t['tracks']['agency']['score']);
        $this->assertSame('long_term_partner', $t['suggested_angle']);
    }

    public function test_small_focused_low_budget_job_suits_an_individual(): void
    {
        $t = $this->score($this->smallJob(), $this->agencyModeOff())['target'];

        $this->assertSame('individual', $t['target']);
        $this->assertFalse($t['ambiguous']);
        $byKey = [];
        foreach ($t['signals'] as $s) {
            $byKey[$s['track'] . ':' . $s['key']] = $s;
        }
        $this->assertArrayHasKey('individual:scope', $byKey, 'small scope is an individual preference');
        $this->assertArrayHasKey('individual:focused_skills', $byKey);
        $this->assertSame(-8.0, $byKey['agency:below_preference']['points']);
        $this->assertStringContainsString('Fixed budget $400 is below the agency minimum of $1,000', $byKey['agency:below_preference']['reason']);
        $this->assertSame('quick_turnaround', $t['suggested_angle']);
    }

    public function test_medium_job_without_distinguishing_signals_suits_either(): void
    {
        $t = $this->score($this->job(), $this->agencyModeOff())['target'];

        $this->assertSame('both', $t['target']);
        $this->assertFalse($t['ambiguous'], 'a scope both profiles prefer is agreement, not conflict');
        $this->assertSame('Suitable for either profile', explode(':', $t['summary'])[0]);
        $this->assertLessThan($t['margin'], abs($t['tracks']['agency']['score'] - $t['tracks']['individual']['score']));
    }

    public function test_conflicting_signals_are_ambiguous_and_left_to_a_person(): void
    {
        // Team wording, but a tiny fixed budget and a two-week scope.
        $t = $this->score($this->smallJob([
            'description' => 'Need a dedicated team with a project manager to fix our WordPress plugin checkout error.',
        ]), $this->agencyModeOff())['target'];

        $this->assertTrue($t['ambiguous']);
        $this->assertSame('low', $t['confidence']);
        $this->assertStringContainsString('Signals conflict', implode("\n", $t['reasons']));
    }

    public function test_restriction_contradicted_by_other_signals_is_flagged(): void
    {
        $ruleset = $this->agencyModeOff();

        // Upwork says individuals only; the post asks for a team.
        $t = $this->score($this->teamJob(['contractor_type' => 'Individuals only']), $ruleset)['target'];
        $this->assertSame('individual', $t['target'], 'Upwork\'s own field wins');
        $this->assertTrue($t['ambiguous']);
        $this->assertStringContainsString('but other signals point the other way', implode("\n", $t['reasons']));

        // Upwork says individuals only; the wording says agencies only.
        $t = $this->score($this->job(['contractor_type' => 'Individuals only', 'description' => 'Laravel SaaS dashboard. Agencies only please.']), $ruleset)['target'];
        $this->assertSame('individual', $t['target']);
        $this->assertTrue($t['ambiguous']);
        $this->assertStringContainsString('Contradiction: Upwork contractor type: Individuals only, but posting says "agencies only"', implode("\n", $t['reasons']));
    }

    public function test_no_signals_but_weights_tip_one_way_is_ambiguous(): void
    {
        // Widen the gap by configuration only: the individual track ignores client quality.
        $draft = UpworkRuleset::draftOrCreate();
        $draft->update(['settings' => array_merge($draft->settings, ['agency_mode' => false, 'target_profiles' => [
            'margin' => 1, 'preferences' => ['agency' => ['scopes' => []], 'individual' => ['scopes' => []]],
            'weights' => ['individual' => ['technical_fit' => 1, 'experience_fit' => 0, 'client_quality' => 0, 'commercial_value' => 0, 'long_term' => 0, 'ai_automation' => 0, 'competition' => 0]],
        ]])]);
        $draft->publish(null);

        $t = $this->score($this->job())['target'];

        $this->assertNotSame('both', $t['target']);
        $this->assertTrue($t['ambiguous']);
        $this->assertStringContainsString('only the track weights do', implode("\n", $t['reasons']));
        $this->assertEquals(100.0, array_sum($t['tracks']['individual']['weights']), 'custom weights are normalized');
    }

    public function test_individual_profiles_use_their_own_verified_experience(): void
    {
        UpworkSetting::set(TargetProfiles::SETTING, TargetProfiles::normalize([
            ['name' => 'Asha', 'summary' => 'Laravel and Vue.js developer.', 'technologies' => ['laravel', 'vuejs', 'mysql']],
            ['name' => 'Ben', 'summary' => 'WordPress specialist.', 'technologies' => ['wordpress', 'php', 'not-a-tech']],
        ]));
        $this->assertSame(['wordpress', 'php'], TargetProfiles::individuals()['ben']['technologies'], 'unknown slugs are dropped');

        $t = $this->score($this->smallJob(), $this->agencyModeOff())['target'];

        $this->assertSame('individual', $t['target']);
        $this->assertSame('individual:ben', $t['suggested_profile']);
        $this->assertSame('ben', $t['tracks']['individual']['profile']);
        $this->assertSame('profile', $t['tracks']['individual']['experience']['source']);
        $this->assertStringContainsString('Best individual match: Ben — verified WordPress', $t['tracks']['individual']['experience']['detail']);
    }

    // ---- Storage, dedup, preservation -------------------------------------------

    public function test_ingestion_stores_the_target_and_keeps_dedup_scoring_and_sla_intact(): void
    {
        $this->agencyModeOff();
        $service = app(UpworkOpportunityService::class);
        $payload = $this->smallJob(['upwork_job_id' => '~022000000000000000001', 'upwork_url' => 'https://www.upwork.com/jobs/~022000000000000000001']);

        [$o, $created] = $service->ingest($payload);
        $this->assertTrue($created);
        $o->refresh();
        $this->assertSame('individual', $o->target_profile);
        $this->assertNotNull($o->individual_fit_score);
        $this->assertSame('individual', $o->score_explanation['target']['target']);
        $engine = $this->score($payload);
        $this->assertEquals($engine['score'], $o->score, 'stored score is the engine score, untouched by targeting');
        $this->assertSame($engine['recommendation'], $o->recommendation);

        // Same job again (other id form): one row, nothing changed, nothing logged as an update.
        [$again, $createdAgain] = $service->ingest(array_merge($payload, ['upwork_job_id' => '2000000000000000001']));
        $this->assertFalse($createdAgain);
        $this->assertSame($o->id, $again->id);
        $this->assertSame(1, UpworkOpportunity::count());
        $this->assertSame(0, $o->logs()->where('action', 'updated')->count());
    }

    // ---- Approval: profile and angle ---------------------------------------------

    public function test_approval_records_the_chosen_profile_and_angle_without_touching_upwork(): void
    {
        Queue::fake();
        UpworkSetting::set('ai.enabled', true);
        UpworkSetting::set('ai.provider', 'fake');
        UpworkSetting::set('ai.proposal_drafts_enabled', true);
        UpworkSetting::set('mcp.accounts', [['org_uid' => '1', 'name' => 'Code4Each', 'role' => 'FL_AGENCY']]);
        $this->agencyModeOff();
        [$o] = app(UpworkOpportunityService::class)->ingest($this->teamJob(['upwork_job_id' => '02team', 'upwork_url' => 'https://www.upwork.com/jobs/~02team']));
        $bde = $this->bde();

        $this->actingAs($bde)->get(route('upwork.opportunities.show', $o))->assertOk()
            ->assertSee('Best-fit profile')->assertSee('Bid as')->assertSee('Agency profile (Code4Each) — suggested', false);

        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $o), [
            'selected_profile' => 'agency', 'proposal_angle' => 'team_delivery', 'proposal_angle_note' => 'Lead with the booking engine work',
        ])->assertSessionHas('success');

        $o->refresh();
        $this->assertSame(UpworkOpportunity::STATUS_APPROVED, $o->status);
        $this->assertSame(['agency', 'team_delivery', 'Lead with the booking engine work', $bde->id],
            [$o->selected_profile, $o->proposal_angle, $o->proposal_angle_note, $o->profile_selected_by]);
        $log = $o->logs()->where('action', 'target_selected')->first();
        $this->assertSame('Bid as Agency profile (Code4Each); angle: Team delivery', $log->note);
        Queue::assertPushed(GenerateProposalDraft::class, 1);

        // No Upwork account was chosen or switched, nothing was sent anywhere.
        $this->assertSame([['org_uid' => '1', 'name' => 'Code4Each', 'role' => 'FL_AGENCY']], UpworkSetting::get('mcp.accounts'));
        Http::assertNothingSent();
        $this->assertNull($o->submitted_at);
    }

    public function test_approval_without_a_choice_records_the_suggestion_as_such(): void
    {
        $this->agencyModeOff();
        [$o] = app(UpworkOpportunityService::class)->ingest($this->smallJob(['upwork_job_id' => '02small', 'upwork_url' => 'https://www.upwork.com/jobs/~02small']));

        $this->actingAs($this->bde())->post(route('upwork.opportunities.approve', $o))->assertSessionHas('success');

        $o->refresh();
        $this->assertSame([TargetProfiles::GENERIC_INDIVIDUAL, 'quick_turnaround'], [$o->selected_profile, $o->proposal_angle]);
        $this->assertStringContainsString('(engine suggestion, not chosen by the reviewer)', $o->logs()->where('action', 'target_selected')->value('note'));
        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))->assertOk()->assertSee('suggestion — please confirm');
    }

    public function test_choosing_a_profile_the_posting_excludes_is_allowed_but_warned(): void
    {
        $this->agencyModeOff();
        [$o] = app(UpworkOpportunityService::class)->ingest($this->job(['contractor_type' => 'Individuals only', 'upwork_job_id' => '02ind', 'upwork_url' => 'https://www.upwork.com/jobs/~02ind']));

        $this->actingAs($this->bde())->post(route('upwork.opportunities.approve', $o), ['selected_profile' => 'agency'])->assertSessionHas('success');

        $this->assertStringContainsString('WARNING: the posting only accepts individuals', $o->logs()->where('action', 'target_selected')->value('note'));
    }

    public function test_invalid_choices_are_refused_and_nothing_changes(): void
    {
        $o = $this->opportunity();
        $bde = $this->bde();

        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $o), ['selected_profile' => 'individual:nobody'])->assertSessionHasErrors('selected_profile');
        $this->actingAs($bde)->post(route('upwork.opportunities.approve', $o), ['selected_profile' => 'agency', 'proposal_angle' => 'bribe'])->assertSessionHasErrors('proposal_angle');
        $this->assertSame(UpworkOpportunity::STATUS_NEW, $o->fresh()->status);
        $this->assertNull($o->fresh()->selected_profile);
    }

    public function test_profile_and_angle_can_change_until_a_person_edits_the_draft(): void
    {
        UpworkSetting::set(TargetProfiles::SETTING, TargetProfiles::normalize([['name' => 'Asha', 'technologies' => ['laravel']]]));
        $o = $this->opportunity(['status' => 'approved']);
        $bde = $this->bde();

        // A developer may not; a BDE may.
        $this->actingAs($this->developer())->post(route('upwork.opportunities.targeting', $o), ['selected_profile' => 'agency'])->assertForbidden();
        $this->actingAs($bde)->post(route('upwork.opportunities.targeting', $o), ['selected_profile' => 'individual:asha', 'proposal_angle' => 'specialist'])
            ->assertSessionHas('success');
        $this->assertSame('individual:asha', $o->fresh()->selected_profile);

        // Starter draft follows the choice.
        $this->actingAs($bde)->post(route('upwork.opportunities.proposal.generate', $o));
        $draft = $o->fresh()->proposal_draft;
        $this->assertStringContainsString('angle: Hands-on specialist', $draft);
        $this->assertStringEndsWith("Best regards,\nAsha", $draft);

        // After a human edit the choice is locked (the text is what matters now).
        $this->actingAs($bde)->put(route('upwork.opportunities.proposal.update', $o), ['proposal_draft' => 'My words']);
        $this->actingAs($bde)->post(route('upwork.opportunities.targeting', $o), ['selected_profile' => 'agency'])->assertSessionHas('error');
        $this->assertSame('individual:asha', $o->fresh()->selected_profile);
        $this->assertSame(1, $o->logs()->where('action', 'target_selected')->count());

        // Not before approval either.
        $this->actingAs($bde)->post(route('upwork.opportunities.targeting', $this->opportunity()), ['selected_profile' => 'agency'])->assertSessionHas('error');
    }

    public function test_ai_draft_for_an_individual_cites_only_that_persons_verified_experience(): void
    {
        UpworkSetting::set('proposals.profile', 'Code4Each agency: booking engines and SaaS.');
        UpworkSetting::set(TargetProfiles::SETTING, TargetProfiles::normalize([['name' => 'Asha', 'summary' => 'Builds Laravel dashboards.', 'technologies' => ['laravel']]]));
        $o = $this->stored(['status' => 'approved', 'description' => 'Laravel dashboard'], ['selected_profile' => 'individual:asha', 'proposal_angle' => 'specialist']);

        $request = (new ProposalDraftPrompt())->build($o, 8000, 2000);
        $this->assertStringContainsString('written in the first person by Asha, an individual freelancer', $request->staticContext);
        $this->assertStringContainsString('not the agency profile', $request->staticContext);
        $this->assertStringContainsString('VERIFIED TECHNOLOGIES (this freelancer has delivered with): Laravel', $request->staticContext);
        $this->assertStringContainsString('Hands-on specialist', $request->staticContext);
        $this->assertStringNotContainsString('booking engines', $request->staticContext, 'agency claims are not the individual\'s');

        $checked = ProposalDraftPrompt::verify(['draft' => 'Hi', 'experience_used' => ['Laravel', 'Booking engines'], 'placeholders' => [], 'questions_for_client' => []], $request->dynamicContext, $o);
        $this->assertSame(['Booking engines'], $checked['verification']['unverified_experience']);

        // Agency with no choice: the prompt is exactly what it was before targeting existed.
        $agency = $this->opportunity(['status' => 'approved', 'description' => 'Laravel dashboard']);
        $this->assertSame((new ProposalDraftPrompt())->staticContext(), (new ProposalDraftPrompt())->staticContext($agency));
        $this->assertStringContainsString('a software development agency (a team, not an individual freelancer)', (new ProposalDraftPrompt())->staticContext($agency));
    }

    // ---- Dashboard filtering and reporting ---------------------------------------

    public function test_list_filters_and_radar_report_by_target_profile(): void
    {
        $this->stored(['title' => 'Agency job'], ['target_profile' => 'agency', 'agency_fit_score' => 80, 'individual_fit_score' => 60]);
        $this->stored(['title' => 'Individual job'], ['target_profile' => 'individual', 'agency_fit_score' => 50, 'individual_fit_score' => 78]);
        $this->stored(['title' => 'Either job'], ['target_profile' => 'both', 'agency_fit_score' => 70, 'individual_fit_score' => 72]);
        $this->stored(['title' => 'Bid individual', 'status' => 'won'], ['selected_profile' => 'individual:asha']);
        $bde = $this->bde();

        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['target' => 'individual']))->assertOk()
            ->assertSee('Individual job')->assertSee('Either job')->assertDontSee('Agency job');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['target' => 'both']))->assertOk()
            ->assertSee('Either job')->assertDontSee('Individual job');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['bid_as' => 'individual', 'show' => 'all']))->assertOk()
            ->assertSee('Bid individual')->assertDontSee('Either job');
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['sort' => 'individual_fit', 'target' => 'individual']))->assertOk()
            ->assertSeeInOrder(['Individual job', 'Either job']);
        // Unknown filter values are dropped, not errors.
        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['target' => 'robots']))->assertOk()->assertSee('Agency job');

        $radar = app(\App\Services\Upwork\RadarService::class)->radar('today');
        $this->assertSame(1, $radar['targets']['bid_as']['individual']['won']);
        $this->assertSame(100.0, $radar['targets']['bid_as']['individual']['win_rate']);
        $this->actingAs($bde)->get(route('upwork.radar'))->assertOk()->assertSee('Target profiles');
    }

    public function test_targeting_config_page_saves_individual_profiles_and_is_audited(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get(route('upwork.config.targeting.edit'))->assertOk()
            ->assertSee('not linked to Upwork accounts', false)->assertSee('agency_mode');

        $form = ['profiles' => [['name' => 'Asha Rao', 'summary' => 'Laravel', 'technologies' => ['laravel']], ['name' => '']]];
        $this->actingAs($this->bde())->put(route('upwork.config.targeting.update'), $form)->assertForbidden();
        $this->actingAs($admin)->put(route('upwork.config.targeting.update'), $form)->assertSessionHas('success');

        $this->assertSame(['asha-rao'], array_keys(TargetProfiles::individuals()));
        $this->assertArrayHasKey('individual:asha-rao', TargetProfiles::options());
        $this->assertArrayNotHasKey(TargetProfiles::GENERIC_INDIVIDUAL, TargetProfiles::options());
        $this->assertSame(1, \App\Models\UpworkConfigLog::where('label', 'Individual profiles')->count());

        // Remove it again.
        $this->actingAs($admin)->put(route('upwork.config.targeting.update'), ['profiles' => [['key' => 'asha-rao', 'name' => 'Asha Rao', 'remove' => 1]]]);
        $this->assertSame([], TargetProfiles::individuals());
    }

    // ---- Backfill -----------------------------------------------------------------

    public function test_backfill_classifies_old_rows_without_changing_their_score_or_review_state(): void
    {
        $this->agencyModeOff();
        [$o] = app(UpworkOpportunityService::class)->ingest($this->smallJob(['upwork_job_id' => '02old', 'upwork_url' => 'https://www.upwork.com/jobs/~02old']));
        // Simulate a row scored before targeting existed, with a review deadline and snooze in place.
        $explanation = $o->score_explanation;
        unset($explanation['target']);
        $o->forceFill([
            'target_profile' => null, 'agency_fit_score' => null, 'individual_fit_score' => null, 'score_explanation' => $explanation,
            'score' => 42.5, 'recommendation' => 'review', 'review_due_at' => now()->addMinutes(20), 'review_snoozed_until' => now()->addHour(),
        ])->saveQuietly();
        $before = $o->fresh()->only(['score', 'recommendation', 'review_due_at', 'review_snoozed_until', 'status', 'hard_filters', 'classifications']);

        $this->artisan('upwork:classify-targets')
            ->expectsOutput('Dry run: 1 job(s) examined, nothing saved. Run with --apply to write the target fields.')->assertExitCode(0);
        $this->assertNull($o->fresh()->target_profile);

        $this->artisan('upwork:classify-targets', ['--apply' => true])
            ->expectsOutput('Classified 1 job(s). Scores and recommendations were not changed.')->assertExitCode(0);
        $o->refresh();
        $this->assertSame('individual', $o->target_profile);
        $this->assertSame('individual', $o->score_explanation['target']['target']);
        $this->assertEquals($before, $o->only(array_keys($before)));
        $this->assertSame(1, $o->logs()->where('action', 'target_classified')->count());

        // Idempotent: a second run skips classified rows.
        $this->artisan('upwork:classify-targets', ['--apply' => true]);
        $this->assertSame(1, $o->logs()->where('action', 'target_classified')->count());
    }
}
