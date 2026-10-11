<?php

namespace Tests\Feature\Upwork;

use App\Jobs\Upwork\QualifyOpportunity;
use App\Models\UpworkAiAnalysis;
use App\Models\UpworkAiUsage;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiBudgetGuard;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\Ai\Providers\FakeProvider;
use App\Services\Upwork\Ai\Qualification\DeepAnalysisPrompt;
use App\Services\Upwork\Ai\Qualification\QualificationPrompt;
use App\Services\Upwork\Ai\Qualification\QualificationService;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Support\Facades\Queue;

/**
 * Phase 8: verified facts and AI judgement shown separately; Analyze, Deep
 * analyze, Regenerate and per-job AI usage; deep analysis sub-budget.
 */
class UpworkAiUiTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Queue::fake();
    }

    private function enable(array $overrides = []): void
    {
        foreach (array_merge(['enabled' => true, 'provider' => 'fake', 'auto_analyze' => false], $overrides) as $k => $v) {
            UpworkSetting::set("ai.{$k}", $v);
        }
    }

    private function scoredJob(): UpworkOpportunity
    {
        $payload = $this->payload();
        unset($payload['score'], $payload['recommendation']);
        [$o] = app(UpworkOpportunityService::class)->ingest($payload);

        return $o->fresh();
    }

    private function service(): QualificationService
    {
        return new QualificationService(new AiGateway(new FakeProvider(), new AiBudgetGuard(), function () {}));
    }

    public function test_page_separates_verified_facts_local_scoring_and_ai(): void
    {
        $o = $this->scoredJob();

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))->assertOk()
            ->assertSee('VERIFIED · FROM UPWORK')
            ->assertSee('RULES · DETERMINISTIC')
            ->assertSee('AI JUDGEMENT · NOT VERIFIED')
            ->assertSee('No AI analysis yet. Local scoring above stands on its own.')
            ->assertSee('Analyze with AI')
            ->assertSee('Deep analyze')
            ->assertSee('AI usage for this job: 0 requests');
    }

    public function test_analyze_button_queues_and_respects_the_switch(): void
    {
        $o = $this->scoredJob();
        $bde = $this->bde();

        $this->actingAs($bde)->post(route('upwork.opportunities.ai.analyze', $o), ['type' => 'qualify'])
            ->assertSessionHas('error', 'AI is switched off (Upwork → Configuration → AI).');
        Queue::assertNothingPushed();

        $this->enable();
        $this->actingAs($bde)->post(route('upwork.opportunities.ai.analyze', $o), ['type' => 'qualify'])->assertSessionHas('success');
        Queue::assertPushed(QualifyOpportunity::class, fn($j) => $j->type === 'qualify' && $j->userId === $bde->id && !$j->force);
        $this->assertSame(UpworkOpportunity::AI_QUEUED, $o->fresh()->ai_status);

        $this->actingAs($bde)->post(route('upwork.opportunities.ai.analyze', $o), ['type' => 'qualify'])->assertSessionHas('error', 'Already queued.');
        $this->actingAs($bde)->post(route('upwork.opportunities.ai.analyze', $o), ['type' => 'bogus'])->assertSessionHasErrors('type');
    }

    public function test_people_without_upwork_access_cannot_spend_ai_budget(): void
    {
        $this->enable();
        $o = $this->scoredJob();

        $this->actingAs($this->developer())->post(route('upwork.opportunities.ai.analyze', $o), ['type' => 'qualify'])->assertForbidden();
        Queue::assertNothingPushed();
    }

    public function test_analysis_is_shown_with_labelled_claims_and_usage(): void
    {
        $this->enable();
        $o = $this->scoredJob();
        $this->service()->analyze($o);
        $analysis = UpworkAiAnalysis::first();
        $result = $analysis->result;
        $result['claims'][] = ['statement' => 'Client wants a team of ten', 'kind' => 'INFERENCE', 'evidence' => 'we need 10 people', 'downgraded' => true];
        $analysis->forceFill(['result' => $result])->save();

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))->assertOk()
            ->assertSee("{$analysis->fit_score}/100")
            ->assertSee('ASSUMPTION')
            ->assertSee('UNKNOWN')
            ->assertSee('(AI said FACT; quote not found)')
            ->assertSee('Regenerate')
            ->assertSee('AI usage for this job: 1 request')
            ->assertSee('fake/gpt-5-nano');

        $this->actingAs($this->bde())->get(route('upwork.opportunities.index', ['view' => 'all']))->assertOk()->assertSee("AI {$analysis->fit_score}");
    }

    public function test_regenerate_and_deep_analyze_dispatch_the_right_jobs(): void
    {
        $this->enable();
        $o = $this->scoredJob();
        $bde = $this->bde();

        $this->actingAs($bde)->post(route('upwork.opportunities.ai.analyze', $o), ['type' => 'qualify', 'regenerate' => 1]);
        Queue::assertPushed(QualifyOpportunity::class, fn($j) => $j->force && $j->type === 'qualify');

        $this->actingAs($bde)->post(route('upwork.opportunities.ai.analyze', $o), ['type' => 'deep'])->assertSessionHas('error', 'Already queued.');
        $o->fresh()->forceFill(['ai_status' => null])->save(); // the queued job finished
        $this->actingAs($bde)->post(route('upwork.opportunities.ai.analyze', $o), ['type' => 'deep'])->assertSessionHas('success');
        Queue::assertPushed(QualifyOpportunity::class, fn($j) => $j->type === 'deep');
    }

    public function test_deep_analysis_uses_its_own_prompt_caps_and_is_shown(): void
    {
        $this->enable();
        $o = $this->scoredJob();

        $out = $this->service()->analyze($o, 'deep', false, $this->bde()->id);

        $this->assertSame('analyzed', $out['status']);
        $analysis = $out['analysis'];
        $this->assertSame(['deep', 'deep-v1'], [$analysis->request_type, $analysis->prompt_version]);
        $this->assertArrayHasKey('proposal_angle', $analysis->result);
        $usage = UpworkAiUsage::find($analysis->usage_id);
        $this->assertSame('deep', $usage->request_type);
        $this->assertGreaterThan(1500 + 2000, $usage->reserved_tokens, 'deep output cap (3000) applies, not the 1500 default');

        $this->actingAs($this->bde())->get(route('upwork.opportunities.show', $o))->assertOk()
            ->assertSee('Proposal angle (deep)')->assertSee('Deep analysis · fake/gpt-5-nano');
    }

    public function test_deep_analysis_has_its_own_allowance_inside_the_budget(): void
    {
        $this->enable(['deep_daily_request_limit' => 1]);
        $service = $this->service();
        $a = $this->scoredJob();
        $b = $this->scoredJob(); // same job: use a second one
        $payload = $this->payload(['upwork_job_id' => '~01second', 'upwork_url' => 'https://www.upwork.com/jobs/~01second']);
        unset($payload['score'], $payload['recommendation']);
        [$b] = app(UpworkOpportunityService::class)->ingest($payload);

        $this->assertSame('analyzed', $service->analyze($a, 'deep')['status']);
        $out = $service->analyze($b->fresh(), 'deep');

        $this->assertSame('blocked', $out['status']);
        $this->assertStringContainsString('deep daily request limit', $out['message']);
        $this->assertSame('analyzed', $service->analyze($b->fresh(), 'qualify')['status'], 'normal qualification is unaffected');
    }

    public function test_deep_schema_is_strict_and_extends_qualification(): void
    {
        $deep = DeepAnalysisPrompt::schema();

        $this->assertSame(array_keys($deep['properties']), $deep['required']);
        $this->assertSame([], array_diff(array_keys(QualificationPrompt::schema()['properties']), array_keys($deep['properties'])));
        $this->assertFalse($deep['properties']['red_flags']['items']['additionalProperties']);
        $this->assertStringContainsString('never invent past projects', (new DeepAnalysisPrompt())->staticContext());
        $this->assertStringNotContainsString('proposal_angle', (new QualificationPrompt())->staticContext());
    }

    public function test_ai_settings_save_deep_allowance_within_the_budget(): void
    {
        $admin = $this->superAdmin();
        $form = ['enabled' => 0, 'provider' => 'openai', 'model' => 'gpt-5-nano', 'reasoning_effort' => 'minimal',
            'min_local_score' => 55, 'max_input_tokens' => 6000, 'max_output_tokens' => 1500, 'max_retries' => 1, 'timeout_seconds' => 60,
            'daily_request_limit' => 300, 'monthly_request_limit' => 6000, 'daily_token_limit' => 1500000, 'monthly_token_limit' => 20000000,
            'daily_budget_usd' => 0.5, 'monthly_budget_usd' => 5, 'auto_analyze' => 0, 'min_confidence' => 0.7, 'deep_monthly_budget_usd' => 2];

        $this->actingAs($admin)->put(route('upwork.config.ai.update'), array_merge($form, ['deep_monthly_budget_usd' => 6]))
            ->assertSessionHasErrors('deep_monthly_budget_usd');
        $this->actingAs($admin)->put(route('upwork.config.ai.update'), $form)->assertSessionHas('success', 'AI settings saved.');

        $this->assertSame([false, 0.7, 2], [UpworkSetting::get('ai.auto_analyze'), UpworkSetting::get('ai.min_confidence'), UpworkSetting::get('ai.deep_monthly_budget_usd')]);
        $this->actingAs($admin)->get(route('upwork.config.ai.edit'))->assertOk()->assertSee('Deep analyses')->assertSee('Analyse eligible jobs automatically');
    }
}
