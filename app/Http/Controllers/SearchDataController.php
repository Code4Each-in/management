<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tickets;
use App\Models\TicketComments;
use App\Models\Sprint;
use App\Models\Message;
use App\Models\Projects;
use Illuminate\Support\Facades\Validator;

class SearchDataController extends Controller
{
    public function searchList(Request $request)
{
    $user = auth()->user();

    if ($request->ajax()) {
        $validator = Validator::make($request->all(), [
            'searchTerm' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    }

    $searchTerm = $request->input('searchTerm');
    $searchPages = $request->input('searchPage', []);
    $results = [];

    // Default to all if no pages selected
    if (empty($searchPages)) {
        $searchPages = ['ticket', 'message', 'sprint'];
    }

    $dateThreshold = '2025-01-01';

    // If role_id is 6 (client), filter project_ids
    $clientProjectIds = [];
    if ($user->role_id == 6) {
        $clientProjectIds = Projects::where('client_id', $user->id)->pluck('id')->toArray();
    }

    if ($searchTerm && is_array($searchPages)) {
        // Search tickets
        if (in_array('ticket', $searchPages)) {
            $ticketQuery = Tickets::where(function ($query) use ($searchTerm) {
                    $query->where('title', 'like', "%{$searchTerm}%")
                          ->orWhere('description', 'like', "%{$searchTerm}%")
                          ->orWhere('id', 'like', "%{$searchTerm}%");
                })
                ->whereDate('created_at', '>=', $dateThreshold);

            if ($user->role_id == 6) {
                $ticketQuery->whereIn('project_id', $clientProjectIds);
            }

            $tickets = $ticketQuery->get();
            $results['ticket'] = $tickets;

            // Ticket comments → ticket ids
            $commentedTicketIds = TicketComments::where('comments', 'like', "%{$searchTerm}%")
                ->pluck('ticket_id')
                ->unique()
                ->toArray();

            if (!empty($commentedTicketIds)) {
                $commentedTicketsQuery = Tickets::whereIn('id', $commentedTicketIds)
                    ->whereDate('created_at', '>=', $dateThreshold);

                if ($user->role_id == 6) {
                    $commentedTicketsQuery->whereIn('project_id', $clientProjectIds);
                }

                $commentedTickets = $commentedTicketsQuery->get();

                $results['ticket'] = isset($results['ticket'])
                    ? $results['ticket']->merge($commentedTickets)->unique('id')
                    : $commentedTickets;
            }
        }

        // Search sprints
        if (in_array('sprint', $searchPages)) {
            $sprintQuery = Sprint::where(function ($query) use ($searchTerm) {
                    $query->where('name', 'like', "%{$searchTerm}%")
                          ->orWhere('description', 'like', "%{$searchTerm}%");
                })
                ->whereDate('created_at', '>=', $dateThreshold);

            if ($user->role_id == 6) {
                $sprintQuery->whereIn('project', $clientProjectIds);
            }

            $results['sprint'] = $sprintQuery->get()->unique('id');
        }

        // Search messages
        if (in_array('message', $searchPages)) {
            $messageQuery = Message::where('message', 'like', "%{$searchTerm}%")
                ->whereDate('created_at', '>=', $dateThreshold);

            if ($user->role_id == 6) {
                $messageQuery->whereIn('project_id', $clientProjectIds);
            }

            $results['message'] = $messageQuery->get()->unique('id');
        }
    }

    if ($request->ajax()) {
        return view('search.results', compact('results'))->render();
    }

    return view('search.index', compact('results'));
}

}
