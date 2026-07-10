<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Projects;
use App\Models\ScheduledEmail;
use App\Models\ScheduledEmailRecipient;
use App\Models\User;
use Illuminate\Http\Request;
Use Illuminate\Support\Carbon;

class ScheduledEmailController extends Controller
{
    // public function index()
    // {
    //     $emails = ScheduledEmail::with('template', 'project', 'recipients')->latest()->get(); 
    //     return view('scheduled_emails.index', compact('emails'));  
    // }

    public function index()
    {
        $recipients = ScheduledEmailRecipient::with([
            'client',
            'scheduledEmail.template',
            'scheduledEmail.project'
        ])->latest()->get();

        return view('scheduled_emails.index', compact('recipients'));
    }

    public function create()
    {
        $templates = EmailTemplate::all();
        // $clients   = Client::all();
        $clients = Client::where('status', 1)->orderBy('name', 'asc')->get();
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
        'body'         => 'required|string', 
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
            'body'        => $request->body,
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

    public function preview($id)
    {
        
        $email = ScheduledEmail::with(['template', 'recipients.client.allprojects'])->findOrFail($id);
      // dd($email);
        $template = $email->template;

        // pick first client for preview
        $recipient = $email->recipients->first();
        $client = $recipient->client ?? null;

        if (!$client) {
            return "No client found for preview";
        }

        $projectNames = $client->allprojects->pluck('project_name')->implode(', ');

        $placeholders = [
            '{{ client_name }}' => $client->name,
            '{{ company_name }}' => $client->company ?? '',
            '{{ project_name }}' => $projectNames ?: 'N/A',
        ];
       // dd($email->body);

        $body = str_replace(array_keys($placeholders), array_values($placeholders), $email->body);
        //dd($template->body);

        return response($body);
    }

    public function cancel_scheduler(Request $request, $id)
    {
       
       ScheduledEmailRecipient::where('id', $id)->update(["status" => 'cancelled']);
       return redirect()->route('scheduled.index')->with('success', 'Schedular Cancelled Successfully');
       
    }

  
}
