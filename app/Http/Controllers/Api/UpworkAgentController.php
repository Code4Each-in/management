<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Services\Upwork\Discovery\DiscoveryException;
use App\Services\Upwork\Discovery\DiscoveryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Discovery endpoints for the Mac mini agent (Sanctum token with upwork:ingest).
 * The agent is a worker: it reads its configuration and query plan from here,
 * runs read-only Upwork searches, and reports back. Nothing here calls Upwork.
 */
class UpworkAgentController extends Controller
{
    const MAX_ROWS_PER_QUERY = 50;

    /** @var DiscoveryService */
    private $discovery;

    public function __construct(DiscoveryService $discovery)
    {
        $this->discovery = $discovery;
    }

    public function config()
    {
        $this->authorize('ingest', UpworkOpportunity::class);

        return response()->json(['success' => true, 'data' => $this->discovery->config()]);
    }

    public function startRun(Request $request)
    {
        $this->authorize('ingest', UpworkOpportunity::class);

        $validated = $request->validate([
            'agent_host' => ['nullable', 'string', 'max:100'],
            'trigger' => ['nullable', Rule::in(['scheduled', 'manual'])],
        ]);

        try {
            $run = $this->discovery->startRun($request->user()->id, $validated['agent_host'] ?? null, $validated['trigger'] ?? 'scheduled');
        } catch (DiscoveryException $e) {
            return $this->refuse($e);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'run' => $this->runSummary($run),
                'plan' => $run->plan,
                'limits' => $this->discovery->config()['agent'] + $this->discovery->config()['discovery'],
            ],
        ], 201);
    }

    public function heartbeat(UpworkDiscoveryRun $run)
    {
        $this->authorize('ingest', UpworkOpportunity::class);

        try {
            $this->discovery->heartbeat($run);
        } catch (DiscoveryException $e) {
            return $this->refuse($e);
        }

        return response()->json(['success' => true, 'data' => $this->runSummary($run)]);
    }

    public function recordQuery(Request $request, UpworkDiscoveryRun $run)
    {
        $this->authorize('ingest', UpworkOpportunity::class);

        $validated = $request->validate([
            'query' => ['required', 'array'],
            'query.search_profile_id' => ['nullable', 'integer', 'exists:upwork_search_profiles,id'],
            'query.type' => ['required', Rule::in(['query', 'title'])],
            'query.text' => ['required', 'string', 'max:255'],
            'query.params' => ['nullable', 'array'],
            'query.page' => ['nullable', 'integer', 'min:1', 'max:10'],
            'query.status' => ['nullable', Rule::in(['ok', 'error', 'rate_limited'])],
            'query.error' => ['nullable', 'string', 'max:2000'],
            'query.duration_ms' => ['nullable', 'integer', 'min:0'],
            'jobs' => ['present', 'array', 'max:' . self::MAX_ROWS_PER_QUERY],
        ]);

        // Validate rows one by one with the same rules as full ingestion, so a single
        // bad row is audited as invalid data without failing the whole batch.
        [$valid, $invalid] = \App\Services\Upwork\Discovery\RowValidator::split($validated['jobs']);

        try {
            $results = $this->discovery->recordQuery($run, $validated['query'], $valid, $invalid, $request->user()->id);
        } catch (DiscoveryException $e) {
            return $this->refuse($e);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'invalid' => array_values($invalid),
                'enrich' => array_values(array_column(array_filter($results, fn($r) => $r['enrich']), 'upwork_job_id')),
            ],
        ]);
    }

    public function completeRun(Request $request, UpworkDiscoveryRun $run)
    {
        $this->authorize('ingest', UpworkOpportunity::class);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['completed', 'failed'])],
            'enriched' => ['nullable', 'integer', 'min:0'],
            'enrich_errors' => ['nullable', 'integer', 'min:0'],
            'error' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $run = $this->discovery->completeRun($run, $validated['status'], $validated);
        } catch (DiscoveryException $e) {
            return $this->refuse($e);
        }

        return response()->json(['success' => true, 'data' => $this->runSummary($run) + ['stats' => $run->stats]]);
    }

    private function runSummary(UpworkDiscoveryRun $run): array
    {
        return [
            'uuid' => $run->uuid,
            'status' => $run->status,
            'trigger' => $run->trigger,
            'ruleset_version' => $run->ruleset_version,
            'started_at' => optional($run->started_at)->toIso8601String(),
            'lease_expires_at' => optional($run->lease_expires_at)->toIso8601String(),
            'finished_at' => optional($run->finished_at)->toIso8601String(),
        ];
    }

    private function refuse(DiscoveryException $e)
    {
        return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 409);
    }
}
