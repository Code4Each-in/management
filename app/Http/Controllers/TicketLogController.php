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
        $projectIds = $projects->pluck('id')->toArray();
    } else {
        $projects = Projects::orderBy('project_name')->get();
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
    $sprints = Sprint::with(['tickets', 'projectDetails'])
        ->where('sprints.status', $status)
        ->when($clientId, fn($q) => $q->whereIn('project', $projectIds))
        ->when($projectFilter, fn($q) => $q->where('project', $projectFilter))
        ->get();

            $categories = [
            'Technical' => [],
            'Design' => [],
            'Data Entry' => [],
            'Others' => [],
        ];

        foreach ($sprints as $sprint) {
            foreach ($sprint->tickets as $ticket) {
                $cat = ucwords(strtolower(trim($ticket->ticket_category)));
                $category = in_array($cat, ['Technical', 'Design', 'Data Entry']) ? $cat : 'Others';

                $ticket->sprint_name = $sprint->name;
                $ticket->project_name = $sprint->projectDetails->project_name ?? 'No Project';

                $categories[$category][] = $ticket;
            }
        }

     
    $sprintData[$label] = $categories; // store categories directly under status
}
    
    $role_id = $user->role_id;

    return view('ticket-logs.index', compact(
        'projects',
        'sprintData',
        'role_id',
        'user'
    ));
}
}
