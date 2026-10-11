<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\UpworkAiUsage;
use App\Models\UpworkConfigLog;
use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Ai\AiGateway;
use App\Services\Upwork\Ai\AiPricing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** AI provider, model, limits and budgets, plus current usage. Secrets stay in .env and are never shown. */
class AiSettingsController extends Controller
{
    /** form field => [setting key, rules, label, help] */
    const FIELDS = [
        'min_local_score' => ['ai.min_local_score', ['required', 'numeric', 'min:0', 'max:100'], 'Minimum local score for AI', 'Only jobs scoring at least this locally (and not hard-rejected) are sent to AI.'],
        'max_input_tokens' => ['ai.max_input_tokens', ['required', 'integer', 'min:500', 'max:50000'], 'Max input tokens per request', 'Job descriptions are shortened to fit.'],
        'max_output_tokens' => ['ai.max_output_tokens', ['required', 'integer', 'min:100', 'max:8000'], 'Max output tokens per request', 'Includes reasoning tokens.'],
        'max_retries' => ['ai.max_retries', ['required', 'integer', 'min:0', 'max:3'], 'Retries per job per day', 'For transient errors or malformed answers only.'],
        'timeout_seconds' => ['ai.timeout_seconds', ['required', 'integer', 'min:10', 'max:300'], 'Request timeout (seconds)', null],
        'daily_request_limit' => ['ai.daily_request_limit', ['required', 'integer', 'min:0', 'max:100000'], 'Requests per day', null],
        'monthly_request_limit' => ['ai.monthly_request_limit', ['required', 'integer', 'min:0', 'max:1000000'], 'Requests per month', null],
        'daily_token_limit' => ['ai.daily_token_limit', ['required', 'integer', 'min:0', 'max:100000000'], 'Tokens per day', null],
        'monthly_token_limit' => ['ai.monthly_token_limit', ['required', 'integer', 'min:0', 'max:1000000000'], 'Tokens per month', null],
        'daily_budget_usd' => ['ai.daily_budget_usd', ['required', 'numeric', 'min:0', 'max:1000'], 'Maximum cost per day (USD)', 'Estimated from configured prices; worst case is reserved before each call.'],
        'monthly_budget_usd' => ['ai.monthly_budget_usd', ['required', 'numeric', 'min:0', 'max:10000'], 'Monthly AI budget (USD)', 'Hard stop. Never raised automatically.'],
        'min_confidence' => ['ai.min_confidence', ['nullable', 'numeric', 'min:0', 'max:1'], 'Confidence needed to affect scoring', '0–1. Below this, the AI answer is shown but scoring rules ignore it.'],
        'max_analyses_per_job_per_day' => ['ai.max_analyses_per_job_per_day', ['nullable', 'integer', 'min:1', 'max:10'], 'Analyses per job per day', 'All types together, including Regenerate.'],
        'deep_max_input_tokens' => ['ai.deep_max_input_tokens', ['nullable', 'integer', 'min:500', 'max:50000'], 'Deep analysis: max input tokens', null],
        'deep_max_output_tokens' => ['ai.deep_max_output_tokens', ['nullable', 'integer', 'min:100', 'max:16000'], 'Deep analysis: max output tokens', null],
        'deep_daily_request_limit' => ['ai.deep_daily_request_limit', ['nullable', 'integer', 'min:0', 'max:1000'], 'Deep analyses per day', 'Manual only; counts toward the overall limits too.'],
        'deep_monthly_budget_usd' => ['ai.deep_monthly_budget_usd', ['nullable', 'numeric', 'min:0', 'max:10000'], 'Deep analysis monthly allowance (USD)', 'Part of the monthly budget, not on top of it.'],
    ];

    const REASONING = ['' => 'Not sent', 'minimal' => 'Minimal', 'low' => 'Low', 'medium' => 'Medium'];

    public function edit()
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        $settings = UpworkSetting::allWithDefaults();
        $provider = (string) $settings['ai.provider'];

        return view('upwork.config.ai', [
            'settings' => $settings,
            'fields' => self::FIELDS,
            'reasoning' => self::REASONING,
            'providers' => AiGateway::availableProviders(),
            'pricing' => (array) config('upwork.ai.pricing.' . ($provider === 'fake' ? 'openai' : $provider), []),
            'pricingCheckedAt' => config('upwork.ai.pricing_checked_at'),
            'status' => AiGateway::fromSettings()->status(),
            'recent' => UpworkAiUsage::with('opportunity:id,title')->latest('id')->limit(20)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $provider = (string) $request->input('provider');
        $rules = array_map(fn($f) => $f[1], self::FIELDS) + [
            'enabled' => ['boolean'],
            'provider' => ['required', Rule::in(AiGateway::availableProviders())],
            'model' => ['required', Rule::in(AiPricing::models($provider))],
            'reasoning_effort' => ['nullable', Rule::in(array_keys(self::REASONING))],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'auto_analyze' => ['nullable', 'boolean'],
        ];
        $v = $request->validate($rules, ['model.in' => 'Choose a model with a configured price (prices live in config/upwork.php).']);

        foreach ([['daily_budget_usd', 'monthly_budget_usd'], ['daily_request_limit', 'monthly_request_limit'], ['daily_token_limit', 'monthly_token_limit']] as [$daily, $monthly]) {
            if ((float) $v[$daily] > (float) $v[$monthly]) {
                throw ValidationException::withMessages([$daily => self::FIELDS[$daily][2] . ' cannot be higher than ' . strtolower(self::FIELDS[$monthly][2]) . '.']);
            }
        }

        $new = [
            'ai.enabled' => $request->boolean('enabled'),
            'ai.provider' => $v['provider'],
            'ai.model' => $v['model'],
            'ai.reasoning_effort' => $v['reasoning_effort'] ?? '',
            'ai.temperature' => isset($v['temperature']) && $v['temperature'] !== '' ? (float) $v['temperature'] : null,
        ];
        if ($request->has('auto_analyze')) {
            $new['ai.auto_analyze'] = $request->boolean('auto_analyze');
        }
        foreach (self::FIELDS as $field => [$key]) {
            if (isset($v[$field]) && $v[$field] !== '') {
                $new[$key] = $v[$field] + 0;
            }
        }
        if (isset($new['ai.deep_monthly_budget_usd']) && $new['ai.deep_monthly_budget_usd'] > $new['ai.monthly_budget_usd']) {
            throw ValidationException::withMessages(['deep_monthly_budget_usd' => 'The deep analysis allowance is part of the monthly budget, so it cannot be higher.']);
        }

        $current = UpworkSetting::allWithDefaults();
        $changes = [];
        foreach ($new as $key => $value) {
            if (($current[$key] ?? null) != $value || (($current[$key] ?? null) === null) !== ($value === null)) {
                $changes[$key] = [$current[$key] ?? null, $value];
                UpworkSetting::set($key, $value, $request->user()->id);
            }
        }
        if ($changes) {
            UpworkConfigLog::record($request->user()->id, 'setting', null, 'AI settings', 'updated', $changes);
        }

        return redirect()->route('upwork.config.ai.edit')->with('success', $changes ? 'AI settings saved.' : 'No changes.');
    }
}
