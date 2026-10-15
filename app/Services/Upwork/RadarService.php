<?php

namespace App\Services\Upwork;

use App\Models\UpworkClassification;
use App\Models\UpworkDiscoveryQuery;
use App\Models\UpworkDiscoveryRun;
use App\Models\UpworkOpportunity;
use App\Models\UpworkOpportunityLog;
use App\Models\UpworkOpportunitySighting;
use App\Models\UpworkSearchProfile;
use App\Models\UpworkSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Opportunity Radar: every number is a database query over a time window.
 * Nothing is estimated except where labelled (expected value).
 */
class RadarService
{
    const RANGES = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days'];

    /** @return array{0: Carbon, 1: Carbon} window start/end in the app timezone */
    public static function window(string $range): array
    {
        $end = now();
        switch ($range) {
            case '7d':
                return [$end->copy()->subDays(6)->startOfDay(), $end];
            case '30d':
                return [$end->copy()->subDays(29)->startOfDay(), $end];
            default:
                return [$end->copy()->startOfDay(), $end];
        }
    }

    public function radar(string $range = 'today'): array
    {
        [$from, $to] = self::window($range);
        $viableMin = (float) UpworkSetting::get('discovery.enrich_min_score');

        // Jobs first seen in the window (directly ingested jobs have no sighting: use created_at).
        $inWindow = fn() => UpworkOpportunity::whereRaw('COALESCE(first_seen_at, created_at) >= ?', [$from])
            ->whereRaw('COALESCE(first_seen_at, created_at) <= ?', [$to]);
        $relevant = fn() => $inWindow()->where('discovery_ignored', false)->whereNotNull('score');

        $funnel = [
            'results' => (int) UpworkDiscoveryQuery::whereBetween('created_at', [$from, $to])->sum('results_count'),
            'discovered' => $inWindow()->count(),
            'relevant' => $relevant()->count(),
            'viable' => $relevant()->where('score', '>=', $viableMin)->count(),
            'review' => $relevant()->where('recommendation', UpworkOpportunity::RECOMMENDATION_REVIEW)->count(),
            'apply' => $relevant()->where('recommendation', UpworkOpportunity::RECOMMENDATION_APPLY)->count(),
            // A non-empty JSON list starts with ["  (portable across MySQL JSON, MariaDB and SQLite text).
            'hard_rejected' => $inWindow()->where('hard_filters', 'like', '["%')->count(),
            'ignored' => $inWindow()->where('discovery_ignored', true)->count(),
            'viable_min' => $viableMin,
        ];

        $categories = [];
        foreach (UpworkClassification::where('enabled', true)->ordered()->get() as $c) {
            $count = $relevant()->where('classifications', 'like', '%"' . $c->code . '"%')->count();
            if ($count > 0) {
                $categories[] = ['classification' => $c, 'count' => $count];
            }
        }

        $top = $relevant()->whereNotIn('status', [UpworkOpportunity::STATUS_REJECTED, UpworkOpportunity::STATUS_ARCHIVED])
            ->orderByDesc('score')->orderByDesc('id')->limit(10)->get();

        $openEv = $relevant()->whereIn('recommendation', [UpworkOpportunity::RECOMMENDATION_APPLY, UpworkOpportunity::RECOMMENDATION_REVIEW])
            ->whereIn('status', [UpworkOpportunity::STATUS_NEW, UpworkOpportunity::STATUS_APPROVED, UpworkOpportunity::STATUS_PROPOSAL_DRAFT]);

        return [
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'funnel' => $funnel,
            'categories' => $categories,
            'top' => $top,
            'expected_value' => [
                'total' => (float) (clone $openEv)->sum('expected_value'),
                'jobs' => (clone $openEv)->whereNotNull('expected_value')->count(),
            ],
            'pipeline' => $this->pipeline(),
            'decisions' => $this->decisions($from, $to),
            'health' => $this->health($from, $to),
            'profiles' => $this->profiles($from, $to, $viableMin),
            'targets' => $this->targets($relevant),
        ];
    }

    /**
     * Dual-track reporting. Best fit: relevant jobs in the window per track
     * (exclusive counts). Bid as: what reviewers chose, across the whole
     * pipeline (not windowed), with outcomes.
     */
    public function targets(callable $relevant): array
    {
        $fit = [];
        foreach (array_keys(\App\Services\Upwork\Targeting\TargetProfiles::TRACKS) as $track) {
            $fit[$track] = [
                'relevant' => $relevant()->where('target_profile', $track)->count(),
                'review_plus' => $relevant()->where('target_profile', $track)
                    ->whereIn('recommendation', [UpworkOpportunity::RECOMMENDATION_APPLY, UpworkOpportunity::RECOMMENDATION_REVIEW])->count(),
            ];
        }
        $fit['unclassified'] = ['relevant' => $relevant()->whereNull('target_profile')->count(), 'review_plus' => null];

        $bidAs = [];
        foreach ([\App\Services\Upwork\Targeting\TargetProfiles::TRACK_AGENCY, \App\Services\Upwork\Targeting\TargetProfiles::TRACK_INDIVIDUAL] as $track) {
            $counts = UpworkOpportunity::where('selected_profile', 'like', $track . '%')
                ->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');
            $won = (int) ($counts[UpworkOpportunity::STATUS_WON] ?? 0);
            $lost = (int) ($counts[UpworkOpportunity::STATUS_LOST] ?? 0);
            $bidAs[$track] = [
                'total' => (int) $counts->sum(),
                'submitted' => (int) $counts->only([UpworkOpportunity::STATUS_SUBMITTED, UpworkOpportunity::STATUS_CLIENT_RESPONSE, UpworkOpportunity::STATUS_WON, UpworkOpportunity::STATUS_LOST])->sum(),
                'won' => $won,
                'lost' => $lost,
                'win_rate' => ($won + $lost) > 0 ? round($won / ($won + $lost) * 100, 1) : null,
            ];
        }

        return ['fit' => $fit, 'bid_as' => $bidAs];
    }

    /** Current workflow state across all opportunities (not windowed). */
    public function pipeline(): array
    {
        $counts = UpworkOpportunity::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');
        $won = (int) ($counts[UpworkOpportunity::STATUS_WON] ?? 0);
        $lost = (int) ($counts[UpworkOpportunity::STATUS_LOST] ?? 0);

        return [
            'new_matches' => self::view('new_matches')->count(),
            'approved' => (int) ($counts[UpworkOpportunity::STATUS_APPROVED] ?? 0),
            'proposal_drafts' => self::view('proposal_drafts')->count(),
            'ready_to_submit' => self::view('ready_to_submit')->count(),
            'submitted' => (int) ($counts[UpworkOpportunity::STATUS_SUBMITTED] ?? 0),
            'client_response' => (int) ($counts[UpworkOpportunity::STATUS_CLIENT_RESPONSE] ?? 0),
            'won' => $won,
            'lost' => $lost,
            'win_rate' => ($won + $lost) > 0 ? round($won / ($won + $lost) * 100, 1) : null,
        ];
    }

    /** Human approve/reject decisions in the window, compared with the recommendation at the time. */
    public function decisions(Carbon $from, Carbon $to): array
    {
        $logs = UpworkOpportunityLog::whereIn('action', [UpworkOpportunity::STATUS_APPROVED, UpworkOpportunity::STATUS_REJECTED])
            ->whereBetween('created_at', [$from, $to])->with('opportunity:id,recommendation')->get();

        $agree = 0;
        foreach ($logs as $log) {
            $rec = optional($log->opportunity)->recommendation;
            $positive = in_array($rec, [UpworkOpportunity::RECOMMENDATION_APPLY, UpworkOpportunity::RECOMMENDATION_REVIEW], true);
            if (($log->action === UpworkOpportunity::STATUS_APPROVED) === $positive) {
                $agree++;
            }
        }

        return [
            'approved' => $logs->where('action', UpworkOpportunity::STATUS_APPROVED)->count(),
            'rejected' => $logs->where('action', UpworkOpportunity::STATUS_REJECTED)->count(),
            'agreement' => $logs->count() ? round($agree / $logs->count() * 100) : null,
        ];
    }

    public function health(Carbon $from, Carbon $to): array
    {
        $runs = UpworkDiscoveryRun::whereBetween('started_at', [$from, $to])->get();
        $finished = $runs->filter(fn($r) => $r->started_at && $r->finished_at);
        $queries = UpworkDiscoveryQuery::whereBetween('created_at', [$from, $to]);

        return [
            'agent_enabled' => (bool) UpworkSetting::get('agent.enabled'),
            'interval' => (int) UpworkSetting::get('agent.interval_minutes'),
            'runs' => $runs->count(),
            'completed' => $runs->where('status', UpworkDiscoveryRun::STATUS_COMPLETED)->count(),
            'failed' => $runs->where('status', UpworkDiscoveryRun::STATUS_FAILED)->count(),
            'abandoned' => $runs->where('status', UpworkDiscoveryRun::STATUS_ABANDONED)->count(),
            'running' => $runs->where('status', UpworkDiscoveryRun::STATUS_RUNNING)->count(),
            'avg_duration_s' => $finished->count() ? (int) round($finished->avg(fn($r) => $r->finished_at->diffInSeconds($r->started_at))) : null,
            'queries' => (clone $queries)->count(),
            'failed_queries' => (clone $queries)->whereIn('status', ['error', 'rate_limited'])->count(),
            'rate_limited' => (clone $queries)->where('status', 'rate_limited')->count(),
            'last_run' => UpworkDiscoveryRun::latest('id')->first(),
            'backing_off' => UpworkSearchProfile::where('enabled', true)->where('backoff_until', '>', now())->get(['name', 'backoff_until', 'last_error']),
        ];
    }

    /** Per search profile: jobs it found first in the window, and how many were relevant / worth reviewing. */
    public function profiles(Carbon $from, Carbon $to, float $viableMin): array
    {
        $rows = [];
        foreach (UpworkSearchProfile::ordered()->get() as $profile) {
            $sightings = UpworkOpportunitySighting::where('search_profile_id', $profile->id)->whereBetween('first_seen_at', [$from, $to]);
            $ids = (clone $sightings)->pluck('opportunity_id');
            $found = $ids->count();
            if ($found === 0 && !$profile->enabled) {
                continue;
            }
            $opps = UpworkOpportunity::whereIn('id', $ids);
            $rows[] = [
                'profile' => $profile,
                'found' => $found,
                'ignored' => (clone $sightings)->where('ignored', true)->count(),
                'viable' => (clone $opps)->where('discovery_ignored', false)->where('score', '>=', $viableMin)->count(),
                'review_plus' => (clone $opps)->where('discovery_ignored', false)->whereIn('recommendation', [UpworkOpportunity::RECOMMENDATION_APPLY, UpworkOpportunity::RECOMMENDATION_REVIEW])->count(),
            ];
        }

        return $rows;
    }

    /**
     * Named list views used by the opportunity list tabs and the pipeline.
     */
    public static function view(string $view)
    {
        $q = UpworkOpportunity::query();

        switch ($view) {
            case 'new_matches':
                // Untouched jobs worth a human look: REVIEW/APPLY, or a surfaced archetype (e.g. quick win).
                return $q->where('status', UpworkOpportunity::STATUS_NEW)->where('discovery_ignored', false)
                    ->where(function ($w) {
                        $w->whereIn('recommendation', [UpworkOpportunity::RECOMMENDATION_APPLY, UpworkOpportunity::RECOMMENDATION_REVIEW]);
                        foreach (UpworkClassification::surfacedCodes() as $code) {
                            $w->orWhere('classifications', 'like', '%"' . $code . '"%');
                        }
                    });
            case 'review':
                return $q->where('status', UpworkOpportunity::STATUS_NEW)->where('discovery_ignored', false)->where('recommendation', UpworkOpportunity::RECOMMENDATION_REVIEW);
            case 'apply':
                return $q->where('status', UpworkOpportunity::STATUS_NEW)->where('discovery_ignored', false)->where('recommendation', UpworkOpportunity::RECOMMENDATION_APPLY);
            case 'approved':
                return $q->where('status', UpworkOpportunity::STATUS_APPROVED);
            case 'rejected':
                return $q->where('status', UpworkOpportunity::STATUS_REJECTED);
            case 'archived':
                return $q->where('status', UpworkOpportunity::STATUS_ARCHIVED);
            case 'proposal_drafts':
                return $q->where('status', UpworkOpportunity::STATUS_PROPOSAL_DRAFT)->whereNull('proposal_approved_at');
            case 'ready_to_submit':
                // Proposal approved by a human, not yet submitted (submission stays manual on Upwork).
                return $q->where('status', UpworkOpportunity::STATUS_PROPOSAL_DRAFT)->whereNotNull('proposal_approved_at')->whereNull('submitted_at');
        }

        return $q;
    }

    const VIEWS = [
        'new_matches' => 'New Matches',
        'review' => 'Review',
        'apply' => 'Apply',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'proposal_drafts' => 'Proposal Drafts',
        'ready_to_submit' => 'Ready to Submit',
        'archived' => 'Archived',
    ];
}
