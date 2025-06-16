<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Projects;
use App\Models\Tickets;
use App\Models\Client;
use App\Models\Sprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $tickets = Tickets::with(['sprintDetails.projectDetails','estimationApproval'])
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
            $ticket->is_estimation_approved = $ticket->estimationApproval ? true : false;

            $categories[$category][] = $ticket;
        }

        $ticketData[$status] = $categories;
    }
            // ✅ Need Approval: time_estimation is not null AND not approved yet
        $needApprovalCategories = [
            'Technical' => [],
            'Design' => [],
            'Data Entry' => [],
            'Others' => [],
        ];

        $needApprovalTickets = Tickets::with(['sprintDetails.projectDetails','estimationApproval'])
            ->whereNotNull('time_estimation')
            ->whereDoesntHave('estimationApproval') // Assuming relationship is defined
            ->when($clientId, function ($query) use ($projectIds) {
                $query->whereIn('project_id', $projectIds);
            })
            ->when($projectFilter, function ($query) use ($projectFilter) {
                $query->where('project_id', $projectFilter);
            })
            ->get();

        foreach ($needApprovalTickets as $ticket) {
            $cat = ucwords(strtolower(trim($ticket->ticket_category)));
            $category = in_array($cat, ['Technical', 'Design', 'Data Entry']) ? $cat : 'Others';

            $ticket->sprint_name = $ticket->sprintDetails->name ?? 'No Sprint';
            $ticket->project_name = $ticket->sprintDetails->projectDetails->project_name ?? 'No Project';
            $ticket->is_estimation_approved = $ticket->estimationApproval ? true : false;

            $needApprovalCategories[$category][] = $ticket;
        }

        $ticketData['need_approval'] = $needApprovalCategories;

        // ✅ Approved but Not Started Yet: time_estimation is set, approval exists, but status is still 'to_do'
        $approvedNotStartedCategories = [
            'Technical' => [],
            'Design' => [],
            'Data Entry' => [],
            'Others' => [],
        ];

        $approvedNotStartedTickets = Tickets::with(['sprintDetails.projectDetails','estimationApproval'])
            ->whereNotNull('time_estimation')
            ->whereHas('estimationApproval') // Assuming relationship is defined
            ->where('status', 'to_do')
            ->when($clientId, function ($query) use ($projectIds) {
                $query->whereIn('project_id', $projectIds);
            })
            ->when($projectFilter, function ($query) use ($projectFilter) {
                $query->where('project_id', $projectFilter);
            })
            ->get();

        foreach ($approvedNotStartedTickets as $ticket) {
            $cat = ucwords(strtolower(trim($ticket->ticket_category)));
            $category = in_array($cat, ['Technical', 'Design', 'Data Entry']) ? $cat : 'Others';

            $ticket->sprint_name = $ticket->sprintDetails->name ?? 'No Sprint';
            $ticket->project_name = $ticket->sprintDetails->projectDetails->project_name ?? 'No Project';
            $ticket->is_estimation_approved = $ticket->estimationApproval ? true : false;

            $approvedNotStartedCategories[$category][] = $ticket;
        }

        $ticketData['approved_not_started'] = $approvedNotStartedCategories;

    $role_id = $user->role_id;

    return view('ticket-logs.index', compact(
        'projects',
        'ticketData',
        'role_id',
        'user'
    ));
}

public function pendingApprovals()
{
    $user = Auth::user();

    $projectIds = Projects::where('client_id', $user->client_id)->pluck('id')->toArray();

    $tickets = Tickets::with(['sprintDetails', 'project'])
        ->whereIn('project_id', $projectIds)
        ->whereNotNull('time_estimation') 
        ->whereDoesntHave('estimationApproval') 
        ->get();
    $ticketsCount = $tickets->count();
    return view('developer.pending-approvals', compact('tickets','ticketsCount'));
}

}
