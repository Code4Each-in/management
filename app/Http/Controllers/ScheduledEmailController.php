<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Projects;
use App\Models\ScheduledEmail;
use App\Models\ScheduledEmailRecipient;
use Illuminate\Http\Request;
Use Illuminate\Support\Carbon;

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
      $request->validate([
        'template_id'  => 'required|exists:email_templates,id',
        'client_ids'   => 'required|array|min:1',
        'client_ids.*' => 'exists:clients,id',
        'project_id'   => 'nullable|exists:projects,id',

        'send_date' => 'required|date|after_or_equal:today',
        'send_time' => 'required',
    ]);

    // ✅ Step 2: Combine date + time safely
    $sendAt = Carbon::parse($request->send_date . ' ' . $request->send_time);

    // ✅ Step 3: Validate future datetime
    if ($sendAt->isPast()) {
        return back()->withErrors([
            'send_time' => 'The selected time must be in the future.'
        ])->withInput();
    }

    // ✅ Step 4: Save (example)
    $request->merge([
        'send_at' => $sendAt
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
