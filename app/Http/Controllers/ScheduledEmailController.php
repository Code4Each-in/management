<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Projects;
use App\Models\ScheduledEmail;
use App\Models\ScheduledEmailRecipient;
use Illuminate\Http\Request;

class ScheduledEmailController extends Controller
{
    public function index()
    {
        $emails = ScheduledEmail::with('template', 'project', 'recipients')
            ->latest()->get();
        return view('scheduled_emails.index', compact('emails'));
    }

    public function create()
    {
        $templates = EmailTemplate::all();
        $clients   = Client::all();
        $projects  = Projects::all();  // NEW
        return view('scheduled_emails.create', compact('templates', 'clients', 'projects'));
    }

    public function store(Request $request)
    {
    $request->merge([
        'send_at' => $request->send_date . ' ' . $request->send_time
    ]);

    $request->validate([
        'template_id'  => 'required|exists:email_templates,id',
        'client_ids'   => 'required|array|min:1',
        'client_ids.*' => 'exists:clients,id',
        'project_id'   => 'nullable|exists:projects,id',
        'send_at'      => 'required|date|after:now',
    ]);
        $email = ScheduledEmail::create([
            'template_id' => $request->template_id,
            'project_id'  => $request->project_id,   // NEW
            'send_at'     => $request->send_at,
            'status'      => 'scheduled',
        ]);

        foreach ($request->client_ids as $clientId) {
            ScheduledEmailRecipient::create([
                'scheduled_email_id' => $email->id,
                'client_id'          => $clientId,
                'status'             => 'pending',
            ]);
        }

        return redirect()->route('scheduled.index')->with('success', 'Email scheduled!');
    }

    // NEW — cancel a scheduled email
    public function destroy($id)
    {
        $email = ScheduledEmail::findOrFail($id);

        if ($email->status === 'scheduled') {
            $email->update(['status' => 'cancelled']);  // make sure spelling is exact
        }

        return back()->with('success', 'Scheduled email cancelled.');
    }

    // NEW — tracking page
    public function tracking()
    {
        $query = ScheduledEmailRecipient::with([
            'client',
            'scheduledEmail.template',
            'scheduledEmail.project',
        ])->latest();

        if (request('status')) {
            $query->where('status', request('status'));
        }

        $recipients = $query->paginate(20);

        $stats = [
            'total'  => ScheduledEmailRecipient::count(),
            'sent'   => ScheduledEmailRecipient::where('status', 'sent')->count(),
            'failed' => ScheduledEmailRecipient::where('status', 'failed')->count(),
        ];

        return view('scheduled_emails.tracking', compact('recipients', 'stats'));
    }
}
