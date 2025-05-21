<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tickets;
use App\Models\TicketComments;
use App\Models\Sprint;
use App\Models\Message;
use App\Models\Client;
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

        $projectIds = null;
        // dd($user->id);
        if ($user->role_id == 6) {
            $client = Client::where('id', $user->client_id)->first(); 
            if ($client) {
                $projectIds = Projects::where('client_id', $client->id)->pluck('id')->toArray();
            } else {
                $projectIds = []; 
            }
        }

        if ($searchTerm && is_array($searchPages)) {
            // Search tickets
            if (in_array('ticket', $searchPages)) {
                $ticketsQuery = Tickets::where(function ($query) use ($searchTerm) {
                        $query->where('title', 'like', "%{$searchTerm}%")
                              ->orWhere('description', 'like', "%{$searchTerm}%")
                              ->orWhere('id', 'like', "%{$searchTerm}%");
                    })
                    ->whereDate('created_at', '>=', $dateThreshold);

                if ($user->role_id == 6 && !empty($projectIds)) {
                    $ticketsQuery->whereIn('project_id', $projectIds);
                }

                $tickets = $ticketsQuery->get();
                $results['ticket'] = $tickets;

                 // Ticket comments → ticket ids
                $ticketIdsFromComments = TicketComments::where('comments', 'like', "%{$searchTerm}%")
                    ->pluck('ticket_id')
                    ->unique()
                    ->toArray();

                if (!empty($ticketIdsFromComments)) {
                    $ticketsFromCommentsQuery = Tickets::whereIn('id', $ticketIdsFromComments)
                        ->whereDate('created_at', '>=', $dateThreshold);

                    if ($user->role_id == 6 && !empty($projectIds)) {
                        $ticketsFromCommentsQuery->whereIn('project_id', $projectIds);
                    }

                    $ticketsFromComments = $ticketsFromCommentsQuery->get();

                    if (isset($results['ticket'])) {
                        $results['ticket'] = $results['ticket']->merge($ticketsFromComments)->unique('id');
                    } else {
                        $results['ticket'] = $ticketsFromComments;
                    }
                }
            }

            // Search sprints
            if (in_array('sprint', $searchPages)) {
                $sprintQuery = Sprint::where(function ($query) use ($searchTerm) {
                        $query->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('description', 'like', "%{$searchTerm}%");
                    })
                    ->whereDate('created_at', '>=', $dateThreshold);

                // Filter by project_id if role is 6
                if ($user->role_id == 6 && !empty($projectIds)) {
                    $sprintQuery->whereIn('project', $projectIds);
                }

                $sprints = $sprintQuery->get();
                $results['sprint'] = $sprints->unique('id');
            }

            // Search messages
            if (in_array('message', $searchPages)) {
                $messageQuery = Message::where('message', 'like', "%{$searchTerm}%")
                    ->whereDate('created_at', '>=', $dateThreshold);

                // Filter by project_id if role is 6
                if ($user->role_id == 6 && !empty($projectIds)) {
                    $messageQuery->whereIn('project_id', $projectIds);
                }

                $messages = $messageQuery->get()
                    ->unique('project_id')
                    ->values();

                $results['message'] = $messages;
            }

        }

        if ($request->ajax()) {
            return view('search.results', compact('results'))->render();
        }

        return view('search.index', compact('results'));
    }
}
