<?php

namespace Tests\Feature\Upwork;

use App\Models\UpworkAiUsage;
use App\Models\UpworkConfigLog;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiBudgetGuard;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\Ai\AiPricing;
use App\Services\Upwork\Ai\AiRequest;
use App\Services\Upwork\Ai\JsonSchemaValidator;
use App\Services\Upwork\Ai\Providers\OpenAiProvider;
use Database\Seeders\UpworkIntelligenceSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * AI phase 1: provider abstraction, budget guard, usage logging, settings.
 * Every provider call is faked; nothing leaves the machine.
 */
class UpworkAiLayerTest extends UpworkTestCase
{
    const URL = 'https://api.openai.test/v1/responses';

    protected function setUp(): void
    {
        parent::setUp();
        (new UpworkIntelligenceSeeder)->run();
    }

    private function schema(): array
    {
        return [
            'type' => 'object', 'additionalProperties' => false, 'required' => ['project_type', 'confidence'],
            'properties' => [
                'project_type' => ['type' => 'string'],
                'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
            ],
        ];
    }

    private function request(string $dynamic = 'Job: Laravel SaaS dashboard.'): AiRequest
    {
        return new AiRequest('qualify', 'qualify-v1', str_repeat('Code4Each capabilities. ', 20), $dynamic, $this->schema(), 'opportunity_qualification', 800);
    }

    private function enable(array $overrides = []): void
    {
        UpworkSetting::set('ai.enabled', true);
        foreach ($overrides as $key => $value) {
            UpworkSetting::set("ai.{$key}", $value);
        }
    }

    private function gateway(): AiGateway
    {
        return new AiGateway(new OpenAiProvider('sk-test-key', 'https://api.openai.test/v1'), new AiBudgetGuard(), function () {});
    }

    private function answer(array $data, array $usage = ['input_tokens' => 1200, 'input_tokens_details' => ['cached_tokens' => 800], 'output_tokens' => 300, 'output_tokens_details' => ['reasoning_tokens' => 100]]): array
    {
        return ['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($data)]]]], 'usage' => $usage];
    }

    // ---- Safe defaults ---------------------------------------------------------------------

    public function test_ai_is_off_by_default_and_nothing_is_sent(): void
    {
        Http::fake();
        $result = $this->gateway()->run($this->request());

        $this->assertFalse($result['ok']);
        $this->assertTrue($result['blocked']);
        $this->assertSame('disabled', $result['error_code']);
        Http::assertNothingSent();
        $this->assertSame(0, UpworkAiUsage::count());
    }

    public function test_missing_api_key_never_sends(): void
    {
        Http::fake();
        $this->enable();
        $result = (new AiGateway(new OpenAiProvider(null), new AiBudgetGuard(), function () {}))->run($this->request());

        $this->assertSame('not_configured', $result['error_code']);
        Http::assertNothingSent();
    }

    public function test_unpriced_models_are_refused(): void
    {
        Http::fake();
        $this->enable(['model' => 'gpt-expensive-unknown']);

        $this->assertSame('unpriced_model', $this->gateway()->run($this->request())['error_code']);
        Http::assertNothingSent();
    }

    // ---- Successful call -------------------------------------------------------------------------

    public function test_structured_request_and_usage_record(): void
    {
        Http::fake([self::URL => Http::response($this->answer(['project_type' => 'SaaS', 'confidence' => 0.8]))]);
        $this->enable();

        $result = $this->gateway()->run($this->request(), null);

        $this->assertTrue($result['ok']);
        $this->assertSame(['project_type' => 'SaaS', 'confidence' => 0.8], $result['data']);

        Http::assertSent(function (Request $r) {
            $b = $r->data();
            return $r->url() === self::URL
                && $r->hasHeader('Authorization', 'Bearer sk-test-key')
                && $b['model'] === 'gpt-5-nano'
                && $b['input'][0]['role'] === 'system' && str_starts_with($b['input'][0]['content'], 'Code4Each capabilities.')
                && $b['input'][1]['role'] === 'user' && $b['input'][1]['content'] === 'Job: Laravel SaaS dashboard.'
                && $b['text']['format']['type'] === 'json_schema' && $b['text']['format']['strict'] === true
                && $b['text']['format']['name'] === 'opportunity_qualification'
                && $b['store'] === false
                && $b['max_output_tokens'] === 800
                && $b['reasoning'] === ['effort' => 'minimal']
                && !array_key_exists('temperature', $b)
                && $b['prompt_cache_key'] === 'qualify:qualify-v1';
        });

        $usage = UpworkAiUsage::first();
        $this->assertSame('succeeded', $usage->status);
        $this->assertSame([1200, 800, 300, 100], [$usage->input_tokens, $usage->cached_input_tokens, $usage->output_tokens, $usage->reasoning_tokens]);
        // 400 uncached x 0.05 + 800 cached x 0.005 + 300 out x 0.40, per 1M tokens
        $this->assertEqualsWithDelta((400 * 0.05 + 800 * 0.005 + 300 * 0.40) / 1e6, $usage->estimated_cost_usd, 1e-9);
        $this->assertGreaterThan($usage->estimated_cost_usd, $usage->reserved_cost_usd, 'the reservation was the worst case');
        $this->assertSame('openai', $usage->provider);
        $this->assertSame('qualify-v1', $usage->prompt_version);
    }

    // ---- Failures never break anything --------------------------------------------------------------

    public function test_malformed_answer_is_retried_once(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => 'not json {']]]], 'usage' => ['input_tokens' => 10, 'output_tokens' => 5]])
            ->push($this->answer(['project_type' => 'API', 'confidence' => 0.6]))]);
        $this->enable();

        $result = $this->gateway()->run($this->request(), null);

        $this->assertTrue($result['ok']);
        $this->assertSame(2, $result['attempts']);
        $this->assertSame(['failed', 'succeeded'], UpworkAiUsage::orderBy('id')->pluck('status')->all());
        $this->assertSame('malformed', UpworkAiUsage::orderBy('id')->first()->error_code);
    }

    public function test_schema_violations_are_rejected_then_marked_failed(): void
    {
        Http::fake([self::URL => Http::response($this->answer(['project_type' => 'SaaS', 'confidence' => 7, 'extra' => 1]))]);
        $this->enable();

        $result = $this->gateway()->run($this->request(), null);

        $this->assertFalse($result['ok']);
        $this->assertSame('schema_invalid', $result['error_code']);
        $this->assertStringContainsString('above maximum', $result['error']);
        $this->assertSame(2, UpworkAiUsage::where('status', 'failed')->count(), 'one retry, then failed');
    }

    public function test_error_classification_and_retry_policy(): void
    {
        $this->enable();
        $cases = [
            'rate_limited' => [Http::response(['error' => ['code' => 'rate_limit_exceeded', 'message' => 'slow down']], 429), 2],
            'quota_exhausted' => [Http::response(['error' => ['code' => 'insufficient_quota', 'message' => 'no credits']], 429), 1],
            'auth' => [Http::response(['error' => ['message' => 'bad key']], 401), 1],
            'server_error' => [Http::response(['error' => ['message' => 'oops']], 503), 2],
            'refusal' => [Http::response(['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'refusal', 'refusal' => 'no']]]]]), 1],
            'incomplete' => [Http::response(['status' => 'incomplete', 'incomplete_details' => ['reason' => 'max_output_tokens']]), 1],
        ];

        foreach ($cases as $code => [$response, $expectedAttempts]) {
            UpworkAiUsage::query()->delete();
            Http::swap(new \Illuminate\Http\Client\Factory()); // Laravel 8 appends fakes; start clean per case
            Http::fake([self::URL => $response]);
            $result = $this->gateway()->run($this->request(), null);

            $this->assertSame($code, $result['error_code'], $code);
            $this->assertSame($expectedAttempts, $result['attempts'], "{$code} attempts");
            $this->assertSame($expectedAttempts, UpworkAiUsage::where('status', 'failed')->count(), "{$code} usage rows");
        }
    }

    public function test_network_errors_are_retried(): void
    {
        $this->enable();
        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $result = $this->gateway()->run($this->request(), null);

        $this->assertSame('timeout', $result['error_code']);
        $this->assertSame(2, $result['attempts']);
    }

    // ---- Budgets and limits ----------------------------------------------------------------------------

    public function test_monthly_budget_stops_ai_before_any_request(): void
    {
        Http::fake();
        $this->enable(['monthly_budget_usd' => 0.00001]);

        $result = $this->gateway()->run($this->request(), null);

        $this->assertSame('monthly_budget_usd', $result['error_code']);
        Http::assertNothingSent();

        // Once the month's estimated cost reaches the budget, the UI message says so.
        UpworkAiUsage::create(['provider' => 'openai', 'model' => 'gpt-5-nano', 'request_type' => 'qualify', 'status' => 'succeeded', 'estimated_cost_usd' => 0.00002]);
        $this->assertSame('AI monthly budget reached — local scoring continues.', (new AiBudgetGuard())->status()['blocked']);
    }

    public function test_each_limit_is_enforced(): void
    {
        Http::fake([self::URL => Http::response($this->answer(['project_type' => 'SaaS', 'confidence' => 0.5]))]);
        $limits = [
            'daily_request_limit' => ['daily_request_limit' => 1],
            'monthly_request_limit' => ['monthly_request_limit' => 1],
            // first call: ~1,000 tokens / ~$0.00033 reserved, 1,500 tokens / ~$0.000144 actual
            'daily_token_limit' => ['daily_token_limit' => 2400],
            'daily_budget_usd' => ['daily_budget_usd' => 0.0004],
        ];

        foreach ($limits as $expected => $overrides) {
            UpworkAiUsage::query()->delete();
            UpworkSetting::query()->where('key', 'like', 'ai.%')->delete();
            $this->enable($overrides);

            $first = $this->gateway()->run($this->request(), null);
            $second = $this->gateway()->run($this->request(), null);

            $this->assertTrue($first['ok'], "{$expected}: first call fits");
            $this->assertSame($expected, $second['error_code'], "{$expected}: second call refused");
        }
    }

    public function test_per_request_token_limit(): void
    {
        Http::fake();
        $this->enable(['max_input_tokens' => 500]);

        $result = $this->gateway()->run($this->request(str_repeat('A long job description. ', 400)), null);

        $this->assertSame('job_token_limit', $result['error_code']);
        Http::assertNothingSent();
    }

    public function test_per_job_retry_limit_across_runs(): void
    {
        Http::fake([self::URL => Http::response(['error' => ['message' => 'down']], 503)]);
        $this->enable();
        $job = $this->opportunity();

        $this->gateway()->run($this->request(), $job->id);           // 2 attempts (1 + 1 retry)
        $again = $this->gateway()->run($this->request(), $job->id);  // no attempts left today

        $this->assertSame('job_retry_limit', $again['error_code']);
        $this->assertSame(2, UpworkAiUsage::where('opportunity_id', $job->id)->count());
    }

    public function test_reservations_count_until_finished_so_parallel_calls_cannot_overshoot(): void
    {
        $this->enable(['monthly_budget_usd' => 0.0005]); // one worst-case reservation (~$0.00033) fits, two do not
        $guard = new AiBudgetGuard();

        $first = $guard->reserve('openai', 'gpt-5-nano', $this->request(), null);  // in flight
        $this->assertSame('reserved', $first->status);

        try {
            (new AiBudgetGuard())->reserve('openai', 'gpt-5-nano', $this->request(), null);
            $this->fail('second reservation should not fit while the first is in flight');
        } catch (\App\Services\Upwork\Ai\AiBudgetExceeded $e) {
            $this->assertSame('monthly_budget_usd', $e->reason);
        }

        // A crashed call is abandoned but keeps counting at its worst case.
        $this->travel(20)->minutes();
        try {
            (new AiBudgetGuard())->reserve('openai', 'gpt-5-nano', $this->request(), null);
            $this->fail('abandoned reservation must still count');
        } catch (\App\Services\Upwork\Ai\AiBudgetExceeded $e) {
            $this->assertSame('abandoned', $first->fresh()->status);
        }
    }

    public function test_budget_windows_are_utc_calendar_month(): void
    {
        $this->enable();
        UpworkAiUsage::create(['provider' => 'openai', 'model' => 'gpt-5-nano', 'request_type' => 'qualify', 'status' => 'succeeded',
            'estimated_cost_usd' => 1.0, 'input_tokens' => 10, 'output_tokens' => 10]);
        $this->travel(40)->days();

        $status = (new AiBudgetGuard())->status();
        $this->assertSame(0, $status['month']['requests'], 'last month does not count');
        $this->assertSame(5.0, $status['monthly_remaining_usd']);
    }

    // ---- Pricing & schema validation ----------------------------------------------------------------------

    public function test_pricing_math(): void
    {
        $rates = AiPricing::rates('openai', 'gpt-5-nano');
        $this->assertSame(['input' => 0.05, 'cached_input' => 0.005, 'output' => 0.40], $rates);
        $this->assertNull(AiPricing::rates('openai', 'nope'));
        $this->assertEqualsWithDelta((1000 * 0.05 + 500 * 0.40) / 1e6, AiPricing::worstCase($rates, 1000, 500), 1e-12);
        $this->assertEqualsWithDelta((200 * 0.05 + 800 * 0.005 + 100 * 0.40) / 1e6, AiPricing::actual($rates, ['input' => 1000, 'cached_input' => 800, 'output' => 100]), 1e-12);
    }

    public function test_json_schema_validator(): void
    {
        $schema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['a', 'b', 'c'], 'properties' => [
            'a' => ['type' => 'string', 'enum' => ['FACT', 'INFERENCE']],
            'b' => ['type' => ['integer', 'null'], 'minimum' => 0],
            'c' => ['type' => 'array', 'items' => ['type' => 'string']],
        ]];

        $this->assertSame([], JsonSchemaValidator::validate(['a' => 'FACT', 'b' => null, 'c' => ['x']], $schema));
        $errors = JsonSchemaValidator::validate(['a' => 'GUESS', 'b' => -1, 'c' => [1], 'd' => true], $schema);
        $this->assertCount(4, $errors);
        $this->assertNotEmpty(JsonSchemaValidator::validate(['a' => 'FACT'], $schema), 'missing required fields');
    }

    // ---- Settings UI & status ----------------------------------------------------------------------------------

    public function test_ai_settings_page_access_validation_and_audit(): void
    {
        config(['upwork.ai.providers.openai.api_key' => 'sk-super-secret-123']);
        $form = ['enabled' => 1, 'provider' => 'openai', 'model' => 'gpt-5-nano', 'reasoning_effort' => 'minimal', 'temperature' => '',
            'min_local_score' => 55, 'max_input_tokens' => 6000, 'max_output_tokens' => 1500, 'max_retries' => 1, 'timeout_seconds' => 60,
            'daily_request_limit' => 300, 'monthly_request_limit' => 6000, 'daily_token_limit' => 1500000, 'monthly_token_limit' => 20000000,
            'daily_budget_usd' => 0.5, 'monthly_budget_usd' => 5];

        $bde = $this->bde();
        $this->actingAs($bde)->get(route('upwork.config.ai.edit'))->assertOk()->assertSee('Read-only')->assertSee('configured')
            ->assertDontSee('sk-super-secret-123')->assertSee('gpt-5-nano');
        $this->actingAs($bde)->put(route('upwork.config.ai.update'), $form)->assertForbidden();

        $admin = $this->superAdmin();
        $this->actingAs($admin)->put(route('upwork.config.ai.update'), array_merge($form, ['model' => 'gpt-unpriced']))->assertSessionHasErrors('model');
        $this->actingAs($admin)->put(route('upwork.config.ai.update'), array_merge($form, ['daily_budget_usd' => 9]))->assertSessionHasErrors('daily_budget_usd');
        $this->actingAs($admin)->put(route('upwork.config.ai.update'), $form)->assertSessionHas('success', 'AI settings saved.');

        $this->assertTrue(UpworkSetting::get('ai.enabled'));
        $this->assertSame([false, true], UpworkConfigLog::where('label', 'AI settings')->latest('id')->first()->changes['ai.enabled']);
    }

    public function test_status_command_makes_no_request(): void
    {
        Http::fake();
        $this->artisan('upwork:ai-status')
            ->expectsOutput('AI disabled · provider openai (NOT configured) · model gpt-5-nano')
            ->expectsOutput('AI is disabled — local scoring continues.')
            ->assertExitCode(0);
        Http::assertNothingSent();
    }
}
