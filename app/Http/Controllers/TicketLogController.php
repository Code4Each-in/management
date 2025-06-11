<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Projects;
use App\Models\Tickets;
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

    $ticketStatuses = ['in_progress', 'to_do', 'invoice_done'];
    $ticketData = [];

    foreach ($ticketStatuses as $status) {
        $categories = [
            'Technical' => [],
            'Design' => [],
            'Data Entry' => [],
            'Others' => [],
        ];

        $tickets = Tickets::with(['sprintDetails.projectDetails'])
            ->where('status', $status)
            ->when($clientId, function ($query) use ($projectIds) {
                $query->whereIn('project_id', $projectIds);
            })
            ->when($projectFilter, function ($query) use ($projectFilter) {
                $query->where('project_id', $projectFilter);
            })
            ->get();

        foreach ($tickets as $ticket) {
            $cat = ucwords(strtolower(trim($ticket->ticket_category)));
            $category = in_array($cat, ['Technical', 'Design', 'Data Entry']) ? $cat : 'Others';

            // ✅ Add Sprint and Project Name
            $ticket->sprint_name = $ticket->sprintDetails->name ?? 'No Sprint';
            $ticket->project_name = $ticket->sprintDetails->projectDetails->project_name ?? 'No Project';

            $categories[$category][] = $ticket;
        }

        $ticketData[$status] = $categories;
    }

    $role_id = $user->role_id;

    return view('ticket-logs.index', compact(
        'projects',
        'ticketData',
        'role_id',
        'user'
    ));
}
}
