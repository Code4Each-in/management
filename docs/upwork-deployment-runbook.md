# Upwork read-only MVP: production deployment runbook

Target: `https://pms.code4each.com` (Laravel 8, Hostinger, MariaDB 11.8). The server deploys from the Git repository over SSH. This runbook installs the module **with everything off**: no scheduled polling, no AI, no Upwork writes.

Every command is marked:

- **[READ]**: inspection only, changes nothing.
- **[CHANGE]**: changes production. Run only with explicit approval for that step.

Placeholders: `<PMS_PATH>` is the application directory on the server, `<PHP>` is the PHP CLI the site uses, `<DB_*>` are the database settings from `.env`. Discover them in section 1. Never print `.env`; use the `grep` forms below, which show variable names only.

## 0. Release contents and gates

- Release branch `release/upwork-readonly-mvp`, based on `ed4a4e4` (the current `main`). It holds two commits: the squashed module and release hardening. No new Composer packages. 18 additive migrations, all on new `upwork_*` tables.
- Gates before step 3:
  1. The release PR is reviewed and merged into the branch the server deploys from.
  2. A backup window is agreed.
  3. An alert mailbox is chosen. It is needed later for preflight, not for installing.
- Gates before any scheduled polling, which is **not part of this deployment**: the Upwork API & MCP Terms questions on scheduled searches and job-data retention are resolved in writing.

## 1. Inspect the server [READ]

```bash
ssh <user>@<host>
cd <PMS_PATH>

# Code and deployment
git status --short                     # must be clean (no local edits on the server)
git branch --show-current
git remote -v
git log --oneline -1                   # record as PREVIOUS_RELEASE for rollback

# PHP the site and cron use
which php; php -v                      # needs PHP >= 7.4
php -m | grep -iE '^(curl|json|mbstring|openssl|pdo_mysql|tokenizer|xml|ctype|fileinfo)$'
crontab -l                             # look for the schedule:run line and the PHP binary it uses

# Laravel state
<PHP> artisan --version                # Laravel 8.x
<PHP> artisan migrate:status | grep -c '| No'      # pending migrations BEFORE deploying (expect 0)
ls bootstrap/cache/                    # config.php present = config is cached; routes-*.php should be absent

# Environment: names and non-secret values only
grep -E '^(APP_ENV|APP_DEBUG|APP_URL|CACHE_DRIVER|QUEUE_CONNECTION|MAIL_MAILER|SESSION_DRIVER)=' .env
grep -cE '^(UPWORK_|OPENAI_)' .env     # expect 0 before this release

# Writable paths for the file cache and logs
ls -ld storage/framework/cache storage/logs
```

Stop and report if any of these hold:
- `git status` is not clean
- PHP is older than 7.4
- `APP_ENV` is not exactly `production`
- `APP_URL` is not `https://pms.code4each.com`
- `CACHE_DRIVER` is `array` or `null`
- non-Upwork migrations are pending

## 2. Back up [CHANGE: writes backup files only]

```bash
mkdir -p ~/backups && chmod 700 ~/backups
cd <PMS_PATH>
git rev-parse HEAD > ~/backups/pms-previous-release-$(date +%F).txt
cp .env ~/backups/pms-env-$(date +%F).bak && chmod 600 ~/backups/pms-env-$(date +%F).bak
mysqldump --single-transaction --routines --triggers -h <DB_HOST> -u <DB_USERNAME> -p <DB_DATABASE> \
  | gzip > ~/backups/pms-db-pre-upwork-$(date +%F-%H%M).sql.gz   # prompts for the password; do not pass it on the command line
ls -lh ~/backups/                      # [READ] the dump must be non-trivial in size
```

A Hostinger hPanel database backup taken at the same time is a good second copy.

## 3. Deploy the code [CHANGE]

Use the server's normal deployment method. For a `git pull` setup:

```bash
cd <PMS_PATH>
git fetch origin
git checkout <deploy-branch>           # the branch the server already uses, normally main
git pull --ff-only origin <deploy-branch>
git log --oneline -3                   # [READ] the release commits are present
```

- **Effect:** new module code, routes and views. The **Upwork** menu appears only for users with Upwork access. Existing PMS features are unchanged.
- **Composer:** the release adds no packages. Run `composer install --no-dev` only if your deployments always do. The repository has no `composer.lock`, so a fresh install may resolve newer patch versions. Check first that `webklex/laravel-imap` (used by existing ticket code) is present: `ls vendor/webklex`.

## 4. Configure the environment [CHANGE]

Add to `.env`, keeping the existing values. Never commit `.env`.

```dotenv
UPWORK_SCHEDULER_ENABLED=false     # no Upwork scheduler entries are registered
# Leave unset or empty. The official Upwork API path is dormant:
# UPWORK_CLIENT_ID=
# UPWORK_CLIENT_SECRET=
# UPWORK_TENANT_ID=
# Leave unset or empty. AI stays off:
# OPENAI_API_KEY=
```

Must already be true (verified in step 1): `APP_ENV=production`, `APP_URL=https://pms.code4each.com`, `CACHE_DRIVER=file` (or database/redis), and a working `MAIL_MAILER` (needed later for alerts). `QUEUE_CONNECTION` is **not** changed; the module uses its own `upwork` queue connection.

```bash
<PHP> artisan config:clear
<PHP> artisan config:cache             # only if config was cached before (bootstrap/cache/config.php existed)
<PHP> artisan view:clear
# Do NOT run route:cache: the PMS has closure routes.
```

## 5. Migrate the module tables [CHANGE]

Run the 18 Upwork migrations **by path, in this order**. Other pending migrations, if any, are never run by accident.

```bash
<PHP> artisan migrate --pretend --path=database/migrations/2026_10_07_100000_create_upwork_opportunities_table.php   # [READ] preview one
for m in \
 2026_10_07_100000_create_upwork_opportunities_table \
 2026_10_07_100100_create_upwork_opportunity_logs_table \
 2026_10_08_100000_create_upwork_technology_catalog_tables \
 2026_10_08_100100_create_upwork_search_profiles_table \
 2026_10_08_100200_create_upwork_scoring_tables \
 2026_10_08_100300_add_scoring_columns_to_upwork_opportunities_table \
 2026_10_08_100400_create_upwork_discovery_tables \
 2026_10_08_100500_create_upwork_clients_tables \
 2026_10_08_100600_create_upwork_classifications_table \
 2026_10_08_100700_add_outlook_columns_to_upwork_opportunities_table \
 2026_10_08_100800_create_upwork_config_logs_table \
 2026_10_08_100900_add_notification_columns_to_upwork_opportunities_table \
 2026_10_09_100000_create_upwork_ai_usage_table \
 2026_10_09_100100_add_tiers_to_upwork_search_profiles_table \
 2026_10_09_100200_create_upwork_ai_analyses_table \
 2026_10_09_100300_create_upwork_queue_jobs_table \
 2026_10_09_100400_create_upwork_api_credentials_table \
 2026_10_09_100500_add_fingerprint_to_upwork_clients_table; do
  <PHP> artisan migrate --force --path=database/migrations/$m.php || { echo "STOPPED at $m"; break; }
done
<PHP> artisan migrate:status | grep _upwork_      # [READ] all 18 show "Yes"
```

If the loop stops, do not continue. Note the migration name and go to the rollback section.

- **Effect:** creates 21 new `upwork_*` tables. `ALTER`s touch only those new tables. Foreign keys point to the module's own tables and to `users.id`, which is `BIGINT UNSIGNED` on production, as `foreignId()` expects. There is no data change outside the module (the fingerprint backfill only reads the new, empty `upwork_clients`). Nothing uses `migrate:fresh` or `migrate:refresh`, and never use them.

## 6. Seed the defaults [CHANGE]

```bash
<PHP> artisan db:seed --force --class=UpworkIntelligenceSeeder
```

- **Effect:** inserts the default technology catalog, search profiles, ruleset v1 and classifications, and settings with `agent.enabled=false` and `ai.enabled=false`. It is idempotent and never overwrites admin edits.

## 7. Grant access [CHANGE: PMS data]

In the PMS, as Super Admin: **Pages/Modules**, register the module route `upwork.config.manage` and grant it only to whoever may change the Upwork configuration. Reading needs Super Admin, the BDE designation or `upwork.opportunities.index`.

## 8. Validate [READ]

```bash
<PHP> artisan upwork:preflight
```

Expected **at this stage**: everything passes **except** two items that are configured later:
- "Discovery source … is connected", because no Upwork connection exists yet
- "Email alerts reach a person", because no recipients are set yet

Any other ✗ is a deployment problem: stop and investigate. Preflight makes no external calls and prints no secrets.

## 9. Post-deployment smoke test (in the browser) [READ]

- [ ] Log in as Super Admin; the **Upwork** menu shows Radar, Opportunities, Client Intelligence and Configuration.
- [ ] Radar loads (empty), including the Discovery health and AI & efficiency cards.
- [ ] Opportunities list loads (empty); Client Intelligence loads.
- [ ] Configuration: Search profiles (11 seeded), Technologies, Scoring (ruleset v1 published), Classifications, AI (shows **AI disabled**), and Agent & discovery (**Agent enabled = off**, both Upwork cards "Not connected", no open alerts).
- [ ] A user without Upwork access does not see the menu, and `/upwork` returns 403.
- [ ] Existing PMS pages (dashboard, tickets, reminders) behave as before.
- [ ] `tail -n 50 storage/logs/laravel.log` shows no new errors. **[READ]**
- [ ] `crontab -l` is unchanged, and the next `schedule:run` adds no Upwork work. **[READ]** Scheduler entries are off.

## 10. Later steps (each needs its own approval) [CHANGE]

Not part of this deployment:

1. **Alert mailbox:** under Agent & discovery, enable the **mail** channel and set recipients. Then send one approved test notification to confirm delivery.
2. **Connect the Upwork MCP:** Agent & discovery → **Connect Upwork MCP**. Before approving on Upwork, check that the login link's `redirect_uri` is `http://127.0.0.1:45127/upwork/mcp/callback`: Upwork accepts loopback callbacks only and refuses `https://pms.code4each.com/...` (`invalid_redirect_uri`). Approve; the browser then shows "This site can't be reached" at that address, which is expected. Copy the full address from the address bar, go back to Agent & discovery and paste it into **Finish connecting**. The card must show "Connected, searching as Code4Each".
   - **Effect:** registers the PMS as an OAuth client at Upwork and stores encrypted tokens.
   - **If registration fails,** the message now includes Upwork's error code and description, and `laravel.log` has a `[upwork] MCP client registration failed` entry.
3. Set **Upwork source = Upwork MCP, direct**. Run `upwork:preflight` again; it should pass.
4. **One manual, read-only discovery run:** `<PHP> artisan upwork:discover --manual`, or as Super Admin **Run discovery now** on the Upwork Configuration page (runs in the background; refresh after a few minutes to see the result on the Agent card).
   - **Effect:** read-only Upwork calls; stores jobs in `upwork_*` tables.
5. **Token refresh check:** `<PHP> artisan upwork:token-refresh` (rotates the Upwork MCP tokens).
6. **Only after the terms decision:** set `UPWORK_SCHEDULER_ENABLED=true`, then `config:cache`, and switch on **Agent enabled** at the frequency the terms allow.
7. **AI only later, and only by decision:** an OpenAI project with prepaid credit and auto-recharge off.

## 11. Hourly discovery, review SLA and AI proposal drafts

Code-only release plus one additive migration: `2026_10_13_100000_add_review_sla_columns_to_upwork_opportunities_table` (nullable columns, no backfill, so existing jobs never enter the SLA). Everything ships switched off:

| Switch | Default | Effect when on |
|---|---|---|
| `UPWORK_SCHEDULER_ENABLED` (.env) | `false` | Registers `upwork:discover` (checked every 5 min), the AI queue worker, `upwork:review-reminders` (every minute) and token refresh. |
| Agent enabled (PMS) | off | Scheduled discovery runs every **Discovery interval** (new default 60 min; a value already saved in the PMS is kept). After a failed run it retries after 10, 20, 40… min, never later than the interval. |
| Review reminders (PMS, Agent & discovery) | off | Reminders at 10, 20 and 25 min after a new APPLY / REVIEW job is first seen, overdue at 30, then every 10 min until someone approves, rejects or snoozes it. Deadlines and overdue marks are recorded even while off. |
| AI enabled + proposal drafts (PMS, AI) | off | A person's approval queues one AI draft (budget-capped by **Proposal drafts monthly cap**), stored as an editable, unapproved draft. Never submitted. |

Optional `.env`: `UPWORK_REVIEW_TIMEZONE` (snooze presets and email times; defaults to `APP_TIMEZONE`). The AI queue worker is the existing scheduler entry; no daemon is needed.

Before switching reminders on: the mail channel and recipients are set (step 10.1), and the server scheduler is on. Snooze links in emails open a confirmation page; only the button on that page changes anything.

## 12. Dual-track targeting (agency / individual)

Code plus one additive migration: `2026_10_15_100000_add_target_profile_columns_to_upwork_opportunities_table`. All 8 new columns are nullable, plus one index on `target_profile`. Nothing is backfilled by the migration, and no existing column, score, recommendation, review deadline or snooze changes. Nothing in this release calls Upwork, and no setting changes on deploy.

```bash
<PHP> artisan migrate --pretend --force --path=database/migrations/2026_10_15_100000_add_target_profile_columns_to_upwork_opportunities_table.php   # [READ]
<PHP> artisan migrate --force --path=database/migrations/2026_10_15_100000_add_target_profile_columns_to_upwork_opportunities_table.php      # [CHANGE]
<PHP> artisan view:clear
<PHP> artisan migrate:status | grep 2026_10_15_100000           # [READ] "Yes"
```

- **The agency-only filter is untouched.** It is the published ruleset's `agency_mode` setting (seeded on, intentionally off on production). This release neither reads nor writes it. Check before and after: Configuration → Scoring → current version → *Agency mode*.
- **New and re-seen jobs** get a best-fit label automatically. **Existing jobs** need the optional backfill, which needs its own approval:
  ```bash
  <PHP> artisan upwork:classify-targets                 # [READ] dry run: counts only, saves nothing
  <PHP> artisan upwork:classify-targets --apply         # [CHANGE] writes only target_profile, the two fit scores and score_explanation.target
  ```
  The backfill fires no notifications, reminders or SLA changes. It adds one `target_classified` activity entry per job.
- **Optional, each by decision:** add individual profiles under Configuration → Targeting. Tune weights and preferences through a Scoring draft (*Advanced settings*, key `target_profiles`), then publish it.
- **Smoke test [READ]:** a job page shows **Best-fit profile** and the **Bid as / Proposal angle** approval form. The list has the profile filters. The Radar shows **Target profiles**. Configuration shows the **Targeting** tab.
- **Rollback:** code rollback as below. The columns can stay, because old code ignores them. Schema only: `<PHP> artisan migrate:rollback --force --path=database/migrations/2026_10_15_100000_add_target_profile_columns_to_upwork_opportunities_table.php`. This drops the 8 columns and the recorded profile choices; activity log entries remain.

## 13. Archiving and cleaning the review queue

Code plus one additive migration: `2026_10_16_100000_add_archive_columns_to_upwork_opportunities_table`. It adds three nullable columns: `archived_at`, `archived_by` and `archived_from_status`. `status` is already a `varchar(30)`, so `archived` needs no type change, and nothing is backfilled. It requires §12's migration first (same release branch).

```bash
<PHP> artisan migrate --pretend --force --path=database/migrations/2026_10_16_100000_add_archive_columns_to_upwork_opportunities_table.php   # [READ]
<PHP> artisan migrate --force --path=database/migrations/2026_10_16_100000_add_archive_columns_to_upwork_opportunities_table.php      # [CHANGE]
<PHP> artisan view:clear
```

**Clean the queue, then search (in this order):**
1. **[READ] Note the starting point:** the **Review queue** strip on the Opportunities page shows Awaiting review, Overdue, Snoozed, Approved, Rejected and Archived. **Awaiting review** is exactly the set of jobs the reminders cover.
2. **Optional quiet period:** Configuration → Agent & discovery → switch **Review reminders** off while you clean. Deadlines are still tracked. This is a PMS setting; switch it back on in step 6.
3. **[CHANGE: PMS data] Archive.** Open Opportunities, choose a tab (**Awaiting Review**, **Overdue**, **New Matches**) or a filter (for example Status = New with a search), and check the count.
   - Tick rows, or tick the header box and then **Select all N jobs matching this filter**.
   - Press **Archive Selected** and confirm the number. Read the summary; jobs that could not be archived are listed with a reason.
   - Repeat per filter. "Show all" plus Status = New covers hidden SKIP jobs too.
4. **[READ] Verify that archived jobs cannot remind:**
   ```bash
   <PHP> artisan upwork:review-reminders     # prints counts; archived jobs are not candidates
   ```
   ```sql
   -- both must return 0
   SELECT COUNT(*) FROM upwork_opportunities WHERE status = 'archived' AND review_reminded_at > archived_at;
   SELECT COUNT(*) FROM upwork_opportunity_logs l JOIN upwork_opportunities o ON o.id = l.upwork_opportunity_id
     WHERE o.status = 'archived' AND l.action IN ('review_reminder', 'review_overdue') AND l.created_at > o.archived_at;
   ```
   Then check the Opportunities list: the **New Matches** count drops, and the archived jobs appear only in **Archived**.
5. **[READ] Preview the search.** Configuration → Overview → **Next search preview** shows the current queue, how many read-only searches a run would make now and for which profiles, and today's search budget. It also shows an *estimate* of new jobs, based on the last 7 days. Opening the page searches nothing.
6. **[CHANGE: read-only Upwork calls] Search manually.** As Super Admin, Configuration → **Run discovery now**, or `<PHP> artisan upwork:discover --manual`. Rediscovered archived jobs stay archived; new jobs arrive as usual.
7. If you switched reminders off in step 2, switch them back on.

**Restore by mistake-archived jobs:** Archived tab → tick → **Restore Selected** (or **Restore** on a row or the job page). Jobs return to their previous status. Jobs back in review get a fresh 30-minute deadline, with the first reminder after 10 minutes.

**Rollback:** code rollback as below. Archived rows keep `status = 'archived'` and old code does not list them in any tab, so restore them first (Archived tab → select all matching → Restore Selected). Alternatively, return each one to its previous status in SQL: `UPDATE upwork_opportunities SET status = archived_from_status WHERE status = 'archived'` [CHANGE]. Schema only: `migrate:rollback --path=…2026_10_16_100000…`, after archived rows are restored.

## 14. Location eligibility (India-based applicants)

Code plus one additive migration: `2026_10_17_100000_add_location_eligibility_to_upwork_opportunities_table`. It adds 14 nullable columns plus one index on `location_eligibility`. It requires §13's migration first (it places the columns after `archived_from_status`). The migration changes no existing value. Nothing in this release calls Upwork, and no setting changes on deploy.

```bash
<PHP> artisan migrate --pretend --force --path=database/migrations/2026_10_17_100000_add_location_eligibility_to_upwork_opportunities_table.php   # [READ]
<PHP> artisan migrate --force --path=database/migrations/2026_10_17_100000_add_location_eligibility_to_upwork_opportunities_table.php      # [CHANGE]
<PHP> artisan view:clear
<PHP> artisan migrate:status | grep 2026_10_17_100000           # [READ] "Yes"
```

- **New and re-seen jobs** are classified automatically. Restricted jobs are held at SKIP (status untouched) and kept out of review, reminders, notifications, AI analysis, approval and proposals. See *Location eligibility* in `docs/upwork-opportunities.md`.
- **Existing jobs** need the backfill, which needs its own approval:
  ```bash
  <PHP> artisan upwork:classify-locations                       # [READ] dry run: lists affected jobs and the proposed action, saves nothing
  <PHP> artisan upwork:classify-locations --all-rows            # [READ] the same, listing every job examined
  <PHP> artisan upwork:classify-locations --apply               # [CHANGE: PMS data] asks for confirmation in production
  ```
  - `--apply` writes only the `location_*` fields, plus `recommendation` / `hard_filters` for restricted jobs.
  - No status changes, and no record is created or deleted.
  - It sends no notifications or reminders.
  - A second run reports `0 of N`.
- **[READ] Verify after the backfill:**
  ```sql
  -- must return 0: restricted jobs never in the reminder set
  SELECT COUNT(*) FROM upwork_opportunities WHERE location_eligibility = 'restricted' AND status = 'new' AND recommendation <> 'skip';
  SELECT COUNT(*) FROM upwork_opportunity_logs l JOIN upwork_opportunities o ON o.id = l.upwork_opportunity_id
    WHERE o.location_eligibility = 'restricted' AND l.action = 'review_reminder' AND l.created_at > o.location_classified_at;
  ```
- **Smoke test [READ]:** the Opportunities page shows the **Location** strip and the **Skipped: Location** and **Location Unclear** tabs. A job page shows the **Location eligibility** panel with its correction form.
- **Rollback:**
  - Code rollback as below. The columns can stay, because old code ignores them.
  - Restricted jobs keep `recommendation = skip` and the location reason in `hard_filters` until they are re-scored. To release them all first: `UPDATE upwork_opportunities SET recommendation = location_prior_recommendation, hard_filters = JSON_REMOVE(hard_filters, JSON_UNQUOTE(JSON_SEARCH(hard_filters, 'one', 'Location restriction: India-based applicants are not eligible'))) WHERE location_eligibility = 'restricted'` [CHANGE].
  - Schema only: `migrate:rollback --path=…2026_10_17_100000…`.

## Rollback

Choose the smallest step that fixes the problem.

1. **Switch off** (no deploy): keep `UPWORK_SCHEDULER_ENABLED=false`, `agent.enabled` and `ai.enabled` off. The module is then passive.
2. **Disconnect Upwork:** Agent & discovery → Disconnect (deletes tokens), and revoke the PMS in the Upwork account's connected apps.
3. **Code rollback:** revert the release merge on the deploy branch (`git revert -m 1 <merge-commit>` locally, reviewed and pushed as usual), then on the server:
   ```bash
   git pull --ff-only origin <deploy-branch>
   <PHP> artisan config:clear && <PHP> artisan config:cache && <PHP> artisan view:clear
   git log --oneline -1                   # [READ] matches the reverted state
   ```
   In an emergency, `git checkout <PREVIOUS_RELEASE>` (detached) restores the previous code exactly. Return to the branch afterwards. The new tables can stay; old code ignores them.
4. **Schema rollback:** this deletes all module data, so export first if needed. Roll back newest first, one path at a time:
   ```bash
   for m in 2026_10_16_100000_add_archive_columns_to_upwork_opportunities_table 2026_10_15_100000_add_target_profile_columns_to_upwork_opportunities_table 2026_10_13_100000_add_review_sla_columns_to_upwork_opportunities_table 2026_10_09_100500_add_fingerprint_to_upwork_clients_table 2026_10_09_100400_create_upwork_api_credentials_table \
    2026_10_09_100300_create_upwork_queue_jobs_table 2026_10_09_100200_create_upwork_ai_analyses_table \
    2026_10_09_100100_add_tiers_to_upwork_search_profiles_table 2026_10_09_100000_create_upwork_ai_usage_table \
    2026_10_08_100900_add_notification_columns_to_upwork_opportunities_table 2026_10_08_100800_create_upwork_config_logs_table \
    2026_10_08_100700_add_outlook_columns_to_upwork_opportunities_table 2026_10_08_100600_create_upwork_classifications_table \
    2026_10_08_100500_create_upwork_clients_tables 2026_10_08_100400_create_upwork_discovery_tables \
    2026_10_08_100300_add_scoring_columns_to_upwork_opportunities_table 2026_10_08_100200_create_upwork_scoring_tables \
    2026_10_08_100100_create_upwork_search_profiles_table 2026_10_08_100000_create_upwork_technology_catalog_tables \
    2026_10_07_100100_create_upwork_opportunity_logs_table 2026_10_07_100000_create_upwork_opportunities_table; do
     <PHP> artisan migrate:rollback --force --path=database/migrations/$m.php
   done
   ```
   After a migration that stopped partway (step 5), first check which `upwork_*` tables exist (`SHOW TABLES LIKE 'upwork\_%'`), then roll back only the migrations recorded as run.
5. **Full restore:** restore the step 2 database dump and `.env` copy. This is the last resort, because it also reverts any non-Upwork data written since the backup.
