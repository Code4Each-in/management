# Upwork Opportunity Intelligence Engine

The module finds Upwork jobs worth Code4Each's attention and explains why. It runs on the PMS server (Laravel scheduler, a database queue and cron). It reads Upwork jobs by calling the **Upwork MCP directly from Laravel**, with no Claude and no AI model in that path. It filters and scores jobs locally first, and uses a low-cost AI model only for jobs that already look promising, within a hard budget. Every decision stays with a person.

**Upwork stays read-only.** The PMS calls only `find_jobs` (search, get) and `list_accounts`. The Upwork MCP also exposes write tools (`send_message`, `save_job`, `manage_proposals`, `respond_to_offer`, `update_profile` and more); the PMS refuses them in code before anything is sent. Nothing submits proposals, sends messages, saves jobs, changes profiles, responds to offers or submits milestones. "Approve", "approve proposal" and "mark as submitted" only record human decisions in the PMS.

## Status (9 Oct 2026)

| Area | State |
|---|---|
| Production | **Not deployed.** All work is on the `upwork-mcp` branch (not pushed) and was tested on the local test database `pms_upwork_test`. |
| Laravel → Upwork MCP (read-only) | Built and **verified live locally**: connect from the PMS, one real discovery cycle (3 searches, 28 jobs, 2 detail lookups, $0), two real token refreshes. |
| Unattended scheduled discovery | Built, tested with fakes, **not enabled**. Blocked on the Upwork API & MCP Terms question (see *Compliance*). |
| Filtering, scoring, review, approve/reject | Built and tested. |
| Proposal drafts | **Template only.** Approval does not create a draft; the "Generate draft" button creates a starter text a person must complete. Tailored drafts are not built. |
| Proposal submission to Upwork | **Not built**, by design. "Mark as submitted" records a manual submission. |
| AI qualification (OpenAI) | Built and tested with the fake provider; **never called the real OpenAI API**. |
| Failure alerts | Built and tested; reach a person only when the mail channel and recipients are set. |

```
cron (every minute) -> php artisan schedule:run
  ├─ upwork:discover   (every 5 min; runs only when switched on and the interval has passed)
  │    tiered plan (HOT → NORMAL → DEEP → EXPLORATORY), per-profile and per-day search budgets
  │    Upwork MCP, READ-ONLY: find_jobs search -> search rows ("discovered", scored locally)
  │                           find_jobs get    -> full details for promising jobs ("enriched", scored locally)
  │    local scoring: hard filters -> score + explanation -> classifications -> estimates
  │    eligible jobs (local score ≥ 55, not hard-rejected, still new) -> AI queue
  ├─ queue:work upwork (every minute, stops when empty)
  │    QualifyOpportunity -> budget guard -> OpenAI (gpt-5-nano) -> schema check -> fact check
  │    -> stored analysis -> re-score with AI signals (only above the confidence gate)
  └─ upwork:token-refresh (daily; serialized with a lock, see Upwork connection)

People: Radar -> opportunity page (facts | local scoring | AI judgement) -> APPROVE / REJECT
        -> proposal draft -> approve draft -> submit manually on Upwork -> "Mark as submitted" -> WON / LOST
```

## Cost model

| Item | Cost |
|---|---|
| Claude Pro | $20/month for development and manual work only. Nothing in production uses Claude. |
| Automated AI | OpenAI `gpt-5-nano` at $0.05 / $0.40 per 1M input / output tokens ([pricing](https://developers.openai.com/api/docs/pricing), checked 8 Oct 2026). A qualification is about 1,900 input tokens plus 400–1,000 output tokens, which comes to **about $0.0003–0.0005 per job**. |
| Default limits | **$5.00/month** and $0.50/day, 300 requests/day, 6,000/month, per-request token caps, at most 3 analyses per job per day, and deep analysis capped at $1.00/month and 20/day *inside* the $5. |
| Upwork MCP | No charge, no AI involved. At the defaults: about 330 searches/day (hard cap 400), plus detail lookups for promising jobs (at most 15 per run). The frequency is subject to the *Compliance* decision. |

**What happens when a limit is reached.** AI calls stop, the reason is shown on the Radar, the AI settings and each job, and local scoring continues. Nothing raises a limit, buys credits or switches to a more expensive model. Before each call, its worst-case cost is reserved under a lock, so parallel workers can't overshoot. A model without a configured price can't be used.

**Set up two safeguards on OpenAI's side as well:**
- a dedicated project with about $5 of **prepaid** credit and **auto-recharge off**
- a project budget limit and an API key scoped to that project

## Code map

| Area | Location |
|---|---|
| Opportunities (ingestion, workflow) | `app/Models/UpworkOpportunity*.php`, `app/Services/Upwork/UpworkOpportunityService.php`, `UpworkProposalService.php` |
| Configuration data | `UpworkTechnology` (+ aliases, relations), `UpworkSearchProfile` (tiers), `UpworkRuleset`, `UpworkRule`, `UpworkClassification`, `UpworkSetting`, `UpworkConfigLog` |
| Local scoring | `app/Services/Upwork/Scoring/`: `ScoringEngine`, `TechnologyMatcher`, `RuleConditions`, `RuleEvaluator`, `ScoringContext`, `ClientQualityScorer`, `StrategicScorer`, `OutlookEstimator`, `ConditionDescriber` |
| Discovery | `app/Services/Upwork/Discovery/`: `DiscoveryService` (lease, tiered plan, sightings), `QueryPlanner`, `ServerDiscoveryRunner`, `RowValidator` |
| Upwork access (read-only) | `app/Services/Upwork/Api/`: `UpworkClientInterface`, **`McpUpworkClient`** + **`UpworkMcpOAuth`** (the source in use), `UpworkTokenRefreshBusy`, `GraphQlUpworkClient` + `ReadOnlyGraphQl` + `UpworkOAuth` (official API, dormant; its detail mapper is reused by the MCP client), `FixtureUpworkClient` (development only), `UpworkClientFactory` |
| Alerts and notifications | `app/Services/Upwork/Notifications/`: `AlertService` (operational alerts), `NotificationService` (new APPLY/REVIEW jobs), `LogNotifier`, `MailNotifier` |
| AI | `app/Services/Upwork/Ai/`: `AiGateway`, `AiBudgetGuard`, `AiPricing`, `JsonSchemaValidator`, `Providers/OpenAiProvider`, `Providers/FakeProvider` (development only), `Qualification/QualificationService`, `QualificationPrompt`, `DeepAnalysisPrompt`; job `app/Jobs/Upwork/QualifyOpportunity.php` |
| Clients | `UpworkClient`, `UpworkClientSnapshot`, `app/Services/Upwork/Clients/ClientIntelligenceService.php` (history, fingerprint matching) |
| Radar and metrics | `RadarService`, `MetricsService`, `Upwork\RadarController` |
| Scheduler | `app/Console/UpworkSchedule.php` (registered from `app/Console/Kernel.php`) |
| Commands | `upwork:discover`, `upwork:ai-analyze`, `upwork:ai-status`, `upwork:metrics`, `upwork:token-refresh`, `upwork:preflight`, `upwork:explain`, `upwork:agent-token` |
| Default configuration | `database/seeders/UpworkIntelligenceSeeder.php` (idempotent; never overwrites admin edits) |
| Mac mini worker | `agent/`: development and manual use only (see `agent/README.md`) |
| Tests | `tests/Feature/Upwork/`, `tests/Unit/Upwork*`, fixtures in `tests/Fixtures/Upwork/` |

## Local filtering and scoring (always first, no AI)

1. **Technologies.** Catalog aliases are matched as whole words, with the relationship graph giving partial credit.
2. **Hard filters**, applied before any points:
   - already hired, or an offer is out
   - individuals only (in agency mode)
   - budget floors
   - EXCLUDED-only capability
   - missing description
   - **stale job**: older than `max_job_age_hours`, default 168

   Hard-rejected jobs are stored with the rule, reason and ruleset version.
3. **Components** (technical fit, proven experience, client quality, commercial value, long-term, AI/automation, competition). Each gets a baseline plus rule points; bonuses are capped before penalties apply.
   - The hire-rate penalties ignore new clients: `zero_hire_rate` needs at least 3 jobs posted, `low_hire_rate` at least 5.
4. **Recommendation** from the thresholds: APPLY at 80 or above, REVIEW at 70–79, SKIP below 70.
5. **Estimates**, labelled as estimates: Strategic Value, win probability (`heuristic-v1`), contract value and expected value.
6. **Client fingerprint.** A search row has no Upwork client id. When its country, jobs posted, hires and total spent match exactly one client seen in the last 30 days, that client's history and cached facts feed the stage-1 score (`score_explanation.client.match`). Only full job details link a job to a client.

Rules are structured JSON (`{all|any|not}` trees of `{field, op, value}`) and never use `eval`. `php artisan upwork:explain <id>` explains any job.

## Search tiers

| Tier | Seeded profiles | Every | Searches per run (tier allowance) | Profile budget per day |
|---|---|---|---|---|
| HOT | Laravel/PHP, AI Automation | 20 min | 3 | 120 |
| NORMAL | SaaS, React, API Integration, Booking, AI Agents | 2 h | 6 | 36 |
| DEEP | WordPress, WooCommerce, CRM | 8 h | 12 | 18 |
| EXPLORATORY | ERP | 24 h | 5 | 6 |

- **Order.** HOT is planned first. Within a tier, the profile that has waited longest goes first.
- **Caps.** These apply to every run as well: 15 searches per run, 4 per profile per run, and 400 per day.
- **Settings.** Each profile sets its tier, its searches per day, results per search (`max_results`; pages = results ÷ 10, at most 3) and whether to also search related technologies. Related searches are off by default because they were noisy in the first real run.
- **Where to edit.** Tier allowances are under **Upwork → Configuration → Agent & discovery**, profiles under **Search profiles**.

## AI qualification

**Which jobs reach AI automatically:** enriched (full details), not hard-rejected, status still *new*, and local score at least `ai.min_local_score` (55).

**What is never re-sent:** the same input (a hash of request type, prompt version, model and job input) is answered once and the stored answer is reused. A person can **Regenerate**.

**What the AI answers.** It answers in a strict JSON schema (OpenAI structured outputs) with:
- `fit_score`, `recommendation`, `confidence`, `summary`
- `technologies` (FACT or INFERENCE)
- `scope`, with hour estimates labelled as assumptions
- `signals`, `risks`
- `claims`, each labelled **FACT / INFERENCE / ASSUMPTION / UNKNOWN**
- `unknowns`, `questions_for_client`

**Fact check.** A FACT must quote words that really appear in the job post or client data. Otherwise it is stored as INFERENCE and flagged on the page.

**Prompt structure.** The static part (instructions and the capability catalog) is identical for every job, so OpenAI caches it. The job text is fenced off as untrusted data, and instructions inside it are ignored.

**Effect on scoring.** AI signals reach the rules (`ai.*` fields) only when confidence is at least `ai.min_confidence` (0.6). Four small default rules use them, worth −6 to +4 points. Win probability is blended with an AI-implied probability, weighted by confidence and capped at 30% (`heuristic-v1+ai`). AI hour estimates add an AI contract value, or an effective hourly rate for fixed-price jobs. They replace the posting-based value only when the posting gives none.

**Deep analyze** is manual only and uses the same model. It adds a bid angle, an approach, experience to highlight and red flags. It has larger token caps and its own allowance inside the monthly budget.

**When AI fails:**
- A timeout, rate limit or server error gets one bounded retry, then the job is marked `failed`.
- Quota exhausted, auth errors and refusals are not retried.
- Malformed answers are rejected and never stored as results.

In every case local scoring stands. Every call is logged in `upwork_ai_usage` (tokens, estimated cost, status, error), and every answer in `upwork_ai_analyses`.

**Queue.** The `upwork` connection is a database queue on its own table, `upwork_queue_jobs`; the PMS `jobs` table holds recruitment postings. The app's default `QUEUE_CONNECTION` is not changed. Jobs are idempotent: identical input reuses the stored answer, and a per-opportunity lock prevents double work.

## Opportunity page

- **Job: VERIFIED · FROM UPWORK.** Facts as Upwork reported them.
- **Local scoring: RULES · DETERMINISTIC.** Hard filters, classifications, technologies.
- **AI analysis: AI JUDGEMENT · NOT VERIFIED.**
  - fit, suggestion, and confidence (including whether it reaches scoring)
  - labelled claims, risks, unknowns, questions for the client
  - scope and hour estimate, signals
  - model, prompt version and time
  - buttons: **Analyze with AI**, **Regenerate**, **Deep analyze**
  - **AI usage for this job**: requests, tokens and cost, with a link to the overall usage page
- **Commercial outlook: ESTIMATE.** Win probability with its contributions and the AI blend, contract value and expected value, and the AI hour estimate.

The **Radar** adds **AI & efficiency** for today, 7 or 30 days:
- searches and detail lookups
- new jobs, duplicates and locally filtered jobs
- AI analysed, skipped and blocked
- AI requests, tokens and cost
- month-to-date spend against the budget
- 70+ and 80+ jobs, approvals and rejections
- cost per analysed, 70+ and approved job
- searches per new job, AI share, and how often AI agreed with people

The same figures are available from `php artisan upwork:metrics --range=7d`.

## Configuration pages (Upwork → Configuration)

| Page | Controls |
|---|---|
| Search profiles | Keywords, title phrases, required/optional/excluded terms, Upwork filters, tier, searches per day, results per search, related searches, technologies |
| Technologies | Capability catalog (PROVEN / ADJACENT / EMERGING / EXCLUDED), aliases, relationships |
| Scoring | Versioned rulesets: edit a draft, then publish |
| Classifications | Labels that rules assign; "surface" keeps a job visible below REVIEW |
| Agent & discovery | **Upwork MCP, direct** (connect, account, reconnect, disconnect, token status), the dormant Upwork API card, discovery on/off, interval, **source**, caps, tier allowances, time budget, notification channels and recipients (also used for alerts) |
| AI | On/off, auto-analyze, provider and model (priced models only), confidence gate, every limit and budget, deep-analysis allowance, usage |

**Access:**
- **Reading** needs Upwork access (Super Admin, BDE, or the `upwork.opportunities.index` module).
- **Changing configuration or connecting Upwork** needs Super Admin or the `upwork.config.manage` module.
- **Analyze / Deep analyze** needs Upwork access.

Every configuration change is logged in `upwork_config_logs`.

## Upwork connection (MCP, direct)

**How it connects.** Standard MCP OAuth 2.1. On **Connect Upwork MCP** the PMS registers itself with Upwork as its own public client (dynamic client registration, no API key, no client secret), a Super Admin signs in to Upwork and approves (PKCE), and the PMS lists the accounts and defaults to the agency (`FL_AGENCY`). Tokens are stored encrypted with `APP_KEY` under the `mcp` credential label. The registration is tied to the callback address, so **each server connects once**: the local connection cannot be copied to production.

**Callback: loopback only.** Upwork accepts only `localhost` / `127.0.0.1` redirect URIs. Registering `https://<pms-domain>/upwork/config/mcp/callback` is refused with `400 invalid_redirect_uri`, `application_type: "web"` does not change that, and a self-hosted client ID metadata document is refused with "Client not found or disabled" (all verified 12 Oct 2026). Every client connected to Upwork (Claude Code included) uses a loopback callback. So on a public host the PMS registers `http://127.0.0.1:45127/upwork/mcp/callback` (`UpworkMcpOAuth::LOOPBACK_REDIRECT_URI`): after approving, the browser shows "This site can't be reached" at that address, and the Super Admin pastes the full address into **Finish connecting** on the MCP card. The PMS checks `state` and exchanges the code with the PKCE verifier, which never leaves the session; the pasted address is single-use. On localhost the PMS callback receives the result directly. Token refresh does not use the callback, so pasting is needed only when (re)connecting.

**Transport.** MCP Streamable HTTP (JSON or SSE answers), one session per discovery run, `Mcp-Session-Id` reused, one re-initialization if the session expires. Descriptions arrive wrapped in `<untrusted_participant_content>`; the wrapper is stripped and the text stays untrusted data. Client reviews are never stored.

**Read-only guarantee.** `McpUpworkClient::TOOLS` allows `find_jobs` (`search`, `get`) and `list_accounts` only; any other tool or action throws before a request is sent, and no public method takes a tool name. Tests also scan `app/` for any MCP write-tool name in a string. Note that **Upwork grants the full set of scopes to the connection**, so read-only is enforced by the PMS, not by Upwork.

**Token lifecycle (verified live on the test connection, 9 Oct 2026):**

| | Observed |
|---|---|
| Token response | `access_token`, `refresh_token`, `token_type` (`Bearer`), `expires_in` |
| Access token | 24 hours (`expires_in` = 86400), opaque |
| Refresh token | **Rotated on every refresh**; a rotated token refreshes again; opaque |
| Refresh-token lifetime | **Not stated** by Upwork; the PMS assumes 14 days, and the daily refresh keeps it rolling |
| Reusing an old refresh token | Not tested on purpose (it may revoke the connection) |

**Refresh is serialized.** Because the refresh token rotates, two processes refreshing at once could break the connection. `UpworkMcpOAuth::refresh` takes a cache lock (held up to 90 s, waiters up to 45 s), re-reads the stored credential under the lock, and skips when another process already rotated it. The answer is validated before anything is saved, and the new tokens and expiry metadata are saved together in one transaction. A busy lock is transient: discovery reports `refresh_busy` (not an auth error) and the daily command skips.

**Requirement:** the cache store must lock across processes. The default `file` store does (exclusive file lock); `array` and `null` do not. Preflight fails in production otherwise.

**Known recovery limits.**
- If a process dies after Upwork returned rotated tokens but before they were saved (a single database write right after the response), the stored refresh token is the superseded one. The next refresh fails, the token-refresh alert fires, and a Super Admin must **Reconnect**.
- A process stalled for more than 90 s loses the lock; another process may refresh with the same old token, with the same outcome.
- If the refresh token expires (lifetime unknown) because refreshes stopped (for example cron stopped for days), reconnect.

## Alerts

`AlertService` sends operational alerts through the same channels and recipients as opportunity notifications (**Agent & discovery → Notification channels / Email recipients**). Defaults: channel `log` only, so **alerts reach a person only when the mail channel is enabled and recipients are set**.

| Alert | When |
|---|---|
| Scheduled discovery cannot run | Discovery is switched on but the Upwork source is not connected (no run record is created) |
| Upwork rejected the discovery connection | A token is refused during a run (the run stops instead of repeating failing calls) |
| Every Upwork search in a run failed | All searches failed for a reason other than rate limiting |
| Discovery run failed | An unexpected error ended a run |
| Token refresh failed | `upwork:token-refresh` could not refresh |

Each problem alerts once per `alerts.cooldown_minutes` (default 360) with an occurrence count, and sends one "resolved" notice after recovery. Rate limits do not alert (normal backoff). Alert text is scrubbed of anything credential-like.

**Open alerts are visible in the PMS** until the problem clears: on **Agent & discovery** and in the Radar's **Discovery health** card (stored in the `alerts.state` setting). `upwork:preflight` lists them too, reports the alert channels, the number of valid recipients and the mailer, and **in production fails** when email alerts cannot reach anyone (mail channel off, no valid recipients, or a mailer such as `log` that does not send).

## Compliance

Use of the Upwork MCP is governed by Upwork's **API & MCP Terms of Use**, the same terms as the official API, so switching to an API key does not change this. Third-party summaries quote §4.1 as allowing searches for "a specific, documented, user-directed task" and not "activity designed to enumerate or continuously monitor Upwork's available content corpus". Upwork's own pages were not machine-readable for this review, so the quotes are **unverified**. Until the terms are read in full or Upwork confirms in writing, **do not enable unattended polling**; manual runs (`upwork:discover --manual`, or **Run discovery now** on the Upwork Configuration page, Super Admin only) remain available.

## Deployment (Hostinger shared hosting)

Nothing here has been deployed. Do these steps in order. Each step is reversible.

**Gates before the release**
1. **Compliance:** the Upwork API & MCP Terms allow the intended discovery frequency, or Upwork confirmed in writing. Without this, deploy only for manual runs.
2. **Code:** the `upwork-mcp` PR reviewed and merged. It must not contain the unrelated local `composer.json` or `scheduled_emails` changes.
3. **Alerts:** a mailbox for alerts is agreed, and production mail works.
4. **Optional, AI:** a dedicated OpenAI project with about $5 prepaid credit, **auto-recharge off** and a budget limit; check the `gpt-5-nano` price in `config/upwork.php`. The pilot can run with AI off.

**Release (everything off)**

5. Back up the production database.
6. Deploy the code. The branch adds no packages; if the deployment runs `composer install`, confirm `webklex/laravel-imap` (used by existing ticket code) is already present on the server.
7. Run `php artisan migrate --pretend` and confirm that **only the 18 `*_upwork_*` migrations** are pending (each is additive). Then run `php artisan migrate`. Never run `migrate:fresh` or `migrate:refresh`.
8. Run `php artisan db:seed --class=UpworkIntelligenceSeeder`. It is idempotent.
9. `.env` (never commit values): `UPWORK_SCHEDULER_ENABLED=false`; `CACHE_DRIVER` must be `file` (or database/redis), not `array`; `APP_URL` must be the `https://` address. Leave `OPENAI_API_KEY` empty unless AI is part of the pilot. Then `php artisan config:cache` if the server caches configuration.
10. Register the `upwork.config.manage` module (Pages/Modules) and grant it to whoever may change the configuration.
11. Run `php artisan upwork:preflight`. Everything must pass. It makes no external calls and prints no secrets. In production it checks, among others: all Upwork migrations applied, the module's own queue table, a cross-process cache lock, the read-only allowlists, a priced AI model and sane budgets, a real discovery source (MCP connected with an account when it is the source), **email alerts reaching a person**, and the **HTTPS OAuth redirect**. It also reports the scheduler and AI switches, both off by default.

**Connect and check (still nothing scheduled)**

12. As Super Admin: **Agent & discovery → Connect Upwork MCP**. Before approving on Upwork, check that the login link's `redirect_uri` is `http://127.0.0.1:45127/upwork/mcp/callback`. Approve; the browser then shows "This site can't be reached". Copy the full address from the address bar, go back to **Agent & discovery** and paste it into **Finish connecting**. The card must show "Connected, searching as Code4Each".
13. Set **Upwork source = Upwork MCP, direct**, enable the **mail** channel and set recipients.
14. Run one manual cycle: `php artisan upwork:discover --manual`. Check the Radar (Discovery health) and a few jobs.
15. Run `php artisan upwork:token-refresh` once and confirm it reports "refreshed".

**Pilot (only after the compliance gate)**

16. Confirm the existing cron line runs `php artisan schedule:run` every minute with the site's PHP version. Set `UPWORK_SCHEDULER_ENABLED=true`, run `config:cache`, and switch on **Agent enabled**, at the frequency the terms allow.
17. Watch for the first days: Radar, alerts, `storage/logs`, the token refresh after 24 hours, duplicates.
18. AI only later: provider `openai`, model `gpt-5-nano`, $5/month; test with `php artisan upwork:ai-analyze <id>` first.

**Stopping and rollback**
- **Immediate stop:** switch off "Agent enabled" and/or "AI enabled" in the PMS, or set `UPWORK_SCHEDULER_ENABLED=false` (then `config:cache`).
- **Disconnect Upwork:** Agent & discovery → Disconnect deletes the stored tokens. Also revoke the PMS in your Upwork account's connected apps if access should end on Upwork's side.
- **Schema rollback:** `php artisan migrate:rollback --path=database/migrations/<file>` for each Upwork migration, newest first. Each one has a `down()`. Rolling back deletes the module's data (opportunities, analyses, usage); export first if needed.
- **Code rollback:** redeploy the previous release; the module has no effect on other PMS features when its scheduler switch is off.

**Shared-hosting notes**
- There is no daemon. The worker starts every minute and exits when the queue is empty (`--max-time=50`).
- Discovery stops cleanly after `discovery.max_seconds_per_run` (240 s) and pauses 500 ms between Upwork calls.
- If `pcntl` is missing, `--timeout` is ignored. The job lock and the 300 s `retry_after` still prevent double work.

**Official Upwork API (dormant, optional).** `GraphQlUpworkClient` maps fields from Upwork's public GraphQL schema and has never been tested against the live API. It would need an API key (read-only scopes, callback `https://<pms-domain>/upwork/config/api/callback`), one manual cycle and a mapping check. It is subject to the same API & MCP Terms.

## Development and testing

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: vendor/bin/phpunit                  # whole suite
DB_CONNECTION=sqlite DB_DATABASE=:memory: vendor/bin/phpunit tests/Feature/Upwork
```

The Upwork tests build a minimal schema, run only the Upwork migrations, use a fixed clock, and fake all HTTP (MCP fake: `tests/Feature/Upwork/FakesUpworkMcp.php`, real sanitized MCP answers in `tests/Fixtures/Upwork/mcp_*.json`). Read-only guarantees are tested directly:
- the client interfaces have only read operations; MCP write tools and actions are refused before sending
- every request sent is an allowlisted query or tool call
- the module's code contains no GraphQL mutation and names no MCP write tool

Pilot readiness (`UpworkPilotReadinessTest`, `UpworkMcpTokenRefreshTest`) covers the `upwork:discover` and `upwork:token-refresh` commands with the MCP source, safe skips, alerts, preflight, token rotation, concurrent refresh, lock timeout and expiry, and malformed token responses.

**Local dry run** without Upwork or OpenAI:
- set **Upwork source = Fixture data** under Agent & discovery
- set **provider = fake** under AI (both appear only outside production)
- then run `php artisan upwork:discover --manual` and `php artisan queue:work upwork --queue=upwork --stop-when-empty`

`UPWORK_FIXTURE_PATH` points the fixture client at another file. `UPWORK_FIXTURE_MODE=rate_limited|error` and `FAKE_AI_MODE=rate_limited|server_error|malformed|quota_exhausted` simulate failures.

The test environment's `array` cache only locks within one process; tests simulate a second process by holding the same named lock.

Avoid running `php artisan schedule:run` on a development machine. It also runs the PMS's own scheduled commands (reminders, client emails, ticket mailbox).

**Dry run of 8 Oct 2026** (test database, fixture client and fake AI, run with the same commands as the scheduler):

| What was checked | Result |
|---|---|
| Discovery | 35 searches across the tiers; 11 new jobs, 4 enriched |
| AI routing | 2 analysed automatically; 2 skipped below the threshold or hard-rejected |
| Upwork rate limit | The run stopped after 1 call and the profile backed off |
| AI provider error | Recorded as `failed`; local score unchanged |
| Budget exhaustion | The call was blocked; local scoring continued |

Two issues found during the dry run were fixed:
- an empty run record was created when nothing was due
- a refused retry hid the real AI error
