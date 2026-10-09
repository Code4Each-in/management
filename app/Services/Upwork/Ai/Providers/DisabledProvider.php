<?php

namespace App\Services\Upwork\Ai\Providers;

use App\Services\Upwork\Ai\AiRequest;
use App\Services\Upwork\Ai\AiResponse;
use App\Services\Upwork\Ai\Contracts\AiProviderInterface;

/** Used when AI is switched off or the provider is unknown: never calls anything. */
class DisabledProvider implements AiProviderInterface
{
    public function name(): string
    {
        return 'disabled';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function structured(AiRequest $request, string $model, array $options = []): AiResponse
    {
        return AiResponse::failure('disabled', 'AI is disabled.', false);
    }
}
