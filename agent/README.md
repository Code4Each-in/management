# Code4Each Upwork discovery worker (Mac mini)

> **Development and manual use only.** In production, discovery runs on the PMS server through the official read-only Upwork API (`php artisan upwork:discover`, scheduled), and AI qualification uses a low-cost OpenAI model under a hard budget. See `docs/upwork-opportunities.md`. Don't schedule this worker in production: each headless Claude Code cycle cost about $0.42–$0.86 per call in the first real run.

The worker runs read-only Upwork job searches for the PMS. It holds no business configuration. Search profiles, scoring rules, the schedule and the on/off switch all live in the PMS (**Upwork → Configuration**). Each cycle it:

1. Reads its configuration from the PMS. If the agent is disabled there, or the next cycle isn't due yet, it exits.
2. Asks the PMS to start a run. The PMS hands out a lease (so runs never overlap) and the searches to run.
3. For each search profile, runs headless Claude Code (`claude -p`) to execute those searches through the **read-only** Upwork MCP, then reports every page of results back to the PMS.
4. Fetches full details (`find_jobs get`) for the jobs the PMS flags as worth a closer look, and sends them to the PMS. **The PMS does all scoring.**
5. Completes the run. The PMS records the statistics and applies backoff to any search profile that failed.

## Safety model

| Layer | What it enforces |
|---|---|
| `--strict-mcp-config --mcp-config agent/mcp.json` | Only the Upwork MCP server is loaded. |
| `--allowedTools` | Only `find_jobs` and `list_accounts`. |
| `--disallowedTools` | All 17 Upwork write tools, plus Bash, Read, Write, Edit, Glob, Grep, WebFetch, WebSearch, Task and Agent. |
| `~/.claude/settings.json` deny rules | The same 17 Upwork write tools, for every Claude session on the machine. |
| Empty working directory (`CLAUDE_WORKDIR`) | Claude can't read local files such as `.env`. |
| Prompts | Read-only instructions; the answer must match a JSON schema (`agent/schemas`). |
| PMS token | Read by the wrapper, from the macOS Keychain. **It is never given to Claude.** |
| `--max-budget-usd`, timeout | A cost cap and a time limit on every Claude call. |
| Logs (`LOG_DIR`) | JSON lines, with secrets redacted. |

Nothing in the worker or the PMS submits proposals, sends messages, saves jobs or changes profiles. Approving a job and approving a proposal remain human actions inside the PMS.

## Setup

1. **PMS token.** On the PMS: `php artisan upwork:agent-token <agent user email>`. Store the token in the Keychain:
   ```bash
   security add-generic-password -s code4each-upwork-agent -a pms -w '<token>'
   ```
2. **Configuration.** `cp agent/agent.env.example agent/agent.env`, then set `PMS_URL`, `ORG_UID` (the agency `org_uid` from `list_accounts`), `CLAUDE_BIN` and `LOG_DIR` (replace `YOUR_USER` with your macOS user name). `agent.env` is gitignored. The launchd plist has the same `YOUR_USER` placeholder in its log paths.
3. **Upwork login.** Run `claude` once interactively and complete the Upwork MCP OAuth (`/mcp`). The worker reuses that login.
4. **Check the setup.** This makes no Upwork calls and no PMS writes:
   ```bash
   php agent/upwork-agent.php --check
   ```

## Testing

- **Dry run, no Upwork.** The fake Claude replays the Test 7 fixtures:
  ```bash
  CLAUDE_BIN=agent/testing/fake-claude.php php agent/upwork-agent.php --manual --verbose
  ```
  `FAKE_CLAUDE_MODE=rate_limit` or `FAKE_CLAUDE_MODE=error` simulate failures.
- **One real manual cycle** (read-only Upwork calls; works even while the agent is disabled in the PMS):
  ```bash
  php agent/upwork-agent.php --manual --verbose
  ```
  Afterwards, check the run on the PMS Radar (Discovery health) and in `LOG_DIR`.

## Scheduling (not enabled by default)

`agent/launchd/com.code4each.upwork-agent.plist` starts the worker every 5 minutes. The worker exits immediately unless the agent is **enabled in the PMS** and the PMS interval (default 20 minutes) has passed. To install it, only after a successful manual run:

```bash
cp agent/launchd/com.code4each.upwork-agent.plist ~/Library/LaunchAgents/
launchctl load ~/Library/LaunchAgents/com.code4each.upwork-agent.plist
```

Then switch the agent on under PMS **Upwork → Configuration → Agent & discovery**. To stop it, switch the agent off in the PMS (it takes effect immediately) or run `launchctl unload` on the plist.

## Failure handling

- **PMS unreachable or 5xx:** retried 3 times with exponential backoff, then the cycle ends. The next cycle tries again.
- **Run already active (409) or agent disabled (423):** the cycle exits normally.
- **Claude error or timeout:** the affected searches are reported as `error`, and the PMS backs those profiles off exponentially.
- **Upwork rate limit:** the run stops, the searches are reported as `rate_limited` (which doubles the backoff), and the remaining profiles stay due for the next run.
- **Worker crash:** the PMS lease expires (`agent.lease_minutes`) and the run is marked abandoned on the next start.

## Cost (measured on the first real run, 8 Oct 2026)

One manual cycle, capped at 4 searches and 3 detail lookups, cost about **$1.73** and took about 5 minutes:

| Claude call | Time | Cost |
|---|---|---|
| Search: one profile, 2 searches (11 jobs) | 51 s | $0.42 |
| Search: one profile, 2 searches | 179 s | $0.86, **stopped by the budget cap** (the cap is checked between steps, so it can be overshot) |
| Enrichment: 3 jobs | 56 s | $0.45 |

At the seeded defaults (30 searches and 15 lookups per run, every 20 minutes) that would cost **hundreds of dollars a day**. Before enabling the schedule:

- **Smaller scope:** lower *Searches per run* (for example 8–12) and *Detail lookups per run* (for example 5), and raise the interval (for example 60–120 minutes).
- **Cheaper model:** try `CLAUDE_MODEL` set to a smaller model for search and normalisation, and compare result quality on a few manual runs.
- **Hard ceiling:** keep `CLAUDE_MAX_BUDGET_USD` as a per-call ceiling, and watch actual spend in `LOG_DIR` (`cost_usd` on every `claude.run` line).

## Verified on the first real run

- **Output shape:** the `--json-schema` output is read correctly (structured output).
- **Field mapping:** Upwork search and get fields map onto the PMS payload. 11 real rows were accepted with 0 invalid, and enriched jobs carry the client id, contractor type, hiring funnel, work history and catalog-only technology slugs.

## Still to watch

- **Claude Code upgrades** can change the CLI flags or output shape. Re-run `--check` and one manual cycle after upgrading.
- **Search relevance.** Related-technology names used as broad searches (for example "Legacy Modernization") return noisy results. Tune the profiles in the PMS, for example by using title phrases or excluded keywords.
