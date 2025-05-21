<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tickets;
use App\Models\TicketComments;
use App\Models\Sprint;
use App\Models\Message;
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

        if ($searchTerm && is_array($searchPages)) {
            // Search tickets
            if (in_array('ticket', $searchPages)) {
                $tickets = Tickets::where(function ($query) use ($searchTerm) {
                        $query->where('title', 'like', "%{$searchTerm}%")
                              ->orWhere('description', 'like', "%{$searchTerm}%")
                              ->orWhere('id', 'like', "%{$searchTerm}%");
                    })
                    ->whereDate('created_at', '>=', $dateThreshold)
                    ->get();

                $results['ticket'] = $tickets;

                 // Ticket comments → ticket ids
                $ticketIdsFromComments = TicketComments::where('comments', 'like', "%{$searchTerm}%")
                    ->pluck('ticket_id')
                    ->unique()
                    ->toArray();

                if (!empty($ticketIdsFromComments)) {
                    $ticketsFromComments = Tickets::whereIn('id', $ticketIdsFromComments)
                        ->whereDate('created_at', '>=', $dateThreshold)
                        ->get();

                    if (isset($results['ticket'])) {
                        $results['ticket'] = $results['ticket']->merge($ticketsFromComments)->unique('id');
                    } else {
                        $results['ticket'] = $ticketsFromComments;
                    }
                }
            }

            // Search sprints
            if (in_array('sprint', $searchPages)) {
                $sprints = Sprint::where(function ($query) use ($searchTerm) {
                        $query->where('name', 'like', "%{$searchTerm}%")
                              ->orWhere('description', 'like', "%{$searchTerm}%");
                    })
                    ->whereDate('created_at', '>=', $dateThreshold)
                    ->get();

                $results['sprint'] = $sprints->unique('id');
            }

            // Search messages
            if (in_array('message', $searchPages)) {
                $messages = Message::where('message', 'like', "%{$searchTerm}%")
                    ->whereDate('created_at', '>=', $dateThreshold)
                    ->get();

                $results['message'] = $messages->unique('id');
            }
        }

        if ($request->ajax()) {
            return view('search.results', compact('results'))->render();
        }

        return view('search.index', compact('results'));
    }
}
