<?php

namespace App\Http\Controllers;

use App\Models\TicketFeedback;
use App\Models\Tickets;
use App\Models\Users;
use App\Models\TicketFeedbackEmailSent;
use App\Models\Projects;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\FeedbackReceivedMail;
use App\Notifications\EmailNotification;
use Carbon\Carbon;

class FeedbackController extends Controller
{
    // Show form
    public function showForm($encodedId)
    {
         try {
            $ticketId = decrypt(urldecode($encodedId));
        } catch (\Exception $e) {
            abort(403, 'Invalid or tampered feedback link.');
        }
        $ticket = Tickets::with(['ticketAssigns', 'project'])->findOrFail($ticketId);
        $clientName = null;
        if ($ticket->project) {
            $clientId = optional($ticket->project->clients)->first()?->id
                        ?? $ticket->project->client_id
                        ?? null;

            if ($clientId) {
                $clientUser = \App\Models\Users::where('client_id', $clientId)->first();
                $client     = \App\Models\Client::find($clientId);

                if ($clientUser && trim($clientUser->first_name . ' ' . $clientUser->last_name)) {
                    $clientName = trim($clientUser->first_name . ' ' . $clientUser->last_name);
                } elseif ($client && !empty($client->name)) {
                    $clientName = $client->name;
                }
            }
        }
        return view('feedback.form', compact('ticket', 'encodedId', 'clientName'));
    }

    // Submit feedback
    public function submit(Request $request)
    {
        $request->validate([
            'encoded_ticket_id' => 'required',
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'required',
        ]);
         try {
            $ticketId = decrypt(urldecode($request->encoded_ticket_id));
        } catch (\Exception $e) {
            abort(403, 'Invalid or tampered feedback link.');
        }


        $ticket = Tickets::with('ticketAssigns')->findOrFail($ticketId);

        //  Always redirect to encoded route, never back()
        $redirectRoute = redirect()->route('ticketfeedback.form', $request->encoded_ticket_id);

        // Prevent duplicate submission
        if (TicketFeedback::where('ticket_id', $ticket->id)->exists()) {
            return $redirectRoute->with('error', 'You have already submitted feedback for this ticket.');
        }

        $assignedDevs = $ticket->ticketAssigns->pluck('user_id')->toArray();

        $feedback = TicketFeedback::create([
            'ticket_id' => $ticket->id,
            'feedback_by' => auth()->id() ?? null,
            'assigned_dev_id' => $assignedDevs,
            'rating' => $request->rating,
            'comments' => $request->comments,
        ]);

        // -----------------------------
        // ✅ SEND EMAIL AFTER FEEDBACK
        // -----------------------------
        $mailData = [
            "subject"   => "New Feedback Received for Ticket #{$ticket->id}",
            "title"     => "New feedback submitted for Ticket #{$ticket->id}",
            "body-text" => $request->comments,
            "url-title" => "View Ticket",
            "url"       => "/view/ticket/" . $ticket->id,
            "ticket_id" => $ticket->id,
            "rating"    => $request->rating,  // ✅ for stars in template
        ];

        try {
            // 1. Admin
            $admin = Users::find(1);
            if ($admin && $admin->email) {
                Mail::to($admin->email)->send(new FeedbackReceivedMail($mailData));
            }

            // 2. Assigned devs
            if (!empty($assignedDevs)) {
                foreach (Users::whereIn('id', $assignedDevs)->get() as $dev) {
                    if ($dev->email) {
                        Mail::to($dev->email)->send(new FeedbackReceivedMail($mailData));
                        sleep(1); // avoid mailtrap rate limit
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('Feedback notification error: ' . $e->getMessage());
        }

        // ✅ Redirect with flag
        return redirect()->route('ticketfeedback.form', $request->encoded_ticket_id)
            ->with('success', 'Thanks for your feedback!');
    }

    // Admin listing
    public function index(Request $request)
    {   
        $auth_user = auth()->user();

        $query = TicketFeedback::with(['ticket.project']);

        // -----------------------------
        // FILTERS
        // -----------------------------

        // Project filter
        if ($request->filled('project_filter')) {
            $query->whereHas('ticket', function ($q) use ($request) {
                $q->where('project_id', $request->project_filter);
            });
        }

        // Assigned Dev filter (JSON)
        if ($request->filled('assigned_to_filter')) {
            $userId = (int)$request->assigned_to_filter;

            $query->where(function ($q) use ($userId) {
                $q->whereJsonContains('assigned_dev_id', $userId)
                ->orWhere('feedback_by', $userId);
            });
        }

        $feedbacks = $query->latest()->get();

        // -----------------------------
        // PROJECT DROPDOWN (ONLY USED + ACTIVE)
        // -----------------------------

        $projectsQuery = Projects::where('status', 'active');

        // Only projects that exist in feedbacks
        $projectsQuery->whereIn('id', function ($q) {
            $q->select('project_id')
            ->from('tickets')
            ->whereIn('id', function ($sub) {
                $sub->select('ticket_id')->from('ticket_feedbacks');
            });
        });

        // Client restriction
        if ($auth_user->role_id == 6) {
            $projectsQuery->where(function ($query) use ($auth_user) {
                $query->where('client_id', $auth_user->client_id)
                    ->orWhereHas('clients', function ($q) use ($auth_user) {
                        $q->where('clients.id', $auth_user->client_id);
                    });
            });
        }

        $projects = $projectsQuery->get();

        // -----------------------------
        // USERS DROPDOWN (ONLY USED IN FEEDBACK JSON)
        // -----------------------------

        // Get assigned dev IDs
        $allAssignedIds = TicketFeedback::pluck('assigned_dev_id')
            ->filter()
            ->flatMap(fn($item) => is_array($item) ? $item : [])
            ->toArray();

        // Get submitted by IDs
        $submittedByIds = TicketFeedback::pluck('feedback_by')
            ->filter()
            ->toArray();

        // Merge both
        $allUserIds = array_unique(array_merge($allAssignedIds, $submittedByIds));

        // Fetch users
        $users = Users::whereIn('id', $allUserIds)->get();

        // Map
        $allUsers = $users->pluck('first_name', 'id');

        return view('feedback.index', compact('feedbacks', 'projects', 'users', 'allUsers'));
    }
}