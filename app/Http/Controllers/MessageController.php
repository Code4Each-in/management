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
use Illuminate\Support\Str;
use App\Notifications\MessageNotification;
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
        $roleid = $user->role_id;
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

        return view('developer.chat', compact('projects', 'client', 'roleid'));
    }



    public function getMessagesByProject(Request $request, $projectId)
    {
        $limit = 15;
        $offset = $request->input('offset', 0); 
    
        $messages = Message::where('project_id', $projectId)
            ->with('user', 'project')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    
        return response()->json(['messages' => $messages]);
    }

public function addMessage(Request $request)
{
    $validator = Validator::make($request->all(), [
        'comment_file.*' => 'file|mimes:jpg,jpeg,png,gif,bmp,svg,pdf,doc,docx,xls,xlsx,csv,txt,rtf,zip,rar,7z,mp3,wav,ogg,mp4,mov,avi,wmv,flv,mkv,webm|max:10240', // 10MB max size
        'comment' => 'nullable|string|max:255',
    ], [
        'comment_file.*.file' => 'The :attribute must be a file.',
        'comment_file.*.mimes' => 'The :attribute must be a file of an allowed type: images, documents, audio, or video.',
        'comment_file.*.max' => 'The :attribute may not be greater than 10MB.',
        'comment.string' => 'The comment must be a valid string.',
        'comment.max' => 'The comment may not be greater than :max characters.',
    ]);


    $validator->setAttributeNames([
        'comment_file.*' => 'document',
    ]);

    if ($validator->fails()) {
        $errors = $validator->errors();
        $allErrors = $errors->all();

        foreach ($allErrors as $error) {
            if (Str::contains($error, 'greater than 10MB')) {
                return response()->json([
                    'errors' => [
                        'One or more files exceed the 10MB limit. If you want to upload files larger than 10MB, please visit: <a href="https://files.code4each.com/" target="_blank">https://files.code4each.com/</a>'
                    ]
                ]);
            }
        }

        return response()->json(['errors' => $allErrors]);
    }

    $user = Auth::user();

    $projectId = $request->input('project_id');
    $project = Projects::find($projectId);
    $projectName = $project->project_name;

    if ($user->role_id == 6) {
        $to_id = 1;
    }else{
        $toUser = Users::where('client_id', $project->client_id)->first();

        if (!$toUser) {
            return response()->json(['errors' => ['User with matching client ID not found.']]);
        }

        $to_id = $toUser->id;
    }


    $documentPaths = [];
    $messageData = [
        'message' => $request->input('message'),
        'project_id' => $request->input('project_id'),
        'to' => $to_id,
        'from' => auth()->id(),
    ];

    if ($request->hasFile('comment_file')) {
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
            // $rawMessage = $request->input('message');
            // $cleanMessage = strip_tags(html_entity_decode($rawMessage));
            $messages["greeting-text"] = "Hello!";
            $messages["subject"] = "New Message received from - {$name}";
            $messages["title"] = "You've received a new message from <strong>{$name}</strong>.";
            $messages["body-text"] = " {$projectName}";
            $messages["url-title"] = "View Message";
            $messages["url"] = "/messages?project_id={$projectId}";

            $assignedUser = Users::find($to_id);
            if ($assignedUser) {
                $assignedUser->notify(new MessageNotification($messages));
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
