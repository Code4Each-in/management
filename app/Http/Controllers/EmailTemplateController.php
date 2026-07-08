<?php
namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmailTemplateController extends Controller
{
public function index()
{
    $templates = EmailTemplate::latest()
        ->when(request('category'), fn($q) => $q->where('category', request('category')))
        ->get();
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
            $data['banner_image'] = $request->file('banner_image')
                ->store('email-banners', 'public');
        }

        EmailTemplate::create($data);
        return redirect()->route('templates.index')->with('success', 'Template created!');
    }

    public function edit($id)
    {
        $template = EmailTemplate::findOrFail($id);
        return view('email_templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = EmailTemplate::findOrFail($id);

        $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'required|in:festival,business,followup,other',
            'subject'      => 'required|string|max:255',
            'body'         => 'required|string',
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
        return redirect()->route('templates.index')->with('success', 'Template updated!');
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
}
