<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\UpworkClassification;
use App\Models\UpworkConfigLog;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkRuleset;
use App\Models\UpworkSearchProfile;
use App\Models\UpworkSetting;
use App\Models\UpworkTechnology;
use Illuminate\Http\Request;

/** Configuration overview and agent / discovery settings. */
class ConfigController extends Controller
{
    /**
     * Editable settings: form field => [setting key, validation rules, label, help].
     * Ranges keep the agent from being configured into aggressive polling.
     */
    const SETTINGS = [
        'agent_enabled' => ['agent.enabled', ['boolean'], 'Agent enabled', 'When off, scheduled discovery runs are refused. Manual test runs still work.'],
        'agent_interval_minutes' => ['agent.interval_minutes', ['required', 'integer', 'min:10', 'max:240'], 'Discovery interval (minutes)', 'How often the Mac mini starts a cycle. Minimum 10.'],
        'agent_lease_minutes' => ['agent.lease_minutes', ['required', 'integer', 'min:5', 'max:60'], 'Run lease (minutes)', 'A run silent for longer is treated as dead; prevents overlapping runs.'],
        'agent_max_backoff_minutes' => ['agent.max_backoff_minutes', ['required', 'integer', 'min:20', 'max:1440'], 'Maximum backoff (minutes)', 'Upper limit for exponential backoff after failures or rate limits.'],
        'agent_enrich_limit_per_run' => ['agent.enrich_limit_per_run', ['required', 'integer', 'min:0', 'max:50'], 'Detail lookups per run', 'Maximum find_jobs "get" calls per run.'],
        'discovery_max_queries_per_profile' => ['discovery.max_queries_per_profile', ['required', 'integer', 'min:1', 'max:10'], 'Searches per profile per run', 'Profiles rotate through their terms over successive runs.'],
        'discovery_max_queries_per_run' => ['discovery.max_queries_per_run', ['required', 'integer', 'min:1', 'max:60'], 'Searches per run (all profiles)', 'Hard cap on Upwork searches in one cycle.'],
        'discovery_max_searches_per_day' => ['discovery.max_searches_per_day', ['required', 'integer', 'min:1', 'max:2000'], 'Searches per day (all profiles)', 'Daily ceiling on Upwork searches; discovery pauses until midnight once reached.'],
        'discovery_max_seconds_per_run' => ['discovery.max_seconds_per_run', ['nullable', 'integer', 'min:30', 'max:600'], 'Time budget per run (seconds)', 'The server run stops cleanly after this, before shared-hosting limits.'],
        'discovery_enrich_min_score' => ['discovery.enrich_min_score', ['required', 'numeric', 'min:0', 'max:100'], 'Score needed for a detail lookup', 'Search-row score at which the agent fetches full job details.'],
        'discovery_known_ids_per_profile' => ['discovery.known_ids_per_profile', ['required', 'integer', 'min:0', 'max:200'], 'Known job ids sent per profile', 'Lets the agent stop paging when it reaches jobs it already reported.'],
    ];

    /** Per-run search allowance for each search tier. */
    const TIER_ALLOWANCE_MAX = 30;

    const NOTIFICATION_CHANNELS = ['log' => 'Application log', 'mail' => 'Email'];

    public function index()
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        $summary = [
            'profiles' => UpworkSearchProfile::count(),
            'profiles_enabled' => UpworkSearchProfile::where('enabled', true)->count(),
            'technologies' => UpworkTechnology::selectRaw('tier, count(*) as c')->groupBy('tier')->pluck('c', 'tier')->all(),
            'classifications' => UpworkClassification::count(),
            'ruleset' => UpworkRuleset::current(),
            'draft' => UpworkRuleset::draft(),
            'agent_enabled' => (bool) UpworkSetting::get('agent.enabled'),
            'interval' => (int) UpworkSetting::get('agent.interval_minutes'),
            'last_run' => UpworkDiscoveryRun::latest('id')->first(),
            'manual_run' => UpworkSetting::get('discovery.manual_last'),
            'ai' => \App\Services\Upwork\Ai\AiGateway::fromSettings()->status(),
        ];
        $logs = UpworkConfigLog::with('user')->latest('id')->limit(20)->get();

        return view('upwork.config.index', compact('summary', 'logs'));
    }

    /**
     * One manual, read-only discovery run (same as `upwork:discover --manual`).
     * It runs after the response is sent, so the browser does not wait for it
     * (the runner stops itself within discovery.max_seconds_per_run); the
     * outcome, including "skipped", is kept in discovery.manual_last.
     */
    public function runDiscovery(Request $request)
    {
        $this->authorize('runDiscovery', UpworkOpportunity::class);

        $userId = $request->user()->id;
        UpworkConfigLog::record($userId, 'setting', null, 'Upwork discovery', 'manual run started', []);

        dispatch(function () use ($userId) {
            ignore_user_abort(true);
            @set_time_limit(max(300, (int) UpworkSetting::get('discovery.max_seconds_per_run') + 60));
            $out = app(\App\Services\Upwork\Discovery\ServerDiscoveryRunner::class)->run('manual');
            UpworkSetting::set('discovery.manual_last', [
                'status' => $out['status'], 'message' => $out['message'], 'run' => $out['run'],
                'stats' => $out['stats'], 'by' => $userId, 'at' => now()->toIso8601String(),
            ], $userId);
        })->afterResponse();

        return redirect()->route('upwork.config.index')->with('success', 'Discovery started. It takes up to a few minutes; refresh this page to see the result.');
    }

    public function editSettings()
    {
        $this->authorize('viewConfig', UpworkOpportunity::class);

        $values = UpworkSetting::allWithDefaults();

        return view('upwork.config.settings', ['values' => $values, 'fields' => self::SETTINGS, 'channels' => self::NOTIFICATION_CHANNELS,
            'tiers' => UpworkSearchProfile::TIERS,
            'clients' => \App\Services\Upwork\Api\UpworkClientFactory::available(),
            'api' => [
                'credential' => \App\Models\UpworkApiCredential::current(),
                'oauth_configured' => (new \App\Services\Upwork\Api\UpworkOAuth())->isConfigured(),
                'scheduler_enabled' => (bool) config('upwork.scheduler.enabled'),
            ],
            'openAlerts' => \App\Services\Upwork\Notifications\AlertService::open(),
            'mcp' => [
                'credential' => \App\Models\UpworkApiCredential::mcp(),
                'registered' => (new \App\Services\Upwork\Api\UpworkMcpOAuth())->client() !== null,
                'accounts' => (array) UpworkSetting::get('mcp.accounts'),
                // A sign-in is waiting for its result to be pasted back (public host only).
                'awaiting_paste' => (new \App\Services\Upwork\Api\UpworkMcpOAuth())->pasteBack() && session()->has('upwork_mcp_oauth'),
            ]]);
    }

    public function updateSettings(Request $request)
    {
        $this->authorize('manageConfig', UpworkOpportunity::class);

        $rules = array_map(fn($f) => $f[1], self::SETTINGS);
        $rules['notification_channels'] = ['nullable', 'array'];
        $rules['notification_channels.*'] = ['string', 'in:' . implode(',', array_keys(self::NOTIFICATION_CHANNELS))];
        $rules['notification_mail_to'] = ['nullable', 'string', 'max:2000'];
        $rules['tier_searches'] = ['nullable', 'array'];
        $rules['discovery_client'] = ['nullable', \Illuminate\Validation\Rule::in(\App\Services\Upwork\Api\UpworkClientFactory::available())];
        $rules['tier_searches.*'] = ['integer', 'min:0', 'max:' . self::TIER_ALLOWANCE_MAX];
        $validated = $request->validate($rules);

        $userId = $request->user()->id;
        $current = UpworkSetting::allWithDefaults();
        $changes = [];

        foreach (self::SETTINGS as $field => [$key]) {
            if ($field !== 'agent_enabled' && !isset($validated[$field])) {
                continue; // optional field not submitted: keep the current value
            }
            $value = $field === 'agent_enabled' ? $request->boolean('agent_enabled') : $validated[$field] + 0;
            if ($current[$key] != $value) {
                $changes[$key] = [$current[$key], $value];
                UpworkSetting::set($key, $value, $userId);
            }
        }

        $recipients = array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,;]+/', (string) $request->input('notification_mail_to', '')) ?: []))));
        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['notification_mail_to' => "“{$email}” is not a valid email address."]);
            }
        }
        if ($current['notifications.mail_to'] != $recipients) {
            $changes['notifications.mail_to'] = [$current['notifications.mail_to'], $recipients];
            UpworkSetting::set('notifications.mail_to', $recipients, $userId);
        }

        $channels = array_values($validated['notification_channels'] ?? []);
        if ($current['notifications.channels'] != $channels) {
            $changes['notifications.channels'] = [$current['notifications.channels'], $channels];
            UpworkSetting::set('notifications.channels', $channels, $userId);
        }

        if (!empty($validated['discovery_client']) && $current['discovery.client'] !== $validated['discovery_client']) {
            $changes['discovery.client'] = [$current['discovery.client'], $validated['discovery_client']];
            UpworkSetting::set('discovery.client', $validated['discovery_client'], $userId);
        }

        if ($request->has('tier_searches')) {
            $tiers = [];
            foreach (array_keys(UpworkSearchProfile::TIERS) as $tier) {
                $tiers[$tier] = (int) ($validated['tier_searches'][$tier] ?? 0);
            }
            if ($current['discovery.tier_searches_per_run'] != $tiers) {
                $changes['discovery.tier_searches_per_run'] = [$current['discovery.tier_searches_per_run'], $tiers];
                UpworkSetting::set('discovery.tier_searches_per_run', $tiers, $userId);
            }
        }

        if ($changes) {
            UpworkConfigLog::record($userId, 'setting', null, 'Agent & discovery settings', 'updated', $changes);
        }

        return redirect()->route('upwork.config.settings.edit')->with('success', $changes ? 'Settings saved.' : 'No changes.');
    }
}
