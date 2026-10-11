<?php

namespace App\Services\Upwork\Ai;

/** Estimated cost from configured prices (USD per 1M tokens). An unpriced model has no estimate and may not be used. */
class AiPricing
{
    public static function rates(string $provider, string $model): ?array
    {
        // The development fake provider is priced like OpenAI, so budgets and
        // limits behave in a dry run exactly as they would for real.
        if ($provider === 'fake') {
            $provider = 'openai';
        }
        $rates = config("upwork.ai.pricing.{$provider}.{$model}");

        return is_array($rates) && isset($rates['input'], $rates['output']) ? $rates : null;
    }

    public static function models(string $provider): array
    {
        if ($provider === 'fake') {
            $provider = 'openai';
        }
        return array_keys((array) config("upwork.ai.pricing.{$provider}", []));
    }

    /** Worst case before a call: every input token uncached, the full output allowance used. */
    public static function worstCase(array $rates, int $inputTokens, int $maxOutputTokens): float
    {
        return ($inputTokens * $rates['input'] + $maxOutputTokens * $rates['output']) / 1000000;
    }

    /** After a call: cached input at the cached rate; reasoning tokens are billed as output. */
    public static function actual(array $rates, array $usage): float
    {
        $cached = min($usage['cached_input'] ?? 0, $usage['input'] ?? 0);
        $uncached = ($usage['input'] ?? 0) - $cached;
        $cachedRate = $rates['cached_input'] ?? $rates['input'];

        return ($uncached * $rates['input'] + $cached * $cachedRate + ($usage['output'] ?? 0) * $rates['output']) / 1000000;
    }
}
