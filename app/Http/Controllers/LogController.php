<?php

namespace App\Http\Controllers;

use App\Models\ProjectLog;
use App\Models\Projects;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LogController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        $query = ProjectLog::with('project');

        // If client, show only their project logs
        if ($user->role_id == 6) {
            $query->whereHas('project', function($q) use ($user) {
                $q->where('client_id', $user->client_id);
            });
        }

        // Apply filters
        if (request()->project_id) {
            $query->where('project_id', request()->project_id);
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
        }
        $logs = $query->latest('logged_at')->get();

        // Fetch projects list based on user role
        if ($user->role_id == 6) {

            $projects = Projects::select('id','project_name')
                ->where('client_id', $user->client_id)
                ->where('status','active')
                ->get();

        } else {

            $projects = Projects::select('id','project_name')
                ->where('status','active')
                ->get();
        }

        // Fetch log types
        $types = ProjectLog::select('type')->distinct()->pluck('type');

        return view('logs.index', compact('logs','projects','types'));
    }

}
