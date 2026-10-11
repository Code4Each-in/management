<?php

namespace Tests\Feature\Upwork;

use App\Jobs\Upwork\QualifyOpportunity;
use App\Models\UpworkAiAnalysis;
use App\Models\UpworkAiUsage;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiBudgetGuard;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\Ai\Providers\DisabledProvider;
use App\Services\Upwork\Ai\Providers\FakeProvider;
use App\Services\Upwork\Ai\Providers\OpenAiProvider;
use App\Services\Upwork\Ai\Qualification\QualificationPrompt;
use App\Services\Upwork\Ai\Qualification\QualificationService;
use App\Services\Upwork\UpworkOpportunityService;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * AI phases 3-4: qualification after local scoring, structured analysis
 * storage, FACT verification, confidence gate, idempotent queue jobs.
 * Only the fake provider is used; nothing leaves the machine.
 */
class UpworkAiQualificationTest extends UpworkTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
        Http::fake(); // any accidental HTTP call would be recorded and asserted against
    }

    private function enable(array $overrides = []): void
    {
        foreach (array_merge(['enabled' => true, 'provider' => 'fake'], $overrides) as $key => $value) {
            UpworkSetting::set("ai.{$key}", $value);
        }
    }

    /** A strong, PMS-scored job (no score in the payload). */
    private function ingest(array $overrides = []): UpworkOpportunity
    {
        $payload = $this->payload($overrides);
        unset($payload['score'], $payload['recommendation']);
        [$o] = app(UpworkOpportunityService::class)->ingest($payload);

        return $o->fresh();
    }

    private function service(?string $mode = null): QualificationService
    {
        return new QualificationService(new AiGateway(new FakeProvider($mode), new AiBudgetGuard(), function () {}));
    }

    private function answer(array $overrides = []): array
    {
        return array_replace_recursive([
            'summary' => 'Laravel SaaS dashboard extension.', 'recommendation' => 'apply', 'fit_score' => 86, 'confidence' => 0.8,
            'technologies' => [['name' => 'Laravel', 'kind' => 'FACT', 'evidence' => 'experienced Laravel team']],
            'scope' => ['type' => 'extension', 'complexity' => 'medium', 'estimated_hours_min' => 80, 'estimated_hours_max' => 160, 'estimate_basis' => '3 to 6 months part-time'],
            'signals' => ['long_term_potential' => 'yes', 'ai_component' => 'yes', 'budget_realistic' => 'yes', 'agency_friendly' => 'yes', 'urgency' => 'normal'],
            'risks' => [['risk' => 'Scope of AI integrations unclear', 'severity' => 'medium', 'kind' => 'INFERENCE']],
            'claims' => [
                ['statement' => 'Client has spent $125,000', 'kind' => 'FACT', 'evidence' => 'Total spent: $125,000'],
                ['statement' => 'Client wants a team', 'kind' => 'FACT', 'evidence' => 'we need an agency of 10 people'],
                ['statement' => 'Existing codebase', 'kind' => 'INFERENCE', 'evidence' => 'experienced Laravel team'],
                ['statement' => 'Deadline', 'kind' => 'UNKNOWN', 'evidence' => null],
            ],
            'unknowns' => ['Deadline'], 'questions_for_client' => ['Which AI integrations?'],
        ], $overrides);
    }

    // ---- Automatic path: what goes to AI ------------------------------------------------------

    public function test_ai_off_means_nothing_is_queued_or_marked(): void
    {
        Queue::fake();

        $o = $this->ingest();

        Queue::assertNothingPushed();
        $this->assertNull($o->ai_status);
        $this->assertSame(0, UpworkAiUsage::count());
    }

    public function test_promising_jobs_are_queued_on_the_module_queue(): void
    {
        Queue::fake();
        $this->enable();

        $o = $this->ingest();

        $this->assertGreaterThanOrEqual(55, $o->score);
        $this->assertSame(UpworkOpportunity::AI_QUEUED, $o->ai_status);
        $this->assertNotNull($o->ai_input_hash);
        Queue::assertPushed(QualifyOpportunity::class, fn($job) => $job->opportunityId === $o->id && $job->connection === 'upwork' && $job->queue === 'upwork');

        // Re-ingesting the same details does not queue (or pay) again.
        $this->ingest();
        Queue::assertPushed(QualifyOpportunity::class, 1);
    }

    public function test_low_scoring_and_hard_rejected_jobs_never_reach_ai(): void
    {
        Queue::fake();
        $this->enable(['min_local_score' => 99]);

        $low = $this->ingest();
        $hard = $this->ingest(['upwork_job_id' => '~01hard', 'upwork_url' => 'https://www.upwork.com/jobs/~01hard', 'contractor_type' => 'Individuals only']);

        Queue::assertNothingPushed();
        $this->assertSame(UpworkOpportunity::AI_SKIPPED, $low->ai_status);
        $this->assertStringContainsString('below the AI threshold (99)', $low->ai_status_reason);
        $this->assertSame('Hard-rejected by local rules.', $hard->ai_status_reason);
    }

    public function test_search_rows_are_never_sent_to_ai(): void
    {
        Queue::fake();
        $this->enable();

        app(UpworkOpportunityService::class)->ingestSighting(['upwork_job_id' => '2107393100064249043', 'title' => 'Laravel SaaS', 'description' => 'Laravel SaaS platform.']);

        Queue::assertNothingPushed();
    }

    public function test_budget_reached_marks_blocked_and_queues_nothing(): void
    {
        Queue::fake();
        $this->enable(['monthly_budget_usd' => 0.0001]);
        UpworkAiUsage::create(['provider' => 'fake', 'model' => 'gpt-5-nano', 'request_type' => 'qualify', 'prompt_version' => 'qualify-v1',
            'status' => 'succeeded', 'attempt' => 1, 'estimated_cost_usd' => 0.01]);

        $o = $this->ingest();

        Queue::assertNothingPushed();
        $this->assertSame(UpworkOpportunity::AI_BLOCKED, $o->ai_status);
        $this->assertSame('AI monthly budget reached — local scoring continues.', $o->ai_status_reason);
        $this->assertNotNull($o->score, 'local scoring still ran');
    }

    // ---- The analysis ---------------------------------------------------------------------

    public function test_analysis_is_stored_separately_and_rescored_with_ai_signals(): void
    {
        Queue::fake();
        $this->enable();
        $o = $this->ingest();
        $localScore = $o->score;

        $out = $this->service()->analyze($o);

        $o->refresh();
        $analysis = $out['analysis'];
        $this->assertSame('analyzed', $out['status']);
        $this->assertSame(UpworkAiAnalysis::STATUS_SUCCEEDED, $analysis->status);
        $this->assertSame([$o->id, 'fake', 'gpt-5-nano', 'qualify-v1'], [$analysis->opportunity_id, $analysis->provider, $analysis->model, $analysis->prompt_version]);
        $this->assertSame($analysis->id, $o->ai_analysis_id);
        $this->assertSame(UpworkOpportunity::AI_ANALYZED, $o->ai_status);
        $this->assertSame(0.7, $o->ai_confidence);
        $this->assertNotNull($analysis->usage_id);
        $this->assertSame('succeeded', UpworkAiUsage::find($analysis->usage_id)->status);
        $this->assertSame($analysis->id, $o->score_explanation['ai']['analysis_id'], 'AI signals reached the scoring rules');
        $this->assertSame(1, $o->logs()->where('action', 'ai_analyzed')->count());
        $this->assertTrue(abs($o->score - $localScore) <= 10, 'AI only nudges the score');
    }

    public function test_identical_input_is_never_paid_for_twice_but_regenerate_is(): void
    {
        $this->enable();
        Queue::fake();
        $o = $this->ingest();
        $service = $this->service();

        $first = $service->analyze($o);
        $second = $service->analyze($o->fresh());
        $this->assertTrue($second['reused']);
        $this->assertSame($first['analysis']->id, $second['analysis']->id);
        $this->assertSame(1, UpworkAiUsage::count());

        $third = $service->analyze($o->fresh(), 'qualify', true);
        $this->assertFalse($third['reused']);
        $this->assertSame(2, UpworkAiUsage::count());
        $this->assertSame(2, UpworkAiAnalysis::count(), 'the earlier answer is kept');
        $this->assertSame($third['analysis']->id, $o->fresh()->ai_analysis_id);
    }

    public function test_per_job_daily_cap(): void
    {
        $this->enable(['max_analyses_per_job_per_day' => 2]);
        Queue::fake();
        $o = $this->ingest();
        $service = $this->service();

        $service->analyze($o, 'qualify', true);
        $service->analyze($o->fresh(), 'qualify', true);
        $out = $service->analyze($o->fresh(), 'qualify', true);

        $this->assertSame('blocked', $out['status']);
        $this->assertSame('Per-job AI limit reached for today.', $o->fresh()->ai_status_reason);
        $this->assertSame(2, UpworkAiUsage::count());
    }

    public function test_ai_failure_keeps_local_scoring_and_records_the_error(): void
    {
        $this->enable(['max_retries' => 1]);
        Queue::fake();
        $o = $this->ingest();
        $before = [$o->score, $o->recommendation];

        $out = $this->service('server_error')->analyze($o);

        $o->refresh();
        $this->assertSame('failed', $out['status']);
        $this->assertSame(UpworkOpportunity::AI_FAILED, $o->ai_status);
        $this->assertSame([UpworkAiAnalysis::STATUS_FAILED, 'server_error'], [$out['analysis']->status, $out['analysis']->error_code]);
        $this->assertSame(2, UpworkAiUsage::count(), 'one bounded retry');
        $this->assertSame($before, [$o->score, $o->recommendation]);
        $this->assertNull($o->ai_analysis_id);
    }

    public function test_a_refused_retry_reports_the_real_failure(): void
    {
        $this->enable(['max_retries' => 1]);
        Queue::fake();
        $o = $this->ingest();
        $this->service()->analyze($o); // attempt 1 of 2 today (succeeds)

        $out = $this->service('server_error')->analyze($o->fresh(), 'qualify', true); // attempt 2 fails, retry refused

        $this->assertSame('failed', $out['status']);
        $this->assertSame('server_error', $out['analysis']->error_code);
        $this->assertSame(UpworkOpportunity::AI_FAILED, $o->fresh()->ai_status);
        $this->assertSame(2, UpworkAiUsage::count());
    }

    public function test_malformed_answers_are_rejected_not_stored_as_results(): void
    {
        $this->enable(['max_retries' => 0]);
        Queue::fake();

        $out = $this->service('malformed')->analyze($this->ingest());

        $this->assertSame('failed', $out['status']);
        $this->assertSame('schema_invalid', $out['analysis']->error_code);
        $this->assertNull($out['analysis']->result);
    }

    public function test_queued_job_rechecks_eligibility_and_is_idempotent(): void
    {
        $this->enable();
        Queue::fake();
        $o = $this->ingest();
        app(UpworkOpportunityService::class)->reject($o, $this->bde()->id, 'Not for us');

        (new QualifyOpportunity($o->id))->handle(app(QualificationService::class));

        $this->assertSame(UpworkOpportunity::AI_SKIPPED, $o->fresh()->ai_status);
        $this->assertSame(0, UpworkAiUsage::count());

        // A person may still ask explicitly.
        (new QualifyOpportunity($o->id, 'qualify', false, $this->bde()->id))->handle(app(QualificationService::class));
        (new QualifyOpportunity($o->id, 'qualify', false, $this->bde()->id))->handle(app(QualificationService::class));
        $this->assertSame(UpworkOpportunity::AI_ANALYZED, $o->fresh()->ai_status);
        $this->assertSame(1, UpworkAiUsage::count(), 'second run reused the answer');
    }

    // ---- Facts versus AI judgement ----------------------------------------------------------

    public function test_unverifiable_facts_are_downgraded_to_inference(): void
    {
        $o = $this->ingest();
        $request = (new QualificationPrompt())->build($o, 6000, 1500);

        $data = QualificationService::verify($this->answer(), $request->dynamicContext);

        $this->assertSame('FACT', $data['technologies'][0]['kind'], 'quote is in the post');
        $this->assertSame('FACT', $data['claims'][0]['kind'], 'quote is in the client data');
        $this->assertSame('INFERENCE', $data['claims'][1]['kind'], 'quote is not in the input');
        $this->assertTrue($data['claims'][1]['downgraded']);
        $this->assertSame(['facts_checked' => 3, 'facts_downgraded' => 1], $data['verification']);
    }

    public function test_low_confidence_analysis_does_not_reach_scoring(): void
    {
        $this->enable(['min_confidence' => 0.9]);
        Queue::fake();
        $o = $this->ingest();

        $this->service()->analyze($o); // fake answers with confidence 0.7

        $o->refresh();
        $this->assertSame(UpworkOpportunity::AI_ANALYZED, $o->ai_status, 'still stored and shown');
        $this->assertNull(QualificationService::signalsFor($o));
        $this->assertNull($o->score_explanation['ai']);
    }

    public function test_ai_rules_use_signals_only_when_available(): void
    {
        $service = app(UpworkOpportunityService::class);
        $attributes = $this->payload();
        $fired = fn($result) => collect($result['components'])->pluck('rules')->flatten(1)->pluck('key')->all();

        $without = $service->scoreWithCurrentRuleset($attributes);
        $with = $service->scoreWithCurrentRuleset($attributes + ['ai_signals' => ['fit_score' => 20, 'confidence' => 0.9, 'budget_realistic' => 'no', 'long_term_potential' => 'yes']]);

        $aiRules = ['ai_strong_fit', 'ai_weak_fit', 'ai_unrealistic_budget', 'ai_long_term_signal'];
        $this->assertSame([], array_values(array_intersect($aiRules, $fired($without))));
        $this->assertContains('ai_weak_fit', $fired($with));
        $this->assertContains('ai_unrealistic_budget', $fired($with));
        $this->assertContains('ai_long_term_signal', $fired($with));
    }

    // ---- Prompt ----------------------------------------------------------------------------

    public function test_prompt_separates_static_and_dynamic_context_and_respects_the_token_budget(): void
    {
        $prompt = new QualificationPrompt();
        $a = $prompt->build($this->ingest(), 6000, 1500);
        $b = $prompt->build($this->ingest(['upwork_job_id' => '~01long', 'upwork_url' => 'https://www.upwork.com/jobs/~01long',
            'description' => str_repeat('Build a Laravel booking platform with Stripe. ', 2000)]), 2500, 1500);

        $this->assertSame($a->staticContext, $b->staticContext, 'cacheable prefix');
        $this->assertStringContainsString('untrusted data', $a->staticContext);
        $this->assertStringContainsString('Laravel', $a->staticContext, 'capability catalog');
        $this->assertStringContainsString("DESCRIPTION\n<<<\n", $a->dynamicContext);
        $this->assertStringContainsString('Total spent: $125,000', $a->dynamicContext);
        $this->assertStringContainsString('[truncated]', $b->dynamicContext);
        $this->assertLessThanOrEqual(2500, $b->estimatedInputTokens());
        $this->assertNotSame(QualificationPrompt::inputHash($a, 'gpt-5-nano'), QualificationPrompt::inputHash($a, 'gpt-4.1-nano'), 'model is part of the hash');
    }

    public function test_schema_is_strict_mode_compatible(): void
    {
        $check = function (array $node, string $path) use (&$check) {
            if (in_array('object', (array) ($node['type'] ?? []), true)) {
                $this->assertFalse($node['additionalProperties'], "{$path}: additionalProperties");
                $this->assertSame(array_keys($node['properties']), $node['required'], "{$path}: every key required");
                foreach ($node['properties'] as $k => $child) {
                    $check($child, "{$path}.{$k}");
                }
            }
            if (isset($node['items'])) {
                $check($node['items'], "{$path}[]");
            }
        };
        $check(QualificationPrompt::schema(), '$');

        $kinds = QualificationPrompt::schema()['properties']['claims']['items']['properties']['kind']['enum'];
        $this->assertSame(['FACT', 'INFERENCE', 'ASSUMPTION', 'UNKNOWN'], $kinds);
        $this->assertSame([], \App\Services\Upwork\Ai\JsonSchemaValidator::validate($this->answer(), QualificationPrompt::schema()));
    }

    // ---- Safety ----------------------------------------------------------------------------

    public function test_fake_provider_is_refused_in_production(): void
    {
        $this->assertInstanceOf(FakeProvider::class, AiGateway::providerFor('fake'));
        $this->assertContains('fake', AiGateway::availableProviders());

        $this->app['env'] = 'production';
        $this->assertInstanceOf(DisabledProvider::class, AiGateway::providerFor('fake'));
        $this->assertNotContains('fake', AiGateway::availableProviders());
        $this->assertInstanceOf(OpenAiProvider::class, AiGateway::providerFor('openai'));
    }

    public function test_module_queue_never_uses_the_pms_jobs_table(): void
    {
        // The PMS `jobs` table holds recruitment postings; queue rows must never land there.
        $this->assertSame('upwork_queue_jobs', config('queue.connections.upwork.table'));
        $this->assertSame('upwork', config('upwork.ai.queue.connection'));
        $this->assertTrue(\Schema::hasTable('upwork_queue_jobs'));

        $this->assertTrue(config('queue.connections.upwork.after_commit'), 'queued only once the opportunity is committed');
        config(['queue.connections.upwork.after_commit' => false]); // tests run inside a transaction that never commits

        $this->enable();
        $o = $this->ingest(); // real (not faked) dispatch onto the database queue
        $this->assertSame(1, \DB::table('upwork_queue_jobs')->where('queue', 'upwork')->count());
        $this->assertStringContainsString('QualifyOpportunity', \DB::table('upwork_queue_jobs')->value('payload'));
        $this->assertSame(UpworkOpportunity::AI_QUEUED, $o->ai_status);
    }

    public function test_nothing_left_the_machine(): void
    {
        $this->enable();
        Queue::fake();
        $this->service()->analyze($this->ingest());

        Http::assertNothingSent();
    }
}
