<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Services\Upwork\Scoring\ScoringEngine;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Invite penalty: -5 once when the client has sent any invites, 0 when none or
 * unknown. Base, penalty and final score stay separately visible, and a
 * re-score never subtracts it twice. Nothing here contacts Upwork.
 */
class UpworkInvitePenaltyTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        (new UpworkIntelligenceSeeder)->run();
    }

    private function service(): UpworkOpportunityService
    {
        return app(UpworkOpportunityService::class);
    }

    /** Full job details without a score, so the PMS engine scores it. */
    private function pmsPayload(array $overrides = []): array
    {
        return array_merge(Arr::except($this->payload(), [
            'score', 'technical_fit_score', 'experience_fit_score', 'client_quality_score', 'commercial_value_score',
            'long_term_score', 'ai_automation_score', 'competition_score', 'scoring_version', 'recommendation',
        ]), $overrides);
    }

    private function scored(?array $activity, string $id): UpworkOpportunity
    {
        $payload = $this->pmsPayload(['upwork_job_id' => $id]);
        if ($activity !== null) {
            $payload['job_activity'] = $activity;
        }
        [$o] = $this->service()->ingest($payload);

        return $o->fresh();
    }

    private function componentSum(UpworkOpportunity $o): float
    {
        return round(array_sum(array_map(fn($c) => $c['points'], $o->score_explanation['components'])), 2);
    }

    public function test_zero_invites_subtract_nothing(): void
    {
        $o = $this->scored(['invites_sent' => 0], '~01zero');
        $inv = $o->score_explanation['invites'];

        $this->assertSame(0, $o->invites_sent);
        $this->assertSame(0, $inv['invites_sent']);
        $this->assertEquals(0, $inv['penalty']);
        $this->assertEquals($inv['base_score'], $inv['final_score']);
        $this->assertEquals($inv['final_score'], $o->score);
        $this->assertEquals($this->componentSum($o), $inv['base_score']);
    }

    public function test_one_or_more_invites_subtract_exactly_five(): void
    {
        $none = $this->scored(['invites_sent' => 0], '~01none');
        foreach (['~01one' => 1, '~01many' => 12] as $id => $count) {
            // Upwork's camelCase key is read too.
            $o = $this->scored(['invitesSent' => $count], $id);
            $inv = $o->score_explanation['invites'];

            $this->assertSame($count, $inv['invites_sent']);
            $this->assertEquals(5, $inv['penalty'], "{$count} invites");
            $this->assertEquals($none->score, $inv['base_score'], 'the base is unaffected by invites');
            $this->assertEquals(round($inv['base_score'] - 5, 2), $inv['final_score']);
            $this->assertEquals($inv['final_score'], $o->score);
            $this->assertEquals($this->componentSum($o), $inv['base_score'], 'components add up to the base, not the final score');
            $this->assertSame(UpworkOpportunity::recommendationForScore($o->score, UpworkRuleset::current()->thresholds), $o->recommendation);
            $this->assertEquals(5.0, $o->invite_penalty);
        }
    }

    public function test_unavailable_invite_count_subtracts_nothing_and_is_never_invented(): void
    {
        $none = $this->scored(['invites_sent' => 0], '~01known');
        foreach (['~01missing' => null, '~01nullval' => ['invites_sent' => null, 'total_hired' => 0], '~01text' => ['invites_sent' => 'n/a']] as $id => $activity) {
            $o = $this->scored($activity, $id);
            $inv = $o->score_explanation['invites'];

            $this->assertNull($o->invites_sent);
            $this->assertNull($inv['invites_sent']);
            $this->assertEquals(0, $inv['penalty']);
            $this->assertEquals($none->score, $o->score);
            $this->assertStringContainsString('not available', $inv['note']);
        }

        // A search row (no job activity) scores without a penalty too.
        [$row] = $this->service()->ingestSighting(['upwork_job_id' => '~01sighting', 'title' => 'Laravel API work', 'description' => 'Laravel']);
        $this->assertEquals(0, $row->fresh()->score_explanation['invites']['penalty']);
    }

    public function test_rescoring_and_rediscovery_never_subtract_twice(): void
    {
        $o = $this->scored(['invites_sent' => 3], '~01twice');
        $first = [$o->score, $o->score_explanation['invites']];

        $this->service()->rescore($o);
        $this->service()->rescore($o->fresh());
        $this->service()->ingest($this->pmsPayload(['upwork_job_id' => '~01twice', 'job_activity' => ['invites_sent' => 3]]));
        $this->service()->ingestSighting(['upwork_job_id' => '~01twice', 'proposals_count' => 12]);

        $o->refresh();
        $this->assertEquals($first[0], $o->score);
        $this->assertEquals($first[1]['final_score'], $o->score_explanation['invites']['final_score']);
        $this->assertEquals(round($o->score_explanation['invites']['base_score'] - 5, 2), $o->score);
        $this->assertSame(0, $o->logs()->where('action', 'rescored')->count(), 'nothing changed, nothing logged');

        // Invites become known as 0 later: back to the base, not +5 on top of the old score.
        $this->service()->ingest($this->pmsPayload(['upwork_job_id' => '~01twice', 'job_activity' => ['invites_sent' => 0]]));
        $this->assertEquals($first[1]['base_score'], $o->fresh()->score);
    }

    public function test_ruleset_score_rules_on_invites_are_superseded(): void
    {
        $job = Arr::except($this->pmsPayload(), ['upwork_job_id']) + ['job_activity' => ['invites_sent' => 4]];
        $before = (new ScoringEngine())->score($job, UpworkRuleset::current()->load('rules'));

        $draft = UpworkRuleset::draftOrCreate();
        $draft->rules()->create(['key' => 'invites_heavy', 'name' => 'Invites sent', 'type' => 'score', 'component' => 'competition', 'points' => -20,
            'reason' => 'Old invite rule', 'conditions' => ['field' => 'competition.invites_sent', 'op' => 'gt', 'value' => 0]]);
        $draft->rules()->create(['key' => 'invites_mixed', 'name' => 'Invites and Laravel', 'type' => 'score', 'component' => 'competition', 'points' => -3,
            'reason' => 'Compound', 'conditions' => ['all' => [['field' => 'competition.invites_sent', 'op' => 'gte', 'value' => 1], ['field' => 'job.text', 'op' => 'contains_any', 'value' => ['laravel']]]]]);
        $draft->publish(null);
        $after = (new ScoringEngine())->score($job, $draft->fresh()->load('rules'));

        $this->assertEquals($before['score'], $after['score'], 'invites count exactly once: -5');
        $this->assertEquals(round($after['invites']['base_score'] - 5, 2), $after['score']);
        $this->assertSame(['invites_heavy', 'invites_mixed'], $after['invites']['superseded_rules']);
        $fired = collect($after['components'])->flatMap(fn($c) => array_column($c['rules'], 'key'))->all();
        $this->assertEmpty(array_intersect(['invites_heavy', 'invites_mixed'], $fired), 'superseded rules add no points');
    }

    public function test_penalty_never_takes_the_score_below_zero(): void
    {
        $draft = UpworkRuleset::draftOrCreate();
        foreach (array_keys(UpworkRuleset::COMPONENTS) as $component) {
            $draft->rules()->create(['key' => "zero_{$component}", 'name' => 'Zero', 'type' => 'score', 'component' => $component, 'points' => -100,
                'reason' => 'Test', 'conditions' => ['field' => 'job.text', 'op' => 'contains_any', 'value' => ['laravel']]]);
        }
        $draft->publish(null);

        $result = (new ScoringEngine())->score(Arr::except($this->pmsPayload(), ['upwork_job_id']) + ['job_activity' => ['invites_sent' => 2]], $draft->fresh()->load('rules'));

        $this->assertSame([0.0, 5.0, 0.0], [$result['invites']['base_score'], $result['invites']['penalty'], $result['score']]);
    }

    public function test_location_restriction_still_wins_over_the_invite_penalty(): void
    {
        $payload = $this->pmsPayload(['upwork_job_id' => '~01usinv', 'job_activity' => ['invites_sent' => 2],
            'raw_data' => ['preferred_qualifications' => ['location' => 'US only']]]);
        [$o] = $this->service()->ingest($payload);
        $o->refresh();

        $this->assertSame(['restricted', 'skip'], [$o->location_eligibility, $o->recommendation]);
        $this->assertEquals(5, $o->score_explanation['invites']['penalty']);
        $this->assertSame(UpworkOpportunity::recommendationForScore($o->score, UpworkRuleset::current()->thresholds), $o->location_prior_recommendation);
    }

    public function test_job_page_and_list_show_invites_and_the_breakdown(): void
    {
        $penalised = $this->scored(['invites_sent' => 3], '~01page');
        $unknown = $this->scored(null, '~01unknownpage');
        $inv = $penalised->score_explanation['invites'];
        $fmt = fn($v) => rtrim(rtrim(number_format($v, 1), '0'), '.');
        $bde = $this->bde();

        $this->actingAs($bde)->get(route('upwork.opportunities.show', $penalised))->assertOk()
            ->assertSeeInOrder(['Base score', $fmt($inv['base_score']), 'Invites Sent', '3', 'Invite Penalty', '−5', 'Final score', $fmt($inv['final_score'])])
            ->assertSee('Client already sent 3 invite(s)');
        $this->actingAs($bde)->get(route('upwork.opportunities.show', $unknown))->assertOk()
            ->assertSeeInOrder(['Invites Sent', 'Not available', 'Invite Penalty', '0']);

        $this->actingAs($bde)->get(route('upwork.opportunities.index', ['show' => 'all']))->assertOk()
            ->assertSee('Invites')->assertSee('−5 invite penalty');
    }
}
