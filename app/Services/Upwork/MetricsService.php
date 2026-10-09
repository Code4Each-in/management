<?php

namespace App\Services\Upwork;

use App\Models\UpworkAiAnalysis;
use App\Models\UpworkAiUsage;
use App\Models\UpworkDiscoveryQuery;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkOpportunityLog;
use App\Services\Upwork\Ai\AiBudgetGuard;
use Illuminate\Support\Carbon;

/**
 * Cost and efficiency of the pipeline for a period: Upwork calls, local
 * filtering, AI use and spend, and what came out of it (good jobs, human
 * decisions). Costs are the module's own estimates from configured prices.
 */
class MetricsService
{
    public function summary(Carbon $from, Carbon $to): array
    {
        $queries = UpworkDiscoveryQuery::whereBetween('created_at', [$from, $to]);
        $runs = UpworkDiscoveryRun::whereBetween('started_at', [$from, $to])->get(['stats']);
        $newJobs = UpworkOpportunity::whereBetween('first_seen_at', [$from, $to]);

        $discovery = [
            'searches' => (clone $queries)->count(),
            'detail_lookups' => (int) $runs->sum(fn($r) => ($r->stats['enriched'] ?? 0) + ($r->stats['enrich_errors'] ?? 0)),
            'results' => (int) (clone $queries)->sum('results_count'),
            'new_jobs' => (clone $newJobs)->count(),
            'duplicates' => (int) (clone $queries)->sum('duplicate_count') + (int) (clone $queries)->sum('existing_count'),
            'filtered' => (int) (clone $queries)->sum('hard_rejected_count') + (int) (clone $queries)->sum('ignored_count'),
        ];

        $usage = UpworkAiUsage::whereBetween('created_at', [$from, $to])->get(['status', 'input_tokens', 'output_tokens', 'reserved_tokens', 'estimated_cost_usd', 'reserved_cost_usd', 'request_type']);
        $analyses = UpworkAiAnalysis::whereBetween('created_at', [$from, $to]);
        $guard = new AiBudgetGuard();
        $status = $guard->status();

        $ai = [
            'requests' => $usage->count(),
            'failed_requests' => $usage->where('status', UpworkAiUsage::STATUS_FAILED)->count(),
            'tokens' => (int) $usage->sum(fn($u) => in_array($u->status, [UpworkAiUsage::STATUS_RESERVED, UpworkAiUsage::STATUS_ABANDONED], true)
                ? $u->reserved_tokens : (int) $u->input_tokens + (int) $u->output_tokens),
            'cost' => round((float) $usage->sum(fn($u) => $u->budget_cost), 6),
            'deep_requests' => $usage->where('request_type', 'deep')->count(),
            'analyzed_jobs' => (clone $analyses)->where('status', UpworkAiAnalysis::STATUS_SUCCEEDED)->distinct('opportunity_id')->count('opportunity_id'),
            'skipped_jobs' => (clone $newJobs)->where('ai_status', UpworkOpportunity::AI_SKIPPED)->count(),
            'blocked_jobs' => (clone $newJobs)->where('ai_status', UpworkOpportunity::AI_BLOCKED)->count(),
            'month_cost' => $status['month']['cost'],
            'monthly_budget' => $status['monthly_budget_usd'],
            'monthly_remaining' => $status['monthly_remaining_usd'],
            'blocked' => $status['blocked'],
        ];

        $logs = UpworkOpportunityLog::whereBetween('created_at', [$from, $to])
            ->whereIn('action', [UpworkOpportunity::STATUS_APPROVED, UpworkOpportunity::STATUS_REJECTED])->get(['upwork_opportunity_id', 'action']);
        $outcomes = [
            'score_70' => (clone $newJobs)->where('score', '>=', 70)->count(),
            'score_80' => (clone $newJobs)->where('score', '>=', 80)->count(),
            'approved' => $logs->where('action', UpworkOpportunity::STATUS_APPROVED)->count(),
            'rejected' => $logs->where('action', UpworkOpportunity::STATUS_REJECTED)->count(),
        ];

        // Did the AI agree with people? apply/review ~ approved, skip ~ rejected.
        $aiRecs = UpworkOpportunity::whereIn('id', $logs->pluck('upwork_opportunity_id')->unique())->whereNotNull('ai_analysis_id')
            ->with('aiAnalysis:id,recommendation')->get()->mapWithKeys(fn($o) => [$o->id => optional($o->aiAnalysis)->recommendation]);
        $compared = $agreed = 0;
        foreach ($logs as $log) {
            $rec = $aiRecs[$log->upwork_opportunity_id] ?? null;
            if ($rec === null) {
                continue;
            }
            $compared++;
            $agreed += (int) (($log->action === UpworkOpportunity::STATUS_APPROVED) === in_array($rec, ['apply', 'review'], true));
        }

        $per = fn(float $cost, int $n) => $n > 0 ? round($cost / $n, 4) : null;
        $ratio = fn(int $a, int $b) => $b > 0 ? round($a / $b, 2) : null;

        return [
            'from' => $from,
            'to' => $to,
            'discovery' => $discovery,
            'ai' => $ai,
            'outcomes' => $outcomes,
            'efficiency' => [
                'searches_per_new_job' => $ratio($discovery['searches'], $discovery['new_jobs']),
                'ai_share_pct' => $discovery['new_jobs'] > 0 ? round($ai['analyzed_jobs'] / $discovery['new_jobs'] * 100, 1) : null,
                'cost_per_analyzed_job' => $per($ai['cost'], $ai['analyzed_jobs']),
                'cost_per_70_job' => $per($ai['cost'], $outcomes['score_70']),
                'cost_per_approved_job' => $per($ai['cost'], $outcomes['approved']),
                'ai_human_agreement_pct' => $compared > 0 ? round($agreed / $compared * 100) : null,
                'ai_human_compared' => $compared,
            ],
        ];
    }
}
