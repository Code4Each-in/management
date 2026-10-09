<?php

/*
|--------------------------------------------------------------------------
| Upwork Opportunities
|--------------------------------------------------------------------------
|
| The PMS only STORES opportunities and their scoring. Discovery and scoring
| run in the external Claude Upwork agent (read-only Upwork MCP). Nothing in
| the PMS calls Upwork.
|
| Scoring weights/thresholds live here (not in the schema) so the agent's
| scoring algorithm can change without migrations.
|
*/

return [

    // Sanctum token ability required for the agent API endpoints.
    'agent_token_ability' => 'upwork:ingest',

    // Users with this designation get access (in addition to Super Admin
    // and roles granted the module via Pages/Modules).
    'designations' => ['BDE'],

    // Recommendation derived from overall score when the agent sends none.
    'thresholds' => [
        'apply' => 80,
        'review' => 70,
    ],

    // Scoring components shown in the UI: column => [label, max points].
    'score_components' => [
        'technical_fit_score' => ['Technical / project fit', 20],
        'experience_fit_score' => ['Proven Code4Each experience', 20],
        'client_quality_score' => ['Client quality & hiring behaviour', 20],
        'commercial_value_score' => ['Budget / commercial value', 15],
        'long_term_score' => ['Long-term potential', 10],
        'ai_automation_score' => ['AI / automation strategic value', 10],
        'competition_score' => ['Competition / win probability', 5],
    ],

    'per_page' => 25,

    /*
    | AI providers. Secrets come from .env only. Prices are USD per 1M tokens and
    | are used to estimate cost and enforce budgets: a model without a price here
    | cannot be used. Update them when the provider changes its pricing.
    */
    // Official Upwork GraphQL API, read-only (see app/Services/Upwork/Api). The key must be
    // requested with read-only scopes only. Secrets stay in .env; tokens are stored encrypted.
    'api' => [
        'client_id' => env('UPWORK_CLIENT_ID'),
        'client_secret' => env('UPWORK_CLIENT_SECRET'),
        'redirect_uri' => env('UPWORK_REDIRECT_URI'),     // default: route upwork.config.api.callback
        'tenant_id' => env('UPWORK_TENANT_ID'),           // agency organization id (X-Upwork-API-TenantId)
        'authorize_url' => 'https://www.upwork.com/ab/account-security/oauth2/authorize',
        'token_url' => 'https://www.upwork.com/api/v3/oauth2/token',
        'graphql_url' => 'https://api.upwork.com/graphql',
        'timeout_seconds' => 30,
        // Development client only (never used in production).
        'fixture_path' => env('UPWORK_FIXTURE_PATH', base_path('tests/Fixtures/Upwork/test7_jobs.json')),
        'fixture_mode' => env('UPWORK_FIXTURE_MODE'), // rate_limited | error, for failure drills
    ],

    // Upwork MCP, called directly by the PMS (no AI model involved). Standard MCP OAuth 2.1:
    // the PMS registers itself (dynamic client registration) and a person approves access.
    // Only find_jobs (search, get) and list_accounts are ever called (McpUpworkClient::TOOLS).
    'mcp' => [
        'url' => env('UPWORK_MCP_URL', 'https://mcp.upwork.com/mcp'),
        'registration_url' => 'https://www.upwork.com/register',
        'authorize_url' => 'https://www.upwork.com/ab/account-security/oauth2/authorize',
        'token_url' => 'https://www.upwork.com/api/v3/oauth2/token',
        'tool_prefix' => 'upwork__',
        'protocol_version' => '2025-06-18',
        // Token refresh is serialized with a cache lock (Upwork rotates refresh tokens).
        // The cache store must lock across processes: file, database or redis, not array.
        'refresh_lock_wait_seconds' => 45,
        'timeout_seconds' => 60,
    ],

    // Laravel scheduler entries for discovery, the AI queue worker and token refresh.
    // Off until explicitly enabled on the server (UPWORK_SCHEDULER_ENABLED=true).
    'scheduler' => [
        'enabled' => (bool) env('UPWORK_SCHEDULER_ENABLED', false),
    ],

    'ai' => [
        'providers' => [
            'openai' => [
                'api_key' => env('OPENAI_API_KEY'),
                'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            ],
        ],

        // Source: https://developers.openai.com/api/docs/pricing (checked 2026-10-08).
        'pricing' => [
            'openai' => [
                'gpt-5-nano' => ['input' => 0.05, 'cached_input' => 0.005, 'output' => 0.40],
                'gpt-4.1-nano' => ['input' => 0.10, 'cached_input' => 0.025, 'output' => 0.40],
                'gpt-5.6-luna' => ['input' => 0.20, 'cached_input' => 0.02, 'output' => 1.20],
                'gpt-5-mini' => ['input' => 0.25, 'cached_input' => 0.025, 'output' => 2.00],
            ],
        ],
        'pricing_checked_at' => '2026-10-08',

        // Abandoned reservations (crashed calls) keep counting at their worst case.
        'reservation_timeout_minutes' => 15,

        // AI jobs run on the module's own database queue (config/queue.php "upwork"),
        // processed by the scheduler; the app's default QUEUE_CONNECTION is untouched.
        'queue' => [
            'connection' => env('UPWORK_AI_QUEUE_CONNECTION', 'upwork'),
            'name' => 'upwork',
        ],
    ],
];
