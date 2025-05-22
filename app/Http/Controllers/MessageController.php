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
        'comment_file.*' => 'file|mimes:jpg,jpeg,png,gif,bmp,svg,pdf,doc,docx,xls,xlsx,csv,txt,rtf,zip,rar,7z,mp3,wav,ogg,mp4,mov,avi,wmv,flv,mkv,webm|max:10240',
        'message' => 'nullable|string',
    ], [
        'comment_file.*.file' => 'Each file must be a valid file.',
        'comment_file.*.mimes' => 'Each file must be of an allowed type.',
        'comment_file.*.max' => 'Each file must not be larger than 10MB.',
        'message.string' => 'The message must be a valid string.'
    ]);

    $validator->setAttributeNames([
        'comment_file.*' => 'document',
    ]);

    if ($validator->fails()) {
        $errors = $validator->errors()->all();

        // Custom 10MB limit message
        foreach ($errors as $error) {
            if (Str::contains($error, '10MB')) {
                return response()->json([
                    'errors' => [
                        'One or more files exceed the 10MB limit. If you want to upload files larger than 10MB, please visit: <a href="https://files.code4each.com/" target="_blank">https://files.code4each.com/</a>'
                    ]
                ]);
            }
        }

        return response()->json(['errors' => $errors]);
    }

    // Ensure at least a message or file is submitted
    if (empty($request->message) && !$request->hasFile('comment_file')) {
        return response()->json([
            'errors' => ['Kindly type a message or attach a file before submitting.']
        ]);
    }

    $user = auth()->user();
    $projectId = $request->input('project_id');
    $project = Projects::find($projectId);

    if (!$project) {
        return response()->json(['errors' => ['Project not found.']]);
    }

    $to_id = $user->role_id == 6
        ? 1
        : Users::where('client_id', $project->client_id)->value('id');

    if (!$to_id) {
        return response()->json(['errors' => ['Recipient user not found for this project.']]);
    }

    // Handle file uploads
    $documentPaths = [];

    if ($request->hasFile('comment_file')) {
        foreach ($request->file('comment_file') as $file) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $fileName = now()->format('YmdHis') . '_' . Str::slug($originalName) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/img/ticketAssets'), $fileName);
            $documentPaths[] = 'ticketAssets/' . $fileName;
        }
    }

    // 🔄 Update existing message
    if ($request->filled('comment_id')) {
        
        $existingMessage = Message::find($request->comment_id);

        if ($existingMessage) {
            
            $existingMessage->message = $request->input('message');
            // dd($existingMessage->message);
            if (!empty($documentPaths)) {
                $existingMessage->document = implode(',', $documentPaths);
            }
            $existingMessage->save();

            //dd($existingMessage);

            return response()->json([
                'status' => 200,
                'message' => $existingMessage->load('user'),
                'is_updated' => true,
                'Commentmessage' => 'Message updated successfully.'
            ]);
        }
    }

    // 🆕 Create new message
    $message = Message::create([
        'message'     => $request->input('message'),
        'project_id'  => $projectId,
        'to'          => $to_id,
        'from'        => $user->id,
        'document'    => !empty($documentPaths) ? implode(',', $documentPaths) : null,
    ])->load('user');

    // 🔔 Send notification
    try {
        $notificationData = [
            "greeting-text" => "Hello!",
            "subject"       => "New Message received from - {$user->first_name}",
            "title"         => "You've received a new message from <strong>{$user->first_name}</strong>.",
            "body-text"     => $project->project_name,
            "url-title"     => "View Message",
            "url"           => "/messages?project_id={$projectId}",
        ];

        $recipient = Users::find($to_id);
        if ($recipient) {
            $recipient->notify(new MessageNotification($notificationData));
        }
    } catch (\Exception $e) {
        \Log::error("Notification error: " . $e->getMessage());
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
