<?php

namespace App\Http\Controllers;

use App\Models\ProjectLog;
use App\Models\Projects;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LogController extends Controller
{
    public function index($project_id = null)
    {
        $user = Auth::user();

        if ($project_id && $user->role_id == 6) {

            $hasAccess = Projects::where('id', $project_id)
                ->where(function ($q) use ($user) {

                    // Direct ownership
                    $q->where('client_id', $user->client_id)

                    // OR via project_clients table
                    ->orWhereHas('clients', function ($q2) use ($user) {
                        $q2->where('client_id', $user->client_id);
                    });
                })
                ->exists();

            if (!$hasAccess) {
                return redirect()->route('dashboard.index')
                    ->with('error', 'You do not have access to this project logs.');
            }
        }
        $query = ProjectLog::with('project');

        if ($project_id) {
            $query->where('project_id', $project_id);
        }

        if ($user->role_id == 6) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('client_id', $user->client_id);
            });
        }

        if (request()->type) {
            $query->where('type', request()->type);
        }

        if (request()->date_filter) {
            $query = $this->applyDateFilter($query, request()->date_filter);
        }

        $logs = $query->latest('logged_at')->get();

        $countQuery = ProjectLog::query();

        if ($project_id) {
            $countQuery->where('project_id', $project_id);
        }

        if ($user->role_id == 6) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('client_id', $user->client_id)
                ->orWhereHas('clients', function ($q2) use ($user) {
                    $q2->where('client_id', $user->client_id);
                });
            });
        }

        if (request()->date_filter) {
            $countQuery = $this->applyDateFilter($countQuery, request()->date_filter);
        }

        $typeCounts = $countQuery
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        // ── Supporting data ──────────────────────────────────────────────
        $projects = Projects::select('id', 'project_name')
            ->where('status', 'active')
            ->get();

        $types = ProjectLog::select('type')->distinct()->pluck('type');

        return view('logs.index', compact('logs', 'projects', 'types', 'project_id', 'typeCounts'));
    }

    private function applyDateFilter($query, $filter)
    {
        switch ($filter) {
            case 'today':
                $query->whereDate('logged_at', Carbon::today());
                break;
            case 'yesterday':
                $query->whereDate('logged_at', Carbon::yesterday());
                break;
            case '7days':
                $query->where('logged_at', '>=', Carbon::now()->subDays(7));
                break;
            case '15days':
                $query->where('logged_at', '>=', Carbon::now()->subDays(15));
                break;
            case '30days':
                $query->where('logged_at', '>=', Carbon::now()->subDays(30));
                break;
        }

        return $query;
    }
}
