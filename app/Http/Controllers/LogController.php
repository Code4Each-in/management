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

    $query = ProjectLog::with('project');

    // Filter by project if project_id exists
    if ($project_id) {
        $query->where('project_id', $project_id);
    }

    // If client, show only their project logs
    if ($user->role_id == 6) {
        $query->whereHas('project', function($q) use ($user) {
            $q->where('client_id', $user->client_id);
        });
    }

    if (request()->type) {
        $query->where('type', request()->type);
    }

    if (request()->date_filter) {

        if (request()->date_filter == 'today') {
            $query->whereDate('logged_at', Carbon::today());
        }

        if (request()->date_filter == 'yesterday') {
            $query->whereDate('logged_at', Carbon::yesterday());
        }

        if (request()->date_filter == '7days') {
            $query->where('logged_at', '>=', Carbon::now()->subDays(7));
        }

        if (request()->date_filter == '15days') {
            $query->where('logged_at', '>=', Carbon::now()->subDays(15));
        }

        if (request()->date_filter == '30days') {
            $query->where('logged_at', '>=', Carbon::now()->subDays(30));
        }
    }

    $logs = $query->latest('logged_at')->get();

    $projects = Projects::select('id','project_name')
        ->where('status','active')
        ->get();

    $types = ProjectLog::select('type')->distinct()->pluck('type');

    return view('logs.index', compact('logs','projects','types','project_id'));
}
}
