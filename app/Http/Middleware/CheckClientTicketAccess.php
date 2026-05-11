<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use App\Models\Tickets;
use App\Models\Projects;

class CheckClientTicketAccess
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();

        // non-clients bypass
        if ($user->role_id != 6) {
            return $next($request);
        }

        $ticket = Tickets::find($request->route('ticketId'));

        if (!$ticket) {
            return redirect('/dashboard')->with('error', 'Ticket not found.');
        }

        $project = Projects::find($ticket->project_id);

        if (!$project) {
            return redirect('/dashboard')->with('error', 'Project not found.');
        }

        //  FIND CLIENT FROM users
        $client = Client::where('email', $user->email)->first();

        if (!$client) {
            return redirect('/dashboard')->with('error', 'Client profile not found.');
        }

        // FINAL CHECK
        if ((int)$project->client_id !== (int)$client->id) {
            return redirect('/dashboard')
                ->with('error', 'You are not authorized to view this ticket.');
        }

        return $next($request);
    }
}
