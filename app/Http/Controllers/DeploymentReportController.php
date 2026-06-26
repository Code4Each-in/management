<?php

namespace App\Http\Controllers;

use App\Models\DeploymentTicket;
use App\Models\DeploymentBug;
use App\Models\DeploymentReviewHistory;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeploymentReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $developerMetrics = $this->developerMetrics($from, $to);
        $reviewerMetrics = $this->reviewerMetrics($from, $to);
        $qaMetrics = $this->qaMetrics($from, $to); 

        return view('deployment.reports.index', compact('developerMetrics', 'reviewerMetrics', 'qaMetrics', 'from', 'to'));
    }

    private function applyDateRange($query, $from, $to, $column = 'created_at')
    {
        if ($from) {
            $query->whereDate($column, '>=', $from);
        }
        if ($to) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * Developer Metrics: # Deployments, Review Pass Rate, Review Rejections, Bugs Raised, Bugs Fixed.
     */
    private function developerMetrics($from, $to)
    {
        $developers = Users::where('role_id', 3)->where('status', 1)->orderBy('first_name')->get();

        return $developers->map(function ($dev) use ($from, $to) {
            $ticketsQuery = $this->applyDateRange(
                DeploymentTicket::where('assigned_developer_id', $dev->id),
                $from,
                $to
            );
            $deploymentCount = (clone $ticketsQuery)->count();

            $reviewsQuery = $this->applyDateRange(
                DeploymentReviewHistory::whereHas('ticket', function ($q) use ($dev) {
                    $q->where('assigned_developer_id', $dev->id);
                })->whereIn('action', ['Approved', 'Rejected']),
                $from,
                $to
            );

            $totalReviewed = (clone $reviewsQuery)->count();
            $approved = (clone $reviewsQuery)->where('action', 'Approved')->count();
            $rejected = (clone $reviewsQuery)->where('action', 'Rejected')->count();

            $passRate = $totalReviewed > 0 ? round(($approved / $totalReviewed) * 100, 1) : null;

            $bugsRaised = $this->applyDateRange(
                DeploymentBug::whereHas('ticket', function ($q) use ($dev) {
                    $q->where('assigned_developer_id', $dev->id);
                }),
                $from,
                $to
            )->count();

            $bugsFixed = $this->applyDateRange(
                DeploymentBug::where('assigned_developer_id', $dev->id)
                    ->whereIn('status', ['Fixed', 'Ready For Retest', 'Closed']),
                $from,
                $to
            )->count();

            return [
                'developer' => $dev,
                'deployments' => $deploymentCount,
                'review_pass_rate' => $passRate,
                'review_rejections' => $rejected,
                'bugs_raised' => $bugsRaised,
                'bugs_fixed' => $bugsFixed,
            ];
        })->filter(fn ($row) => $row['deployments'] > 0 || $row['bugs_fixed'] > 0)->values();
    }

    /**
     * Reviewer Metrics: Reviews Completed, Average Review Time, Rejections Issued.
     */
    private function reviewerMetrics($from, $to)
    {
        $reviewers = Users::where('role_id', 3)->where('status', 1)->orderBy('first_name')->get();

        return $reviewers->map(function ($reviewer) use ($from, $to) {
            $base = $this->applyDateRange(
                DeploymentReviewHistory::where('reviewer_id', $reviewer->id)
                    ->whereIn('action', ['Approved', 'Rejected', 'Changes Requested']),
                $from,
                $to
            );

            $completed = (clone $base)->count();
            $rejections = (clone $base)->where('action', 'Rejected')->count();
            $avgTime = (clone $base)->whereNotNull('time_spent_minutes')->avg('time_spent_minutes');

            return [
                'reviewer' => $reviewer,
                'reviews_completed' => $completed,
                'avg_review_time_minutes' => $avgTime ? round($avgTime, 1) : null,
                'rejections_issued' => $rejections,
            ];
        })->filter(fn ($row) => $row['reviews_completed'] > 0)->values();
    }

    /**
     * QA Metrics: Bugs Found, Bugs Reopened, Testing Approvals.
     */
    private function qaMetrics($from, $to) 
    {
        $testers = Users::whereIn('role_id', [1, 3])->where('status', 1)->orderBy('first_name')->get();
        

        return $testers->map(function ($tester) use ($from, $to) {
            $bugsFound = $this->applyDateRange(
                DeploymentBug::where('reported_by', $tester->id),
                $from,
                $to
            )->count();

            $bugsReopened = $this->applyDateRange(
                DeploymentBug::where('reported_by', $tester->id)->where('status', 'Reopened'),
                $from,
                $to
            )->count();

            $testingApprovals = $this->applyDateRange(
                DeploymentTicket::where('qa_tester_id', $tester->id)->where('qa_approved', true),
                $from,
                $to,
                'updated_at'
            )->count();

            return [
                'tester' => $tester,
                'bugs_found' => $bugsFound,
                'bugs_reopened' => $bugsReopened,
                'testing_approvals' => $testingApprovals,
            ];
        })->filter(fn ($row) => $row['bugs_found'] > 0 || $row['testing_approvals'] > 0)->values();
    }
}
