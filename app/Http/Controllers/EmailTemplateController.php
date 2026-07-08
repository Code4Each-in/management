<?php
namespace App\Http\Controllers;
use Illuminate\Notifications\Notifiable;
use App\Models\Client;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Notifications\EmailTemplateNotification;
use Illuminate\Support\Facades\Log;

class EmailTemplateController extends Controller 
{
public function index()
{
    $templates = EmailTemplate::latest()->when(request('category'), fn($q) => $q->where('category', request('category')))->get();
    return view('email_templates.index', compact('templates'));
}

    public function create()
    {
        return view('email_templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'required|in:festival,business,followup,other',
            'subject'      => 'required|string|max:255',
            'body'         => 'required|string',
            'banner_image' => 'nullable|image|max:5120', // max 5MB
        ]);

        $data = $request->only(['name', 'category', 'subject', 'body']);

        // Handle banner image upload
        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('email-banners', 'public');
        }

        EmailTemplate::create($data);
        return redirect()->route('templates.index')->with('success', 'Template created!');
    }

    public function edit($id)
    { 
        $template = EmailTemplate::findOrFail($id);
      //  dd($template);
        return view('email_templates.edit', compact('template'));
    }



    public function update(Request $request, $id)
    {
        
        $template = EmailTemplate::findOrFail($id);

        $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'required|in:festival,business,followup,other',
            'subject'      => 'required|string|max:255',
            'body'         => 'required',
            'banner_image' => 'nullable|image|max:5120',
        ]);

        $data = $request->only(['name', 'category', 'subject', 'body']);

        // Replace old image only if a new one is uploaded
        if ($request->hasFile('banner_image')) {
            // Delete old image if exists
            if ($template->banner_image) {
                Storage::disk('public')->delete($template->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')
                ->store('email-banners', 'public');
        }

        $template->update($data);
        return redirect()->route('templates.index')->with('success', 'Template updated Successfully');
    }

    public function destroy($id)
    {
        $template = EmailTemplate::findOrFail($id);

        // Delete banner image file if exists
        if ($template->banner_image) {
            Storage::disk('public')->delete($template->banner_image);
        }

        $template->delete();
        return back()->with('success', 'Template deleted!');
    }

    public function mailtoclient()
    {
        $templates = EmailTemplate::all();
         return view('email_templates.send_template', [
                     'clients'   => Client::select('id', 'name', 'email')->get(),
                      'templates' => EmailTemplate::all(),
       ]);
    }


    // public function send(Request $request)
    // {
    //     $request->validate([
    //         'client_ids' => 'required|array|min:1',
    //         'template_id' => 'required|exists:email_templates,id',
    //     ]);

    //     $template = EmailTemplate::findOrFail($request->template_id);

    //     $clients = Client::with('allprojects')->whereIn('id', $request->client_ids)->get();
     
    //     foreach ($clients as $client) {
    //         $projectNames = $client->allprojects
    //             ->pluck('project_name')  
    //             ->implode(', ');

    //     $body = str_replace(
    //                 ['{{client_name}}', '{{company_name}}', '{{project_name}}', '{{banner_image}}'],
    //                 [
    //                     $client->name,
    //                     $client->company ?? '',
    //                     $projectNames ?: 'N/A',
    //                     '<img src="'.asset('storage/'.$template->banner_image).'" style="max-width:100%; height:auto;" />'
    //                 ],
    //                 $template->body
    //             );

    //         $message = [
    //                 'client_name' => $client->name,
    //                 'subject'     => $template->subject,
    //                 'body'        => $body,
    //                 'banner_img'  =>  $template->banner_image,
    //             ];

    //          //  dd($message);
    //          $client = Client::where('email', 'sandhu065@gmail.com')->first();
    //           try {
    //             $client->notify(new EmailTemplateNotification($message));

    //             } catch (\Exception $e) {
    //                 // Log the error with client info
    //                 Log::error('Email sending failed', [
    //                     'client_id' => $client->id,
    //                     'email'     => $client->email,
    //                     'error'     => $e->getMessage(),
    //                 ]);
    //             }
    //     }
   

    //     return back()->with('success', 'Mail sent to ' . count($clients) . ' client(s).');
    // }

    public function send(Request $request)
    {
        $request->validate([
            'client_ids'  => 'required|array|min:1',
            'template_id' => 'required|exists:email_templates,id', 
        ]);

        $template = EmailTemplate::findOrFail($request->template_id);

        $clients = Client::with('allprojects')
            ->whereIn('id', $request->client_ids)
            ->get();

        foreach ($clients as $client) {

            // Get project names
            $projectNames = $client->allprojects
                ->pluck('project_name')
                ->implode(', ');

            // Prepare placeholders
        // dd($template->banner_image);
            $placeholders = [
                '{{ client_name }}'   => $client->name,
                '{{ company_name }}'  => $client->company ?? '',
                '{{ project_name }}'  => $projectNames ?: 'N/A',
               
            ];

            // Replace placeholders
            $body = str_replace(
                array_keys($placeholders),
                array_values($placeholders),
                $template->body
            );
        // dd($body);

            // Prepare message
            $message = [
                'client_name' => $client->name,
                'subject'     => $template->subject,
                'content'     => $body,
                'banner_img'  => $template->banner_image, 
            ];

            try {
                $client = Client::where('email', 'sandhu065@gmail.com')->first();
                $client->notify(new EmailTemplateNotification($message));
            } catch (\Exception $e) {
                Log::error('Email sending failed', [
                    'client_id' => $client->id,
                    'email'     => $client->email,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Mail sent to ' . count($clients) . ' client(s).');
    }
}