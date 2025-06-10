<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Projects;
use App\Models\Client;
use App\Models\Sprint;
use Illuminate\Support\Facades\Auth;

class TicketLogController extends Controller
{
    public function index(Request $request)
    {
        $projectFilter = $request->input('project_filter');
        $user = Auth::user();
        $clientId = $user->client_id;

        if (!is_null($clientId)) {
            $projects = Projects::where('client_id', $clientId)->get();
            $clients = Client::where('id', $clientId)
                ->where('status', 1)
                ->orderBy('name', 'asc')
                ->get();
            $projectIds = $projects->pluck('id')->toArray();
        } else {
            $projects = Projects::orderBy('project_name', 'asc')->get();
            $clients = Client::where('status', 1)->orderBy('name', 'asc')->get();
            $projectIds = $projects->pluck('id')->toArray();
        }

        $statuses = [
            'active' => 1,
            'inactive' => 0,
            'completed' => 2,
            'invoice_done' => 3,
        ];

        $sprintData = [];

        foreach ($statuses as $label => $status) {
            $sprintData[$label] = Sprint::with('projectDetails')
                ->withCount([
                    'tickets',
                    'tickets as completed_tickets_count' => fn($q) => $q->where('status', 'complete'),
                    'tickets as todo_tickets_count' => fn($q) => $q->where('status', 'to_do'),
                    'tickets as in_progress_tickets_count' => fn($q) => $q->where('status', 'in_progress'),
                    'tickets as deployed_tickets_count' => fn($q) => $q->where('status', 'deployed'),
                    'tickets as ready_tickets_count' => fn($q) => $q->where('status', 'ready'),
                ])
                ->where('sprints.status', $status)
                ->when($clientId, fn($q) => $q->whereIn('project', $projectIds))
                ->when($projectFilter, fn($q) => $q->where('project', $projectFilter))
                ->get();
        }

        $role_id = $user->role_id;

        return view('ticket-logs.index', compact(
            'projects',
            'clients',
            'sprintData',
            'role_id',
            'user'
        ));
    }
}
