<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
        if (empty($searchPages)) {
            $searchPages = ['ticket', 'message', 'sprint']; // default to all
        }
        $results = [];

        if ($searchTerm && is_array($searchPages)) {
            // Search tickets directly
            if (in_array('ticket', $searchPages)) {
                $tickets = Tickets::where(function ($query) use ($searchTerm) {
                    $query->where('title', 'like', "%{$searchTerm}%")
                        ->orWhere('description', 'like', "%{$searchTerm}%")
                        ->orWhere('id', 'like', "%{$searchTerm}%");
                })->get();

                $results['ticket'] = $tickets;
            }

            // Search ticket comments and get related tickets
            $ticketIdsFromComments = TicketComments::where('comments', 'like', "%{$searchTerm}%")
                ->pluck('ticket_id')
                ->unique()
                ->toArray();
            
            if (!empty($ticketIdsFromComments)) {
                $ticketsFromComments = Tickets::whereIn('id', $ticketIdsFromComments)->get();
                if (isset($results['ticket'])) {
                    $results['ticket'] = $results['ticket']->merge($ticketsFromComments)->unique('id');
                } else {
                    $results['ticket'] = $ticketsFromComments;
                }
            }
        
            // Search sprints (name, description)
            if (in_array('sprint', $searchPages)) {
                $sprints = Sprint::where(function ($query) use ($searchTerm) {
                    $query->where('name', 'like', "%{$searchTerm}%")
                          ->orWhere('description', 'like', "%{$searchTerm}%");
                })->get();

                $results['sprint'] = $sprints;
            }

            // Search messages (project_messages.message)
            if (in_array('message', $searchPages)) {
                $messages = Message::where('message', 'like', "%{$searchTerm}%")->get();

                $results['message'] = $messages;
            }
        }

        if ($request->ajax()) {
            return view('search.results', compact('results'))->render();
        }

        return view('search.index', compact('results'));
    }
}
