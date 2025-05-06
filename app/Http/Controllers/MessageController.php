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

    if ($user->role_id == 1) {
        $projects = Projects::orderBy('id', 'desc')->get();
        $client = null; 
    } else {
        $clientId = $user->client_id;
        $projectIds = Projects::where('client_id', $clientId)->pluck('id')->toArray();

        $projects = Projects::whereIn('id', $projectIds)
            ->orderBy('id', 'desc')
            ->get();

        $client = Client::find($clientId);
    }

    foreach ($projects as $project) {
        $project->last_message = Message::where('project_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $project->unread_count = Message::where('project_id', $project->id)
            ->where('is_read', 0)
            ->count();
    }

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

    $documentPaths = [];
    $maxTotalSize = 5 * 1024 * 1024;  
    $totalSize = 0;
    $user = Auth::user();
    $messageData = [
        'message' => $request->input('message'),
        'project_id' => $request->input('project_id'),
        'to' => $request->input('to'),
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

    return response()->json([
        'status' => 200,
        'Commentmessage' => 'Message added successfully.'
    ]);
}


}