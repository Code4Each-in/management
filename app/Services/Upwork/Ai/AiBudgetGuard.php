<?php

namespace App\Services\Upwork\Ai;

use App\Models\UpworkAiUsage;
use App\Models\UpworkSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Hard limits for automated AI. Before every call the worst-case cost and
 * tokens are RESERVED under a lock, so concurrent jobs can never overshoot;
 * after the call the reservation is replaced by the actual usage. Windows are
 * UTC calendar days/months (matching provider billing). Reaching a limit stops
 * AI calls only - discovery and local scoring continue. Nothing here raises a
 * budget, buys credits or switches to a more expensive model.
 */
class AiBudgetGuard
{
    const LOCK_KEY = 'ai.lock';

    /** @var array settings snapshot */
    private $s;

    public function __construct(?array $settings = null)
    {
        $this->s = $settings ?? UpworkSetting::allWithDefaults();
    }

    public function setting(string $key)
    {
        return $this->s["ai.{$key}"] ?? null;
    }

    /**
     * Reserve worst-case usage for one call or throw AiBudgetExceeded.
     */
    public function reserve(string $provider, string $model, AiRequest $request, ?int $opportunityId, int $attempt = 1): UpworkAiUsage
    {
        if (!$this->setting('enabled')) {
            throw new AiBudgetExceeded('disabled', 'AI is disabled in the PMS.');
        }
        $rates = AiPricing::rates($provider, $model);
        if (!$rates) {
            throw new AiBudgetExceeded('unpriced_model', "Model '{$model}' has no configured price, so its cost cannot be controlled.");
        }

        $caps = $this->capsFor($request->type);
        $inputTokens = $request->estimatedInputTokens();
        $maxOutput = (int) min($request->maxOutputTokens ?? PHP_INT_MAX, $caps['output']);
        if ($inputTokens > $caps['input']) {
            throw new AiBudgetExceeded('job_token_limit', "Request needs ~{$inputTokens} input tokens; the per-request limit is {$caps['input']}.");
        }
        $tokens = $inputTokens + $maxOutput;
        $cost = AiPricing::worstCase($rates, $inputTokens, $maxOutput);

        UpworkSetting::firstOrCreate(['key' => self::LOCK_KEY], ['value' => null]);
        // Outside the transaction so it persists even when this reservation is refused.
        $this->abandonStaleReservations();

        return DB::transaction(function () use ($provider, $model, $request, $opportunityId, $attempt, $tokens, $cost) {
            UpworkSetting::where('key', self::LOCK_KEY)->lockForUpdate()->first();

            if ($opportunityId !== null) {
                $attemptsToday = UpworkAiUsage::where('opportunity_id', $opportunityId)->where('request_type', $request->type)
                    ->where('created_at', '>=', $this->dayStart())->count();
                if ($attemptsToday >= 1 + (int) $this->setting('max_retries')) {
                    throw new AiBudgetExceeded('job_retry_limit', "Opportunity {$opportunityId} already had {$attemptsToday} {$request->type} attempts today.");
                }
            }

            $day = $this->totals($this->dayStart());
            $month = $this->totals($this->monthStart());
            $checks = [
                ['daily_request_limit', $day['requests'] + 1, 'requests today'],
                ['monthly_request_limit', $month['requests'] + 1, 'requests this month'],
                ['daily_token_limit', $day['tokens'] + $tokens, 'tokens today'],
                ['monthly_token_limit', $month['tokens'] + $tokens, 'tokens this month'],
                ['daily_budget_usd', $day['cost'] + $cost, 'USD today'],
                ['monthly_budget_usd', $month['cost'] + $cost, 'USD this month'],
            ];
            if ($request->type === self::TYPE_DEEP) {
                // Deep analysis has its own, smaller allowance inside the overall budget.
                $deepDay = $this->totals($this->dayStart(), self::TYPE_DEEP);
                $deepMonth = $this->totals($this->monthStart(), self::TYPE_DEEP);
                $checks[] = ['deep_daily_request_limit', $deepDay['requests'] + 1, 'deep analyses today'];
                $checks[] = ['deep_monthly_budget_usd', $deepMonth['cost'] + $cost, 'USD on deep analyses this month'];
            }
            foreach ($checks as [$key, $wouldBe, $label]) {
                $limit = (float) $this->setting($key);
                if ($wouldBe > $limit) {
                    throw new AiBudgetExceeded($key, sprintf('AI %s limit reached (%s %s would exceed %s).', str_replace('_', ' ', $key), round($wouldBe, 6), $label, $limit));
                }
            }

            return UpworkAiUsage::create([
                'provider' => $provider, 'model' => $model, 'request_type' => $request->type, 'prompt_version' => $request->promptVersion,
                'opportunity_id' => $opportunityId, 'status' => UpworkAiUsage::STATUS_RESERVED, 'attempt' => $attempt,
                'reserved_cost_usd' => round($cost, 6), 'reserved_tokens' => $tokens,
            ]);
        });
    }

    /** Replace a reservation with what the call actually used. */
    public function finalize(UpworkAiUsage $usage, AiResponse $response): UpworkAiUsage
    {
        $rates = AiPricing::rates($usage->provider, $usage->model) ?? ['input' => 0, 'output' => 0];
        $usage->forceFill([
            'status' => $response->ok ? UpworkAiUsage::STATUS_SUCCEEDED : UpworkAiUsage::STATUS_FAILED,
            'input_tokens' => $response->usage['input'],
            'cached_input_tokens' => $response->usage['cached_input'],
            'output_tokens' => $response->usage['output'],
            'reasoning_tokens' => $response->usage['reasoning'],
            'estimated_cost_usd' => round(AiPricing::actual($rates, $response->usage), 6),
            'duration_ms' => $response->durationMs,
            'error_code' => $response->errorCode,
            'error' => $response->error !== null ? mb_substr($response->error, 0, 2000) : null,
        ])->save();

        return $usage;
    }

    const TYPE_DEEP = 'deep';

    /** Per-request token caps: deep analysis has its own (larger) ones. */
    public function capsFor(string $type): array
    {
        $deep = $type === self::TYPE_DEEP;

        return [
            'input' => (int) $this->setting($deep ? 'deep_max_input_tokens' : 'max_input_tokens'),
            'output' => (int) $this->setting($deep ? 'deep_max_output_tokens' : 'max_output_tokens'),
        ];
    }

    /** Totals counted against limits since $from (optionally one request type). */
    public function totals(Carbon $from, ?string $type = null): array
    {
        $rows = UpworkAiUsage::where('created_at', '>=', $from)->when($type, fn($q) => $q->where('request_type', $type))->get(['status', 'reserved_cost_usd', 'reserved_tokens', 'estimated_cost_usd', 'input_tokens', 'output_tokens']);

        return [
            'requests' => $rows->count(),
            'tokens' => (int) $rows->sum(fn($r) => in_array($r->status, [UpworkAiUsage::STATUS_RESERVED, UpworkAiUsage::STATUS_ABANDONED], true)
                ? $r->reserved_tokens : (int) $r->input_tokens + (int) $r->output_tokens),
            'cost' => round((float) $rows->sum(fn($r) => $r->budget_cost), 6),
        ];
    }

    /** Snapshot for the UI and reports. */
    public function status(): array
    {
        $day = $this->totals($this->dayStart());
        $month = $this->totals($this->monthStart());
        $monthlyBudget = (float) $this->setting('monthly_budget_usd');
        $dailyBudget = (float) $this->setting('daily_budget_usd');

        $blocked = null;
        if (!$this->setting('enabled')) {
            $blocked = 'AI is disabled — local scoring continues.';
        } elseif ($month['cost'] >= $monthlyBudget || $month['requests'] >= (int) $this->setting('monthly_request_limit') || $month['tokens'] >= (int) $this->setting('monthly_token_limit')) {
            $blocked = 'AI monthly budget reached — local scoring continues.';
        } elseif ($day['cost'] >= $dailyBudget || $day['requests'] >= (int) $this->setting('daily_request_limit') || $day['tokens'] >= (int) $this->setting('daily_token_limit')) {
            $blocked = 'AI daily limit reached — local scoring continues; AI resumes tomorrow (UTC).';
        }

        return [
            'enabled' => (bool) $this->setting('enabled'),
            'provider' => $this->setting('provider'),
            'model' => $this->setting('model'),
            'today' => $day,
            'month' => $month,
            'daily_budget_usd' => $dailyBudget,
            'monthly_budget_usd' => $monthlyBudget,
            'monthly_remaining_usd' => max(0, round($monthlyBudget - $month['cost'], 6)),
            'deep' => [
                'today' => $this->totals($this->dayStart(), self::TYPE_DEEP),
                'month' => $this->totals($this->monthStart(), self::TYPE_DEEP),
                'daily_request_limit' => (int) $this->setting('deep_daily_request_limit'),
                'monthly_budget_usd' => (float) $this->setting('deep_monthly_budget_usd'),
            ],
            'blocked' => $blocked,
        ];
    }

    public function dayStart(): Carbon
    {
        return now()->utc()->startOfDay()->setTimezone(config('app.timezone'));
    }

    public function monthStart(): Carbon
    {
        return now()->utc()->startOfMonth()->setTimezone(config('app.timezone'));
    }

    /** Reservations from crashed calls keep their worst-case cost but stop looking "in flight". */
    private function abandonStaleReservations(): void
    {
        UpworkAiUsage::where('status', UpworkAiUsage::STATUS_RESERVED)
            ->where('created_at', '<', now()->subMinutes((int) config('upwork.ai.reservation_timeout_minutes', 15)))
            ->update(['status' => UpworkAiUsage::STATUS_ABANDONED, 'error_code' => 'abandoned', 'updated_at' => now()]);
    }
}
