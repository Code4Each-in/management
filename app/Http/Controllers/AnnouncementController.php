<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
    // FORM PAGE
    public function create()
    {
        return view('announcement.create');
    }

    // LISTING PAGE (same as reminder.indexing)
    public function index()
    {
        $announcements = Announcement::latest()->get();
        return view('announcement.index', compact('announcements'));
    }

    // STORE
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'end_date' => 'nullable|date',
        ], [
            'title.required' => 'Title is required.',
            'description.required' => 'Description is required.',
        ]);

        $data['message'] = $request->description;
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['end_date'] = $request->end_date;
        $data['show_to_client'] = $request->has('show_to_client') ? 1 : 0;

        $announcement = Announcement::create($data);

        if ($announcement) {
            return redirect()->route('announcement.index')->with('success', 'Announcement created successfully!');
        } else {
            return back()->with('error', 'Failed to create announcement.');
        }
    }

    // EDIT
    public function edit($id)
    {
        $announcement = Announcement::findOrFail($id);
        return view('announcement.edit', compact('announcement'));
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'end_date' => 'nullable|date',
        ]);

        $data['message'] = $request->description;
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['end_date'] = $request->end_date;
        $data['show_to_client'] = $request->has('show_to_client') ? 1 : 0;

        $announcement->update($data);

        return redirect()->route('announcement.index')->with('success', 'Announcement updated successfully!');
    }

    // DELETE (same as reminder)
    public function destroy(Announcement $announcement)
    {
        try {
            $announcement->delete();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete: ' . $e->getMessage()
            ]);
        }
    }
}