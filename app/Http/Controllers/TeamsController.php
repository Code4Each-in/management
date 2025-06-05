<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Projects;
use App\Models\Client;
use App\Models\Users;
use App\Models\Tickets;
use App\Models\GroupMessage;
use App\Models\GroupMessageRead;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Notification;
use App\Notifications\MessageNotification;

class TeamsController extends Controller
{
    /**
     * Display the Team Chat page.
     */
   public function index()
{
    $user = Auth::user();
    $client = null;
    $roleid = $user->role_id;

    if ($roleid == 1) {
        $projects = Projects::orderBy('id', 'desc')->get();
        if ($projects->isNotEmpty()) {
            $client = Client::find($projects->first()->client_id);
        }
    } elseif ($roleid == 6) {
        $clientId = $user->client_id;
        $projects = Projects::where('client_id', $clientId)
            ->orderBy('id', 'desc')
            ->get();
        $client = Client::find($clientId);
    } elseif (in_array($roleid, [2, 3])) {
        $ticketIds = DB::table('ticket_assigns')
            ->where('user_id', $user->id)
            ->pluck('ticket_id');

        $projectIds = Tickets::whereIn('id', $ticketIds)
            ->pluck('project_id')
            ->unique();

        $projects = Projects::whereIn('id', $projectIds)
            ->orderBy('id', 'desc')
            ->get();

        if ($projects->isNotEmpty()) {
            $client = Client::find($projects->first()->client_id);
        }
    } else {
        $projects = collect();
    }

    $projects = $projects->unique('project_name')->values();

    foreach ($projects as $project) {
        $project->last_message = GroupMessage::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $project->unread_count = GroupMessage::where('project_id', $project->id)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->count();

        $project->client = Client::find($project->client_id);
    }

    // Sort projects by last message date descending
    $projects = $projects->sortByDesc(function ($project) {
        return optional($project->last_message)->created_at;
    })->values();

    return view('teamchat.index', compact('projects', 'client', 'roleid'));
}


    public function addMessages(Request $request)
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

        $user = auth()->user();
        $projectId = $request->input('project_id');
        $project = Projects::find($projectId);

        if (!$project) {
            return response()->json(['errors' => ['Project not found.']]);
        }
        $documentPaths = [];
        if ($request->hasFile('comment_file')) {
            foreach ($request->file('comment_file') as $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $fileName = now()->format('YmdHis') . '_' . Str::slug($originalName) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/img/ticketAssets'), $fileName);
                $documentPaths[] = 'ticketAssets/' . $fileName;
            }
        }
        if ($request->filled('comment_id')) {
            $existingMessage = GroupMessage::find($request->comment_id);
            if ($existingMessage) {
                $existingMessage->message = $request->input('message');
                if (!empty($documentPaths)) {
                    $existingMessage->document = implode(',', $documentPaths);
                }
                $existingMessage->save();

                return response()->json([
                    'status' => 200,
                    'message' => $existingMessage->load('user'),
                    'is_updated' => true,
                    'Commentmessage' => 'Message updated successfully.'
                ]);
            }
        }
        $newMessage = GroupMessage::create([
            'message' => $request->input('message'),
            'project_id' => $projectId,
            'user_id' => $user->id,
            'document' => !empty($documentPaths) ? implode(',', $documentPaths) : null,
        ]);
      $exists = GroupMessageRead::where('message_id', $newMessage->id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$exists) {
            GroupMessageRead::create([
                'project_id' => $projectId,
                'user_id' => $user->id,
                'message_id' => $newMessage->id,
            ]);
        }   

       try {
    $notificationData = [
        "greeting-text" => "Hello!",
        "subject" => "New Message received from - {$user->first_name}",
        "title" => "You've received a new message from <strong>{$user->first_name}</strong>.",
        "body-text" => $project->project_name,
        "url-title" => "View Message",
        "url" => "/teamchat?project_id={$projectId}",
    ];

            $recipientIds = [];
            $recipientIds[] = 1;
            

            if ($project->client_id) {
                $clientUser = Users::where('client_id', $project->client_id)->first();
                if ($clientUser && $clientUser->id != $user->id) {
                    $recipientIds[] = $clientUser->id;
                }
            }

            $ticketIds = Tickets::where('project_id', $projectId)->pluck('id');

            $developerIds = DB::table('ticket_assigns')
                ->whereIn('ticket_id', $ticketIds)
                ->pluck('user_id')
                ->unique()
                ->filter(fn ($id) => $id != $user->id); 

            $recipientIds = array_merge($recipientIds, $developerIds->toArray());
            $recipientIds = array_unique($recipientIds);
            $recipients = Users::whereIn('id', $recipientIds)->get();
            foreach ($recipients as $recipient) {
                $recipient->notify(new MessageNotification($notificationData));
            }
        } catch (\Exception $e) {
            \Log::error("Notification error: " . $e->getMessage());
        }

        return response()->json([
            'status' => 200,
            'message' => $newMessage->load('user'),
            'Commentmessage' => 'Message added successfully.'
        ]);
    }

    public function getMessagesByProjects(Request $request, $projectId)
    {
        $limit = 15;
        $offset = $request->input('offset', 0); 
        $messages = GroupMessage::where('project_id', $projectId)
            ->with('user', 'project')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
        return response()->json(['messages' => $messages]);
    }

  public function markAsRead(Request $request, GroupMessage $message)
{
    $userId = auth()->id();
    $projectId = $message->project_id;

    // Get all unread messages up to this message ID
    $messagesToMark = GroupMessage::where('project_id', $projectId)
        ->where('id', '<=', $message->id)
        ->where('user_id', '!=', $userId)
        ->whereDoesntHave('reads', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->pluck('id');

    // Bulk insert missing read records
    foreach ($messagesToMark as $msgId) {
        GroupMessageRead::firstOrCreate([
            'message_id' => $msgId,
            'user_id' => $userId,
        ], [
            'read_at' => now(),
        ]);
    }

    // Recalculate unread count
    $unreadCount = GroupMessage::where('project_id', $projectId)
        ->where('user_id', '!=', $userId)
        ->whereDoesntHave('reads', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->count();

    return response()->json([
        'status' => 'success',
        'updatedUnreadCount' => $unreadCount,
    ]);
}


public function getUnreadMessageCount($projectId)
{
    $userId = auth()->id();

    $unreadCount = GroupMessage::where('project_id', $projectId)
        ->where('user_id', '!=', $userId) // Exclude own messages
        ->whereDoesntHave('reads', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->count();

    return response()->json([
        'status' => 'success',
        'unreadCount' => $unreadCount,
    ]);
}


    public function destroy($id)

    {
        $comment = GroupMessage::findOrFail($id);
        $comment->delete();
        return response()->json(['status' => 200, 'message' => 'Comment deleted successfully.']);
    }


}

