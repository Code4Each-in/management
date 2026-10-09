<?php

namespace App\Services\Upwork\Ai\Providers;

use App\Services\Upwork\Ai\AiRequest;
use App\Services\Upwork\Ai\AiResponse;
use App\Services\Upwork\Ai\Contracts\AiProviderInterface;

/**
 * Development and test provider: answers instantly with a deterministic,
 * schema-valid result and makes no network calls. It is refused in
 * production (see AiGateway::providerFor).
 *
 * FAKE_AI_MODE=rate_limited|server_error|malformed|quota_exhausted simulates failures.
 */
class FakeProvider implements AiProviderInterface
{
    /** @var string|null */
    private $mode;

    public function __construct(?string $mode = null)
    {
        $this->mode = $mode;
    }

    public function name(): string
    {
        return 'fake';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function structured(AiRequest $request, string $model, array $options = []): AiResponse
    {
        $usage = ['input' => $request->estimatedInputTokens(), 'cached_input' => 0, 'output' => 350, 'reasoning' => 0];

        switch ($this->mode) {
            case 'rate_limited':
                return AiResponse::failure('rate_limited', 'Fake: rate limited.', true);
            case 'server_error':
                return AiResponse::failure('server_error', 'Fake: server error.', true);
            case 'quota_exhausted':
                return AiResponse::failure('quota_exhausted', 'Fake: insufficient quota.', false);
            case 'malformed':
                return AiResponse::success(['unexpected' => true], $usage, 5);
        }

        return AiResponse::success($this->answer($request->schema, $request->dynamicContext), $usage, 5);
    }

    /** Minimal valid value for a schema node, with a few realistic, input-derived fields. */
    private function answer(array $schema, string $input, string $key = '')
    {
        $types = (array) ($schema['type'] ?? 'object');
        $seed = hexdec(substr(md5($input), 0, 6));

        if (isset($schema['enum'])) {
            $options = array_values(array_filter($schema['enum'], fn($v) => $v !== null));
            if ($key === 'recommendation') {
                $fit = 40 + $seed % 51;
                return $fit >= 80 ? 'apply' : ($fit >= 65 ? 'review' : 'skip');
            }
            return $options[0] ?? null;
        }
        if (in_array('object', $types, true)) {
            $out = [];
            foreach ($schema['properties'] ?? [] as $name => $child) {
                $out[$name] = $this->answer($child, $input, $name);
            }
            return $out;
        }
        if (in_array('array', $types, true)) {
            if ($key === 'claims') {
                return [
                    ['statement' => 'Fake analysis for development; not a real AI answer.', 'kind' => 'ASSUMPTION', 'evidence' => null],
                    ['statement' => 'Client budget for the full scope', 'kind' => 'UNKNOWN', 'evidence' => null],
                ];
            }
            return [];
        }
        if (in_array('integer', $types, true)) {
            if ($key === 'fit_score') {
                return 40 + $seed % 51;
            }
            return in_array('null', $types, true) ? null : (int) ($schema['minimum'] ?? 0);
        }
        if (in_array('number', $types, true)) {
            return $key === 'confidence' ? 0.7 : (float) ($schema['minimum'] ?? 0);
        }
        if (in_array('boolean', $types, true)) {
            return false;
        }
        if (in_array('string', $types, true)) {
            return in_array('null', $types, true) ? null : ($key === 'summary' ? 'Fake summary (development provider).' : 'fake');
        }

        return null;
    }
}
