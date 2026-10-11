<?php

namespace App\Services\Upwork\Ai;

use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\Contracts\AiProviderInterface;
use App\Services\Upwork\Ai\Providers\DisabledProvider;
use App\Services\Upwork\Ai\Providers\FakeProvider;
use App\Services\Upwork\Ai\Providers\OpenAiProvider;

/**
 * The only way the module calls AI: budget reservation -> provider call ->
 * schema validation -> usage record, with a bounded retry for transient or
 * malformed answers. It never throws on AI problems; callers get a result and
 * carry on with local scoring.
 */
class AiGateway
{
    /** @var AiProviderInterface */
    private $provider;
    /** @var AiBudgetGuard */
    private $guard;
    /** @var callable */
    private $sleep;

    public function __construct(?AiProviderInterface $provider = null, ?AiBudgetGuard $guard = null, ?callable $sleep = null)
    {
        $this->guard = $guard ?? new AiBudgetGuard();
        $this->provider = $provider ?? self::providerFor((string) $this->guard->setting('provider'));
        $this->sleep = $sleep ?? function (int $seconds) { sleep($seconds); };
    }

    public static function providerFor(string $name): AiProviderInterface
    {
        switch ($name) {
            case 'openai':
                return new OpenAiProvider(config('upwork.ai.providers.openai.api_key'), (string) config('upwork.ai.providers.openai.base_url'));
            case 'fake':
                // Development only: never answers with made-up data in production.
                if (!app()->environment('production')) {
                    return new FakeProvider(env('FAKE_AI_MODE'));
                }
        }

        return new DisabledProvider();
    }

    /** Providers an admin may choose here (the fake one only outside production). */
    public static function availableProviders(): array
    {
        $providers = array_keys((array) config('upwork.ai.providers'));
        if (!app()->environment('production')) {
            $providers[] = 'fake';
        }

        return $providers;
    }

    public function provider(): AiProviderInterface
    {
        return $this->provider;
    }

    /**
     * @return array{ok: bool, data: ?array, error_code: ?string, error: ?string, attempts: int, usage_ids: int[], blocked: bool}
     */
    public function run(AiRequest $request, ?int $opportunityId = null): array
    {
        $model = (string) $this->guard->setting('model');
        $options = [
            'max_output_tokens' => min($request->maxOutputTokens ?? PHP_INT_MAX, $this->guard->capsFor($request->type)['output']),
            'reasoning_effort' => $this->guard->setting('reasoning_effort') ?: null,
            'temperature' => $this->guard->setting('temperature'),
            'timeout' => (int) $this->guard->setting('timeout_seconds'),
        ];
        $result = ['ok' => false, 'data' => null, 'error_code' => null, 'error' => null, 'attempts' => 0, 'usage_ids' => [], 'blocked' => false];

        if (!$this->provider->isConfigured()) {
            return ['error_code' => 'not_configured', 'error' => "AI provider '{$this->provider->name()}' is not configured.", 'blocked' => true] + $result;
        }

        $maxAttempts = 1 + max(0, (int) $this->guard->setting('max_retries'));
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $usage = $this->guard->reserve($this->provider->name(), $model, $request, $opportunityId, $attempt);
            } catch (AiBudgetExceeded $e) {
                if ($result['attempts'] > 0) {
                    break; // a retry was refused: report the real failure, not the limit
                }
                return ['error_code' => $e->reason, 'error' => $e->getMessage(), 'blocked' => true, 'attempts' => $result['attempts'], 'usage_ids' => $result['usage_ids']] + $result;
            }

            $response = $this->provider->structured($request, $model, $options);
            if ($response->ok && ($problems = JsonSchemaValidator::validate($response->data, $request->schema))) {
                $response = AiResponse::failure('schema_invalid', 'Answer did not match the schema: ' . implode('; ', array_slice($problems, 0, 5)), true, $response->usage, $response->durationMs);
            }
            $this->guard->finalize($usage, $response);

            $result['attempts'] = $attempt;
            $result['usage_ids'][] = $usage->id;
            if ($response->ok) {
                return ['ok' => true, 'data' => $response->data] + $result;
            }

            $result['error_code'] = $response->errorCode;
            $result['error'] = $response->error;
            if (!$response->retryable || $attempt === $maxAttempts) {
                break;
            }
            ($this->sleep)(min(30, 2 ** $attempt));
        }

        return $result;
    }

    public function status(): array
    {
        return $this->guard->status() + ['configured' => $this->provider->isConfigured()];
    }

    public static function fromSettings(): self
    {
        return new self(null, new AiBudgetGuard(UpworkSetting::allWithDefaults()));
    }
}
