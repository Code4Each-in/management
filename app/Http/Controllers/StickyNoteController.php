<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\StickyNote;
use Carbon\Carbon;


class StickyNoteController extends Controller
{
    public function getNotes(Request $request)
    {
        $userId = Auth::id();

        $notes = StickyNote::join('users', 'sticky_notes.userid', '=', 'users.id')
            ->where('sticky_notes.userid', $userId)
            ->select('sticky_notes.*', 'users.first_name', 'users.last_name') 
            ->get();

        return response()->json($notes);
    }


    public function createNote(Request $request)
    {
        $userId = Auth::id();
        $note = StickyNote::create([
            'recordcreated' => Carbon::now(),
            'userid' => $userId,
            'title' => null,
            'notes' => null,
        ]);
    
        // Load the user relation (first_name and last_name)
        $note->load('user:id,first_name,last_name');
    
        return response()->json([
            'note' => [
                'id' => $note->id,
                'recordcreated' => $note->recordcreated,
                'userid' => $note->userid,
                'title' => $note->title,
                'notes' => $note->notes,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
                'first_name' => $note->user->first_name ?? '',
                'last_name' => $note->user->last_name ?? '',
            ]
        ]);
    }

    public function updateNote(Request $request, $id)
    {
        $note = StickyNote::where('id', $id)->first();

        if ($note && $note->userid == Auth::id()) {
            $note->title = $request->input('title');
            $note->notes = $request->input('notes');
            $note->save();
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error'], 403);
    }

    public function deleteNote(Request $request)
    {
        $note = StickyNote::find($request->id);
        if (!$note) {
            return response()->json(['success' => false, 'message' => 'Note not found.']);
        }

        $note->delete();

        return response()->json(['success' => true]);
    }

}
