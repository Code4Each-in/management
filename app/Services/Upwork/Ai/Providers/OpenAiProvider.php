<?php

namespace App\Services\Upwork\Ai\Providers;

use App\Services\Upwork\Ai\AiRequest;
use App\Services\Upwork\Ai\AiResponse;
use App\Services\Upwork\Notifications\AlertService;
use App\Services\Upwork\Ai\Contracts\AiProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI Responses API with Structured Outputs (strict JSON schema).
 * The static context goes first as the system message so OpenAI's automatic
 * prompt caching can reuse it across jobs; the job-specific part follows.
 */
class OpenAiProvider implements AiProviderInterface
{
    /** @var string|null */
    private $apiKey;
    /** @var string */
    private $baseUrl;

    public function __construct(?string $apiKey, string $baseUrl = 'https://api.openai.com/v1')
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function name(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return is_string($this->apiKey) && trim($this->apiKey) !== '';
    }

    /** The request body, separate so tests can check it without sending anything. */
    public function payload(AiRequest $request, string $model, array $options = []): array
    {
        $body = [
            'model' => $model,
            'input' => [
                ['role' => 'system', 'content' => $request->staticContext],
                ['role' => 'user', 'content' => $request->dynamicContext],
            ],
            'text' => ['format' => [
                'type' => 'json_schema',
                'name' => $request->schemaName,
                'schema' => $request->schema,
                'strict' => true,
            ]],
            'store' => false,
            'prompt_cache_key' => $request->type . ':' . $request->promptVersion,
        ];
        if (!empty($options['max_output_tokens'])) {
            $body['max_output_tokens'] = (int) $options['max_output_tokens'];
        }
        if (!empty($options['reasoning_effort'])) {
            $body['reasoning'] = ['effort' => $options['reasoning_effort']];
        }
        if (isset($options['temperature']) && $options['temperature'] !== '' && $options['temperature'] !== null) {
            $body['temperature'] = (float) $options['temperature'];
        }

        return $body;
    }

    public function structured(AiRequest $request, string $model, array $options = []): AiResponse
    {
        if (!$this->isConfigured()) {
            return AiResponse::failure('not_configured', 'OPENAI_API_KEY is not set.', false);
        }

        $started = microtime(true);
        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout((int) ($options['timeout'] ?? 60))
                ->post($this->baseUrl . '/responses', $this->payload($request, $model, $options));
        } catch (ConnectionException $e) {
            return AiResponse::failure('timeout', 'Could not reach OpenAI: ' . AlertService::scrub($e->getMessage()), true, [], $this->ms($started));
        }
        $ms = $this->ms($started);
        $json = $response->json() ?? [];
        $usage = $this->usage($json['usage'] ?? []);

        if (!$response->successful()) {
            return $this->httpError($response->status(), $json, $usage, $ms);
        }

        if (($json['status'] ?? null) === 'incomplete') {
            $reason = $json['incomplete_details']['reason'] ?? 'unknown';
            return AiResponse::failure('incomplete', "Response incomplete ({$reason}).", false, $usage, $ms);
        }

        $text = null;
        foreach ($json['output'] ?? [] as $item) {
            foreach (($item['type'] ?? null) === 'message' ? ($item['content'] ?? []) : [] as $content) {
                if (($content['type'] ?? null) === 'refusal') {
                    return AiResponse::failure('refusal', 'The model refused: ' . mb_substr((string) ($content['refusal'] ?? ''), 0, 300), false, $usage, $ms);
                }
                if (($content['type'] ?? null) === 'output_text') {
                    $text = ($text ?? '') . $content['text'];
                }
            }
        }

        $data = is_string($text) ? json_decode($text, true) : null;
        if (!is_array($data)) {
            return AiResponse::failure('malformed', 'The answer was not valid JSON.', true, $usage, $ms);
        }

        return AiResponse::success($data, $usage, $ms);
    }

    private function httpError(int $status, array $json, array $usage, int $ms): AiResponse
    {
        $code = $json['error']['code'] ?? $json['error']['type'] ?? null;
        // OpenAI's messages can echo (masked) keys or request details: scrub before storing or showing.
        $message = mb_substr(AlertService::scrub((string) ($json['error']['message'] ?? "HTTP {$status}")), 0, 500);

        if ($status === 401 || $status === 403) {
            return AiResponse::failure('auth', "OpenAI rejected the API key or project (HTTP {$status}). Check OPENAI_API_KEY on the server.", false, $usage, $ms);
        }
        if ($status === 429 && $code === 'insufficient_quota') {
            // Out of credits: never retry, and never fall back to another model.
            return AiResponse::failure('quota_exhausted', $message, false, $usage, $ms);
        }
        if ($status === 429) {
            return AiResponse::failure('rate_limited', $message, true, $usage, $ms);
        }
        if ($status >= 500) {
            return AiResponse::failure('server_error', $message, true, $usage, $ms);
        }

        return AiResponse::failure('bad_request', $message, false, $usage, $ms);
    }

    private function usage(array $u): array
    {
        return [
            'input' => (int) ($u['input_tokens'] ?? 0),
            'cached_input' => (int) ($u['input_tokens_details']['cached_tokens'] ?? 0),
            'output' => (int) ($u['output_tokens'] ?? 0),
            'reasoning' => (int) ($u['output_tokens_details']['reasoning_tokens'] ?? 0),
        ];
    }

    private function ms(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
