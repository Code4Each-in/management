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

        // Non-clients bypass
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

        // Find client from users
        $client = Client::where('email', $user->email)->first();

        if (!$client) {
            return redirect('/dashboard')->with('error', 'Client profile not found.');
        }

        /*
         * OLD / SINGLE CLIENT PROJECT
         *
         * If projects.client_id has a value,
         * use the existing access check.
         */
        if (!is_null($project->client_id)) {

            if ((int) $project->client_id !== (int) $client->id) {
                return redirect('/dashboard')
                    ->with('error', 'You are not authorized to view this ticket.');
            }

        /*
         * NEW / MULTIPLE CLIENT PROJECT
         *
         * If projects.client_id is NULL,
         * check the project_clients pivot table.
         */
        } else {

            $hasAccess = $project->clients()
                ->where('clients.id', $client->id)
                ->exists();

            if (!$hasAccess) {
                return redirect('/dashboard')
                    ->with('error', 'You are not authorized to view this ticket.');
            }
        }

        return $next($request);
    }
}
