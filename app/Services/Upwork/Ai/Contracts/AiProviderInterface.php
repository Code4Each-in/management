<?php

namespace App\Services\Upwork\Ai\Contracts;

use App\Services\Upwork\Ai\AiRequest;
use App\Services\Upwork\Ai\AiResponse;

/**
 * An AI provider (OpenAI now; others later). Providers only transport a
 * structured request and parse the answer: budgets, retries and usage logging
 * live in AiGateway, and the opportunity-specific capabilities (analyze,
 * classify, estimate, ...) are built on top of one structured call so a job
 * costs one request, not one per capability.
 */
interface AiProviderInterface
{
    /** Short provider id, e.g. "openai". */
    public function name(): string;

    /** True when credentials are present (it never makes a request to find out). */
    public function isConfigured(): bool;

    /** Send one structured request; never throws for provider/network errors. */
    public function structured(AiRequest $request, string $model, array $options = []): AiResponse;
}
