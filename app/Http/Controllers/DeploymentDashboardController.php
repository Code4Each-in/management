<?php

namespace App\Http\Controllers;

use App\Models\DeploymentTicket;
use App\Models\DeploymentBug;
use App\Models\Users;
use Illuminate\Http\Request;

class DeploymentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $stats = [
            'total' => DeploymentTicket::count(),
            'pending_review' => DeploymentTicket::where('status', DeploymentTicket::STATUS_REVIEW_PENDING)->count(),
            'review_in_progress' => DeploymentTicket::whereIn('status', [
                DeploymentTicket::STATUS_REVIEW_IN_PROGRESS,
                DeploymentTicket::STATUS_CHANGES_REQUESTED,
            ])->count(),
            'testing_in_progress' => DeploymentTicket::where('status', DeploymentTicket::STATUS_TESTING_IN_PROGRESS)->count(),
            'ready_for_deployment' => DeploymentTicket::where('status', DeploymentTicket::STATUS_READY_FOR_DEPLOYMENT)->count(),
            'approved' => DeploymentTicket::whereIn('status', [
                DeploymentTicket::STATUS_DEPLOYMENT_APPROVED,
                DeploymentTicket::STATUS_DEPLOYED,
            ])->count(),
            'rejected' => DeploymentTicket::whereIn('status', [
                DeploymentTicket::STATUS_REVIEW_REJECTED,
                DeploymentTicket::STATUS_DEPLOYMENT_REJECTED,
            ])->count(),
            'open_bugs' => DeploymentBug::whereNotIn('status', ['Closed'])->count(),
            'closed_bugs' => DeploymentBug::where('status', 'Closed')->count(),
        ];

        // Deployments per developer
        $deploymentsPerDeveloper = DeploymentTicket::selectRaw('assigned_developer_id, count(*) as total')
            ->whereNotNull('assigned_developer_id')
            ->groupBy('assigned_developer_id')
            ->with('developer:id,first_name')
            ->get();

        // Recent tickets for the activity feed
        $recentTickets = DeploymentTicket::with(['project', 'developer', 'reviewer', 'qaTester'])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Tickets needing the current user's attention (if role columns exist on user)
        $myTickets = collect();
        if ($request->user()) {
            $userId = $request->user()->id;
            $myTickets = DeploymentTicket::where(function ($q) use ($userId) {
                $q->where('assigned_developer_id', $userId)
                    ->orWhere('reviewer_id', $userId)
                    ->orWhere('qa_tester_id', $userId);
            })
            ->whereNotIn('status', [DeploymentTicket::STATUS_DEPLOYED, DeploymentTicket::STATUS_ROLLED_BACK])
            ->orderByDesc('id')
            ->limit(10)
            ->get(); 
        }

        return view('deployment.dashboard', compact('stats', 'deploymentsPerDeveloper', 'recentTickets', 'myTickets'));
    }
}
