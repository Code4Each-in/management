<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Users;
use App\Models\Message;
use App\Models\Projects;
use App\Models\Client;
use App\Notifications\EmailNotification;
class MessageController extends Controller
{
    /**
     * Display login page.
     * 
     * @return Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $client = null;
    
        if ($user->role_id == 1) {
            $projects = Projects::orderBy('id', 'desc')->get();
    
            if ($projects->isNotEmpty()) {
                $client = Client::find($projects->first()->client_id);
            }
        } else {

            $clientId = $user->client_id;
            $projects = Projects::where('client_id', $clientId)
                ->orderBy('id', 'desc')
                ->get();
    
            $client = Client::find($clientId);
        }
        $projects = $projects->unique('project_name')->values();
    
        foreach ($projects as $project) {
            $project->last_message = Message::where('project_id', $project->id)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($user->id == $project->last_message?->from) {
                $project->unread_count = Message::where('project_id', $project->id)
                    ->where('from', $user->id)
                    ->where('is_read_from', 0)
                    ->count();
            } else {
                $project->unread_count = Message::where('project_id', $project->id)
                    ->where('to', $user->id)
                    ->where('is_read_to', 0)
                    ->count();
            }
    
            $project->client = Client::find($project->client_id);
        }

        $projects = $projects->sortByDesc(function ($project) {
            return optional($project->last_message)->created_at;
        })->values();
    
        return view('developer.chat', compact('projects', 'client'));
    }
    

    
    public function getMessagesByProject($projectId)
{

    $messages = Message::where('project_id', $projectId)
        ->with('user', 'project')
        ->orderBy('created_at', 'desc')
        ->get();
        return response()->json(['messages' => $messages]);
}

public function addMessage(Request $request)
{
    $validator = Validator::make($request->all(), [
        'comment_file.*' => 'file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:5000'
    ], [
        'comment_file.*.file' => 'The :attribute must be a file.',
        'comment_file.*.mimes' => 'The :attribute must be a file of type: jpeg, png, pdf.',
        'comment_file.*.max' => 'The :attribute may not be greater than :max kilobytes.',
        'comment_file.*.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',
        'comment.required' => 'The comment field is required.',
        'comment.string' => 'The comment must be a valid string.',
        'comment.max' => 'The comment may not be greater than :max characters.',
    ]);

    $validator->setAttributeNames([
        'comment_file.*' => 'document'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()->all()]);
    }
    $user = Auth::user();

    if ($user->role_id == 6) {
        $to_id = 1;
    }else{
        $projectId = $request->input('project_id');
        $project = Projects::find($projectId);

        $toUser = Users::where('client_id', $project->client_id)->first();

        if (!$toUser) {
            return response()->json(['errors' => ['User with matching client ID not found.']]);
        }
    
        $to_id = $toUser->id;
    }
 

    $documentPaths = [];
    $maxTotalSize = 5 * 1024 * 1024;  
    $totalSize = 0;
    $messageData = [
        'message' => $request->input('message'),
        'project_id' => $request->input('project_id'),
        'to' => $to_id,
        'from' => auth()->id(),
    ];

    if ($request->hasFile('comment_file')) {
        foreach ($request->file('comment_file') as $file) {
            $totalSize += $file->getSize();
        }
        if ($totalSize > $maxTotalSize) {
            return response()->json(['errors' => ['Total upload size should not exceed 5MB.']]);
        }
        foreach ($request->file('comment_file') as $file) {
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $dateString = date('YmdHis');
            $name = $dateString . '_' . $fileName . '.' . $file->extension();
            $file->move(public_path('assets/img/ticketAssets'), $name);
            $path = 'ticketAssets/' . $name;
            $documentPaths[] = $path; 
        }
        $documentString = implode(',', $documentPaths);
        $messageData['document'] = $documentString; 
    }

    $message = Message::create($messageData);
    $message = Message::with('user')->find($message->id);
    $user = auth()->user();
    $name = $user->first_name;

    if ($message) {
        try {
            $messages = [
                "subject" => "New Message  received from - {$name}",
                "title" => "You've received new Message from {$name}.",
                "body-text" => "Message: \"" . $request->input('message') . "\"",
            ];
    
            
            $assignedUser = Users::find($to_id);
            if ($assignedUser) {
                $assignedUser->notify(new EmailNotification($messages));
            }
        } catch (\Exception $e) {
            \Log::error("Error sending notification for feedback: " . $e->getMessage());
        }
    } 
    return response()->json([
        'status' => 200,
        'message' => $message, 
        'Commentmessage' => 'Message added successfully.'
    ]);
}
    public function destroy($id)
    {
        $comment = Message::findOrFail($id);
        $comment->delete();
        return response()->json(['status' => 200, 'message' => 'Comment deleted']);
    }


    public function markAsRead($projectId)
{
    $userId = Auth::id();

    // Mark messages as read
    $fromUpdated = Message::where('project_id', $projectId)
        ->where('from', $userId)
        ->update(['is_read_from' => 1]);

    $toUpdated = Message::where('project_id', $projectId)
        ->where('to', $userId)
        ->update(['is_read_to' => 1]);

    $totalUpdated = $fromUpdated + $toUpdated;

    // Get updated unread count
    $unreadCount = Message::where('project_id', $projectId)
        ->where(function ($query) use ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('from', $userId)->where('is_read_from', 0);
            })->orWhere(function ($q) use ($userId) {
                $q->where('to', $userId)->where('is_read_to', 0);
            });
        })
        ->count();

    if ($totalUpdated > 0) {
        return response()->json([
            'status' => 'success',
            'message' => "$totalUpdated message(s) marked as read",
            'updatedUnreadCount' => $unreadCount
        ]);
    }

    return response()->json([
        'status' => 'no_action',
        'message' => 'No matching unread messages',
        'updatedUnreadCount' => $unreadCount
    ]);
}


    

}