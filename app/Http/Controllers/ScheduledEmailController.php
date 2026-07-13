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
       if($request->send_type == "now"){
            $request->validate([
                'template_id'  => 'required|exists:email_templates,id',
                'subject'      => 'required',
                'client_ids'   => 'required|array|min:1',
                'client_ids.*' => 'exists:clients,id',
                'project_id'   => 'nullable|exists:projects,id',
                'body'         => 'required|string', 
            ]);
            $sendAt = now();

       }else{
            $request->validate([
                'template_id'  => 'required|exists:email_templates,id',
                'subject'      => 'required',
                'client_ids'   => 'required|array|min:1',
                'client_ids.*' => 'exists:clients,id',
                'project_id'   => 'nullable|exists:projects,id',
                'body'         => 'required|string', 
                'send_date'    => 'required|date|after_or_equal:today',
                'send_time'    => 'required',
            ]);

                // ✅ Combine date + time
        $sendAt = Carbon::parse($request->send_date . ' ' . $request->send_time);

        // ✅ Future check
        if ($sendAt->isPast()) {
            return back()->withErrors([
                'send_time' => 'The selected time must be in the future.'
            ])->withInput();
        }
       }


       
        $email = ScheduledEmail::create([
            'template_id' => $request->template_id,
            'subject'     => $request->subject,
            'body'        => $request->body,
            'project_id'  => $request->project_id,   // NEW
            'send_at'     => $sendAt,
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
        // ✅ Get recipient
        $recipient = ScheduledEmailRecipient::findOrFail($id);

        // ✅ Update recipient status
        $recipient->update([
            'status' => 'cancelled'
        ]);

        // ✅ Get parent scheduled_email_id
        $scheduledEmailId = $recipient->scheduled_email_id;

        // ✅ Check if ALL recipients are cancelled
        $hasActiveRecipients = ScheduledEmailRecipient::where('scheduled_email_id', $scheduledEmailId)
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        // ✅ If NO active recipients → update parent
        if (!$hasActiveRecipients) {
            ScheduledEmail::where('id', $scheduledEmailId)
                ->update(['status' => 'cancelled']);
        }

        return redirect()->route('scheduled.index')
            ->with('success', 'Scheduler cancelled successfully');
    }

  
}
