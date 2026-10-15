<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key/value settings for the Upwork engine (agent schedule, notifications).
 * Scoring configuration lives in versioned rulesets, not here.
 */
class UpworkSetting extends Model
{
    const DEFAULTS = [
        'agent.enabled' => false,          // the Mac mini worker does nothing until enabled in the PMS
        'agent.interval_minutes' => 60,
        'agent.lease_minutes' => 15,       // a run holding the lease longer than this is considered dead
        'agent.max_backoff_minutes' => 240,
        'agent.enrich_limit_per_run' => 15, // max detail lookups (find_jobs get) per run
        'notifications.channels' => ['log'],
        'notifications.mail_to' => [],            // email recipients when the mail channel is on
        'alerts.cooldown_minutes' => 360,         // the same operational alert is sent at most once per window
        'alerts.state' => [],                     // open alerts: key => {count, first_at, last_at, last_sent_at}
        // AI qualification (off until explicitly enabled; prices live in config/upwork.php, never here).
        'ai.enabled' => false,
        'ai.provider' => 'openai',
        'ai.model' => 'gpt-5-nano',
        'ai.reasoning_effort' => 'minimal',  // sent only when set; '' omits it
        'ai.temperature' => null,            // sent only when set (some models reject it)
        'ai.min_local_score' => 55,          // only jobs at or above this local score go to AI
        'ai.auto_analyze' => true,           // queue eligible jobs automatically after full details arrive
        'ai.min_confidence' => 0.6,          // AI signals reach scoring rules only at or above this confidence
        'ai.max_analyses_per_job_per_day' => 3, // all request types together, incl. manual regenerate
        'ai.max_input_tokens' => 6000,       // per request (description is truncated to fit)
        'ai.max_output_tokens' => 1500,      // per request
        'ai.max_retries' => 1,               // per job and request type, per day
        'ai.timeout_seconds' => 60,
        'ai.daily_request_limit' => 300,
        'ai.monthly_request_limit' => 6000,
        'ai.daily_token_limit' => 1500000,
        'ai.monthly_token_limit' => 20000000,
        'ai.daily_budget_usd' => 0.50,
        'ai.monthly_budget_usd' => 5.00,
        // Deep analysis: manual only, same model, own allowance inside the budget above.
        'ai.deep_max_input_tokens' => 12000,
        'ai.deep_max_output_tokens' => 3000,
        'ai.deep_daily_request_limit' => 20,
        'ai.deep_monthly_budget_usd' => 1.00,
        // AI proposal drafts: queued only when a person approves a job; off until enabled.
        'ai.proposal_drafts_enabled' => false,
        'ai.proposal_max_input_tokens' => 8000,
        'ai.proposal_max_output_tokens' => 2000,
        'ai.proposal_daily_request_limit' => 20,
        'ai.proposal_monthly_budget_usd' => 1.00, // hard cap, part of ai.monthly_budget_usd
        'proposals.profile' => '',                // approved agency profile / verified experience the AI may cite
        // Individual freelancer profiles (PMS writing personas, never linked to Upwork accounts):
        // [{key, name, summary, technologies: [catalog slugs]}]
        'targeting.individual_profiles' => [],
        // Review SLA: deadline tracking always runs; reminders are sent only when switched on.
        'review.reminders_enabled' => false,
        'discovery.client' => 'graphql',          // graphql (official API) | mcp (Upwork MCP, direct) | fixture outside production
        'mcp.client' => null,                     // registered MCP OAuth client: {client_id, redirect_uri, registered_at}
        'mcp.accounts' => [],                     // accounts from list_accounts: [{org_uid, name, role}]
        'discovery.max_seconds_per_run' => 240,   // server runner stops cleanly before shared-hosting limits
        'discovery.api_pause_ms' => 500,          // pause between Upwork API calls
        'discovery.retry_after_failure_minutes' => 10, // first retry after a failed scheduled run; doubles, capped at the interval
        'discovery.max_queries_per_profile' => 4, // searches per profile per run (rotating through its terms)
        'discovery.max_queries_per_run' => 15,     // hard cap across all profiles in one run
        'discovery.max_searches_per_day' => 400,   // hard cap per day (app timezone)
        'discovery.tier_searches_per_run' => ['hot' => 3, 'normal' => 6, 'deep' => 12, 'exploratory' => 5],
        'discovery.enrich_min_score' => 55,       // search-row score needed before the agent fetches full details
        'discovery.known_ids_per_profile' => 50,  // recent job ids sent to the agent so it can stop paging early
    ];

    protected $fillable = ['key', 'value', 'updated_by'];

    protected $casts = [
        'value' => 'json',
    ];

    public static function get(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();

        if ($row) {
            return $row->value;
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function set(string $key, $value, ?int $userId = null): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $userId]);
    }

    /** All known settings with stored values overriding defaults. */
    public static function allWithDefaults(): array
    {
        return array_merge(self::DEFAULTS, static::pluck('value', 'key')->all());
    }
}
