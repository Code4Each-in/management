<?php

namespace App\Http\Controllers;

use App\Models\ProjectLog;
use App\Models\Projects;

class LogController extends Controller
{
public function index()
{
    $query = ProjectLog::with('project');

    // Apply filters
    if (request()->project_id) {
        $query->where('project_id', request()->project_id);
    }

    if (request()->type) {
        $query->where('type', request()->type);
    }

    $logs = $query->latest('logged_at')->get();

    // Fetch projects dynamically
    $projects = Projects::select('id','project_name')->where('status', 'active')->get();
    // Fetch distinct log types dynamically
    $types = ProjectLog::select('type')->distinct()->pluck('type');

    return view('logs.index', compact('logs','projects','types'));
}
}
