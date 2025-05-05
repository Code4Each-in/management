<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Users;
use App\Models\Message;
use App\Models\Projects;
use App\Models\Client;
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
        $clientId = $user->client_id;
        $projectIds = Projects::where('client_id', $clientId)
            ->pluck('id')
            ->toArray();

        $projects = Projects::whereIn('id', $projectIds)
            ->orderBy('id', 'desc')
            ->get();


        foreach ($projects as $project) {
       
            $project->last_message = Message::where('project_id', $project->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $project->unread_count = Message::where('project_id', $project->id)
                ->where('is_read', 0)
                ->count();
        }

        $client = Client::find($clientId);

        return view('developer.chat', compact('projects', 'client'));
    }
    
    public function getMessagesByProject($projectId)
    {
       
        $messages = Message::where('project_id', $projectId)
            ->with('user') 
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['messages' => $messages]);
    }
 
}