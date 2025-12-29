<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use Illuminate\Http\Request;
use App\Models\Users;
use App\Models\Tickets;
use App\Models\TicketAssigns;
use App\Models\TicketComments;
use App\Models\TicketFiles;
use App\Models\Client;
use App\Notifications\EmailNotification;
use App\Notifications\TicketNotification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Models\Sprint;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Carbon\Carbon;
use App\Models\TicketEstimationApproval;
use App\Notifications\EstimationApprovedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

//use Dotenv\Validator;


class TicketsController extends Controller
{
    public function index()
    {
            $ticketsFilter = request()->all() ;
            $allTicketsFilter = $ticketsFilter['all_tickets'] ?? '';
            $projectFilter =  $ticketsFilter['project_filter'] ?? '';
            $completeTicketsFilter = $ticketsFilter['complete_tickets'] ?? '';
            $user = Users::whereHas('role', function ($query) {
                $query->where('name', '!=', 'Super Admin');
            })
            ->where('status', '!=', 0)
            ->where('role_id', '!=', 6)
            ->orderBy('first_name', 'asc')
            ->get();
            $role_id = auth()->user()->role_id;
            $sprints = Sprint::where('status', 1)->get();
            $projects = Projects::all();
            $auth_user =  auth()->user()->id;
            $ticketFilterQuery = Tickets::with('ticketRelatedTo','ticketAssigns')->orderBy('id','desc');
            if ($allTicketsFilter == 'on') {
                if($completeTicketsFilter == 'on'){
                    $tickets = $ticketFilterQuery;
                }else{
                    $tickets = $ticketFilterQuery->where('status', '!=', 'complete');
                }
            } else {
                if (auth()->user()->role->name != "Super Admin") {
                    $tickets = $ticketFilterQuery->whereRelation('ticketAssigns', 'user_id', 'like', '%' . $auth_user . '%')->where('status', '!=', 'complete');

                    if ($completeTicketsFilter == 'on') {
                        $tickets = $ticketFilterQuery->orWhere('status', 'complete');
                    }
                } else {
                    $tickets = $ticketFilterQuery->where('status', '!=', 'complete');
                    // $allTicketsFilter = 'on';
                    if ($completeTicketsFilter == 'on') {
                        $tickets = $ticketFilterQuery->orWhere('status', 'complete');
                    }
                }
            }

            if (request()->has('project_filter') && request()->input('project_filter')!= '') {
                $tickets = $ticketFilterQuery->whereHas('ticketRelatedTo', function($query) {
                    $query->where('id', request()->input('project_filter'));
                });
            }
            if (request()->has('assigned_to_filter') && request()->input('assigned_to_filter')!= '') {
                $tickets = $ticketFilterQuery->whereHas('ticketAssigns', function($query) {
                    $query->where('user_id', request()->input('assigned_to_filter'));
                });
            }
                $tickets = $tickets->get();

            if (!empty($tickets)){
                $ticketStatus = Tickets::join('users', 'tickets.status_changed_by', '=', 'users.id')
            ->select('tickets.status','tickets.id as ticket_id','tickets.updated_at', 'users.first_name', 'users.last_name', )
            ->get();
            foreach ($tickets as $key=>$data)
            {
                $ticketAssigns= TicketAssigns::join('users', 'ticket_assigns.user_id', '=', 'users.id')->where('ticket_id',$data->id)->orderBy('id','desc')->get(['ticket_assigns.*','users.first_name', 'users.profile_picture']);
                $tickets[$key]->ticketassign = !empty($ticketAssigns)? $ticketAssigns:null;
            }
        }

            return view('tickets.index',compact('user','tickets', 'ticketStatus','projects','allTicketsFilter','completeTicketsFilter','sprints', 'role_id'));
    }

        public function create(Request $request)
        {
            $auth_user = auth()->user();
            $sprint_id = $request->get('sprint_id');

            $sprint = null;
            $project_id = null;

            if ($sprint_id) {
                $sprint = Sprint::find($sprint_id);
                if ($sprint) {
                    $project_id = $sprint->project;
                }
            }

            $user = Users::whereHas('role', function($q) {
                    $q->where('name', '!=', 'Super Admin');
                })
                ->where('status', '!=', 0)
                ->where('role_id', '!=', 6)
                ->orderBy('first_name', 'asc')
                ->get();
            $sprints = Sprint::where('status', 1)->get();
            if ($auth_user->role_id == 6) {
                // Assuming there is a `user_id` or similar column in the `projects` table
                $projects = Projects::where('client_id', $auth_user->client_id)->get(); // adjust column name if needed
            } else {
                $projects = Projects::all();
            }
            $auth_user = auth()->user();
            $ticketStatus = Tickets::join('users', 'tickets.status_changed_by', '=', 'users.id')
                ->select('tickets.status','tickets.id as ticket_id','tickets.updated_at', 'users.first_name', 'users.last_name')
                ->get();

            return view('tickets.create', compact('user', 'sprints', 'projects', 'auth_user', 'ticketStatus', 'sprint_id', 'project_id'));
        }



    public function store(Request $request)
	{
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:15',
            'description' => 'required',
            'project_id' => 'required',
            'assign' => 'required|array|min:1',
            'add_document.*' => 'file|mimes:jpg,jpeg,png,gif,bmp,svg,pdf,doc,docx,xls,xlsx,csv,txt,rtf,zip,rar,7z,mp3,wav,ogg,mp4,mov,avi,wmv,flv,mkv,webm|max:10240',
        ], [
            'title.required' => 'Title is required.',
            'title.min' => 'Title must be at least 15 characters.',
            'description.required' => 'Description is required.',
            'project_id.required' => 'Project is required.',
            'assign.required' => 'Assign users is required.',
            'add_document.*.file' => 'Each document must be a valid file.',
            'add_document.*.mimes' => 'Each document must be of type: jpg, jpeg, png, pdf, doc, docx, xls, xlsx.',
            'add_document.*.max' => 'Each document must not exceed 5MB.',
        ]);
            $validator->setAttributeNames([
                'add_document.*' => 'document',
            ]);

            if ($validator->fails())
            {
                return response()->json(['errors'=>$validator->errors()->all()]);
            }

    		$validate = $validator->valid();
            $eta = isset($request['eta']) && !empty($request['eta'])
            ? date("Y-m-d H:i:s", strtotime($request['eta']))
            : null;
            $requestedStatus = $validate['status'] ?? 'to_do';
            $hasEstimation = !empty($validate['time_estimation']);
            $isApproved = false; 

            if ($hasEstimation && !$isApproved && $requestedStatus !== 'to_do') {
                return response()->json([
                    'status' => 403,
                    'error' => 'Estimation is not approved yet. You cannot set the status other than "to_do".'
                ]);
            }

            $tickets =Tickets::create([
                'title' => $validate['title'],
                'description' => $validate['description'],
                'project_id' => $validate['project_id'],
                'sprint_id' => $validate['sprint_id_ticket'],
                'status'=> $validate ['status'],
                'priority'=> $validate ['priority'],
                'ticket_priority'=> $validate ['ticket_priority'],
                'ticket_category'=> $validate ['ticket_category'],
                'time_estimation' => $validate['time_estimation'] ?? null,
                'eta'=> $eta,
                'status_changed_by'=> auth()->user()->id,
                'created_by'=> auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id'=> auth()->user()->id,
            ]);
            Notification::create([
                'user_id' => auth()->user()->id,
                'type' => 'assigned',
                'message' => "You’ve been assigned to Ticket #{$tickets->id}",
                'ticket_id' => $tickets->id,
                'is_super_admin' => false
            ]);

            $managerIds = DB::table('managers')
            ->where('user_id', auth()->user()->id)
            ->pluck('parent_user_id');

        foreach ($managerIds as $managerId) {
            Notification::create([
                'user_id' => $managerId,
                'ticket_id' => $tickets->id,
                'type' => 'assigned',
                'message' => 'Ticket #' . $tickets->id. ' assigned to ' . auth()->user()->first_name,
                'is_read' => false,
                'is_super_admin' => false
            ]);
        }



            if (auth()->user()->id  == 1) {
                Notification::create([
                    'user_id' => auth()->user()->id,
                    'ticket_id' => $tickets->id,
                    'type' => 'assigned',
                    'message' => 'Ticket #' . $tickets->id . ' assigned to ' . auth()->user()->first_name,
                    'is_read' => false,
                    'is_super_admin' => true,
                ]);
            }

            if (isset($validate['assign']))
            {
                foreach($validate['assign'] as $assign)
                {
                    $assign =TicketAssigns::create([
                        'ticket_id' => $tickets->id,
                        'user_id' => $assign,

                    ]);
                }
            }

            if($request->hasfile('add_document')){
                foreach($request->file('add_document') as $file)
                {
                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateString = date('YmdHis');
                $name = $dateString . '_' . $fileName . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/img/ticketAssets'), $name);
                $path='ticketAssets/'.$name;
                    $documents = TicketFiles::create([
                    'document' => $path,
                    'ticket_id'=> $tickets->id,
                    ]);
                }
           }

           //if user assigned then send email Notification to the Assigned Users
           if($assign){
                $ticketAuthor = Users::find(auth()->user()->id);
                $projectDetail = Projects::find($tickets->project_id);
                $authorName = $ticketAuthor->first_name. ' '. $ticketAuthor->last_name;
                $ticket_id = $tickets->id;
                $ticket_eta = $tickets->eta ?: "Not Mentioned";

                $assignee_ids = TicketAssigns::where('ticket_id', $ticket_id)->pluck('user_id');
                $assignedUsers = Users::whereIn('id', $assignee_ids)->get();

                // Compose the message
                $messages = [
                    "subject" => "New Ticket #{$tickets->id} Has Been Created - {$authorName}",
                    "title" => "The New Ticket #{$tickets->id} has been created for project '{$projectDetail->project_name}' subject '{$tickets->title}' and priority level '{$tickets->priority}' end time is '{$ticket_eta}'.",
                    "body-text" => "We kindly request you to review the ticket details and take necessary actions or provide a response if needed.",
                    "action-message" => "To Preview The Change, Click on the link provided below.",
                    "url-title" => "View Ticket",
                    "url" => "/view/ticket/" . $tickets->id
                ];

                foreach ($assignedUsers as $assignedUser) {
                    try {
                        $assignedUser->notify(new EmailNotification($messages));
                    } catch (\Exception $e) {
                        \Log::error("Error sending notification to assigned user {$assignedUser->id}: " . $e->getMessage());
                    }
                }

                if (auth()->user()->role_id == 6) {
                    $admin = Users::find(1); 
                    if ($admin) {
                        try {
                            $admin->notify(new EmailNotification($messages));
                        } catch (\Exception $e) {
                            \Log::error("Error sending notification to admin {$admin->id}: " . $e->getMessage());
                        }
                    }
                }
            }

            $request->session()->flash('message','Tickets added successfully.');
                return response()->json([
                    'status' => 200,
                    'tickets' => $tickets,
                    'redirect' => !empty($request->sprint_id)
                                  ? route('sprint.view', ['sprintId' => $request->sprint_id])
                                  : route('tickets.index')
                ]);
    }

    public function getTicketAssign(Request $request)
	{
        $ticketAssigns= TicketAssigns::join('users', 'ticket_assigns.user_id', '=', 'users.id')->where('ticket_id',$request->id)->orderBy('id','desc')->get(['ticket_assigns.*','users.first_name', 'users.profile_picture']);

        return Response()->json(['status'=>200, 'ticketAssigns'=> $ticketAssigns]);
    }
     public function editTicket($ticketId)
     {
        $ticketsAssign = TicketAssigns::where(['ticket_id' => $ticketId])->get();

        $user = Users::whereHas('role', function($q){
            $q->where('name', '!=', 'Super Admin');
        })->orderBy('id','desc')->get()->toArray();
        $userCount = Users::where('status', '!=', 0)
        ->whereNotIn('role_id', [1, 6])
        ->orderBy('first_name', 'asc')
        ->orderBy('id', 'desc')
        ->get();
        $role_id = auth()->user()->role_id;
        foreach($user as $key1=> $data1)
        {
            foreach($ticketsAssign as $key2=> $data2){
                if($data1['id']==$data2['user_id']){
                    unset($user[$key1]);
                }
            }
        }
        $sprints = Sprint::where('status', 1)->get(['id', 'name']);
        $TicketDocuments=TicketFiles::orderBy('id','desc')->where(['ticket_id' => $ticketId])->get();
        $tickets = Tickets::where(['id' => $ticketId])->first();
        $projects = Projects::all();
        $ticketAssign = TicketAssigns::with('user')->where('ticket_id',$ticketId)->get();
        $CommentsData= TicketComments::with('user')->orderBy('id','Asc')->where(['ticket_id' => $ticketId])->get();  //database query
        $ticketsCreatedByUser = Tickets::with('ticketby')->where('id',$ticketId)->first();
        // dd($ticketsCreatedByUser);
        return view('tickets.edit',compact('tickets','ticketAssign','user','CommentsData' ,'userCount','TicketDocuments','projects', 'ticketsCreatedByUser', 'sprints','role_id'));
     }
     public function updateTicket( Request $request ,$ticketId)
     {
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'edit_project_id' => 'required',
            'edit_document.*' => 'file|mimes:jpg,jpeg,png,gif,bmp,svg,pdf,doc,docx,xls,xlsx,csv,txt,rtf,zip,rar,7z,mp3,wav,ogg,mp4,mov,avi,wmv,flv,mkv,webm|max:10240',
        ], [
            'title.required' => 'Title is required.',
            'description.required' => 'Description is required.',
            'edit_project_id.required' => 'Project is required.',
            'edit_document.*.file' => 'Each document must be a valid file.',
            'edit_document.*.mimes' => 'Each document must be a file of type: jpg, jpeg, png, pdf, doc, docx, xls, xlsx.',
            'edit_document.*.max' => 'Each document may not be greater than 10MB.',
        ]);

            $validator->setAttributeNames([
                'edit_document.*' => 'document',
            ]);

            if ($validator->fails())
            {
                return Redirect::back()->withErrors($validator);
            }
           $validate = $validator->valid();

           $assignedUsers= TicketAssigns::join('users', 'ticket_assigns.user_id', '=', 'users.id')->where('ticket_id',$ticketId)->get(['ticket_assigns.*','users.first_name','users.email']);
           $ticketData = Tickets::with('ticketAssigns')->where('id',$ticketId)->first();
           $changed_by = auth()->user()->first_name;
           $eta = isset($request['eta']) && !empty($request['eta'])
            ? date("Y-m-d H:i:s", strtotime($request['eta']))
            : null;

               $isStatusChanging = $ticketData->status !== $validate['status'];
               // Block all status changes if time_estimation exists and is not approved
                $isStatusChanging = isset($validate['status']) && $ticketData->status !== $validate['status'];
                    $hasEstimation = !is_null($ticketData->time_estimation);
                    $isApproved = TicketEstimationApproval::where('ticket_id', $ticketId)->exists();

                    // Block changing status to anything other than 'to_do' if estimation exists and not approved
                    if ($isStatusChanging && $hasEstimation && !$isApproved && $validate['status'] !== 'to_do') {
                        return Redirect::back()->with('error', 'Estimation is not approved yet. You cannot change the status of this ticket.');
                    }



            $tickets=   Tickets::where('id', $ticketId)
            ->update([
            'title' => $validate['title'],
            'description' => $validate['description'],
            'project_id' => $validate['edit_project_id'],
            'sprint_id' => $validate['edit_sprint_id'],
            'ticket_priority'=> $validate ['ticket_priority'],
            'ticket_category'=> $validate ['ticket_category'],
            'time_estimation' => $validate['time_estimation'] ?? null,
            'status' => $validate['status'],
            'status_changed_by'=> auth()->user()->id,
            'priority' => $validate['priority'],
            'eta'=>$eta
            ]);

            Notification::create([
                'user_id' => auth()->user()->id,
                'type' => 'status_change',
                'message' => "Ticket #{$ticketId} status changed to {$validate['status']}",
                'ticket_id' => $ticketId,
                'is_super_admin' => false
            ]);
            $managerIds = DB::table('managers')
            ->where('user_id', auth()->user()->id)
            ->pluck('parent_user_id');

        foreach ($managerIds as $managerId) {
            Notification::create([
                'user_id' => $managerId,
                'ticket_id' => $ticketId,
                'type' => 'status_change',
                'message' => 'Ticket #' . $ticketId . ' status was updated by ' . auth()->user()->first_name,
                'is_read' => false,
                'is_super_admin' => false
            ]);
        }


            if (auth()->user()->id == 1) {
                Notification::create([
                    'user_id' => auth()->user()->id,
                    'ticket_id' => $ticketId,
                    'type' => 'assigned',
                    'message' => 'Ticket #' . $ticketId . ' assigned to ' . auth()->user()->first_name,
                    'is_read' => false,
                    'is_super_admin' => true,
                ]);
            }

            if ($tickets && $ticketData->status != $validate['status']) {
                foreach ($assignedUsers as $assignedUser) {
                    try {
                        $messages["subject"] = "Status Of #{$assignedUser->ticket_id} Changed By - {$changed_by}";
                        $messages["title"] = "The status of Ticket #{$assignedUser->ticket_id} has been updated to  '{$validate['status']}' by {$changed_by}.";
                        $messages["body-text"] = "To Preview The Change, Click on the link provided below.";
                        $messages["url-title"] = "View Ticket";
                        $messages["url"] = "/view/ticket/" . $assignedUser->ticket_id;
                        $assignedUser->notify(new EmailNotification($messages));
                    } catch (\Exception $e) {
                        \Log::error("Error sending notification for ticket #{$assignedUser->ticket_id} to user {$assignedUser->id}: " . $e->getMessage());
                    }
                }
            }

            if (isset($request->assign)) {
                foreach ($request->assign as $data) {
                    try {
                        $newTicketAssign = TicketAssigns::create([
                            'ticket_id' => $ticketId,
                            'user_id' => $data,
                        ]);

                        if ($newTicketAssign) {
                            $messages["subject"] = "New Ticket #{$ticketId} Assigned By - {$changed_by}";
                            $messages["title"] = "You have been assigned a new ticket #{$ticketId} by {$changed_by}.";
                            $messages["body-text"] = " Please review and take necessary action.";
                            $messages["url-title"] = "View Ticket";
                            $messages["url"] = "/view/ticket/" . $ticketId;
                            $user = Users::find($data);
                            $user->notify(new EmailNotification($messages));
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error assigning ticket #{$ticketId} to user {$data}: " . $e->getMessage());
                    }
                }
            }
            if($request->hasfile('edit_document')){
                foreach($request->file('edit_document') as $file)
                {
                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateString = date('YmdHis');
                $name = $dateString . '_' . $fileName . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/img/ticketAssets'), $name);
                $path='ticketAssets/'.$name;
                    $documents = TicketFiles::create([
                    'document' => $path,
                    'ticket_id'=> $ticketId,
                    ]);
                }

           }
            $source = $request->input('source') ?? $request->query('source');
            // dd($source);
            $request->session()->flash('message','Ticket updated successfully.');
    		if ($source === 'ticket') {
                return redirect()->route('tickets.index');
            } elseif ($source === 'sprint') {
                return redirect()->route('sprint.view', ['sprintId' => $request->input('edit_sprint_id')]);
            } else {
                return redirect()->route('tickets.index');
            }
     }

     public function destroy(Request $request)
     {
         $tickets = Tickets::where('id',$request->id)->delete();
         $request->session()->flash('message','Ticket deleted successfully.');
         return Response()->json($tickets);
     }

        public function addComments(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'comment' => 'nullable|string',
                'comment_files.*' => 'file|mimes:jpg,jpeg,png,gif,bmp,svg,pdf,doc,docx,xls,xlsx,csv,txt,rtf,zip,rar,7z,mp3,wav,ogg,mp4,mov,avi,wmv,flv,mkv,webm|max:51200',
            ], [
                'comment_files.*.file' => 'Each file must be a valid file.',
                'comment_files.*.mimes' => 'Each file must be one of the allowed types.',
                'comment_files.*.max' => 'Each file may not be greater than :max kilobytes.',
            ]);
        
            $validator->after(function ($validator) use ($request) {
                if (empty($request->comment) && !$request->hasFile('comment_files')) {
                    $validator->errors()->add('comment', 'Kindly type a message or attach a file before submitting.');
                }
            });

            if ($validator->fails()) {
                $errors = $validator->errors();
                $allErrors = $errors->all();

                foreach ($allErrors as $error) {
                    if (Str::contains($error, 'greater than') || Str::contains($error, 'Maximum file size allowed')) {
                        return response()->json([
                            'errors' => [
                                'One or more files exceed the 10MB limit. If you want to upload files larger than 10MB, please visit: <a href="https://files.code4each.com/" target="_blank">https://files.code4each.com/</a>'
                            ]
                        ]);
                    }
                }

                return response()->json(['errors' => $allErrors]);
            }

            $validate = $validator->valid();
            $documentPaths = [];
            if ($request->hasFile('comment_files')) {
                
                foreach ($request->file('comment_files') as $file) {
                    $fileSizeInKB = $file->getSize() / 1024;
                    $originalFilename = $file->getClientOriginalName();

                    if ($fileSizeInKB <= 10240) { // ≤10MB → store locally
                        $fileName = pathinfo($originalFilename, PATHINFO_FILENAME);
                        $dateString = date('YmdHis');
                        $name = $dateString . '_' . $fileName . '.' . $file->getClientOriginalExtension();
                        $file->move(public_path('assets/img/ticketAssets'), $name);
                        $path = 'ticketAssets/' . $name;

                        TicketFiles::create([
                            'document' => $path,
                            'ticket_id' => $validate['id'],
                        ]);

                        $documentPaths[] = $path;
                    } else { // >10MB → upload via external API
                        $fileUniqueId = Str::random(10);
                        try {
                            $apiResponse = Http::attach(
                                'fileToTransfer',
                                file_get_contents($file),
                                $originalFilename
                            )->post('https://files.code4each.com/api/upload-and-generate-link', [
                                'file_unique_id' => $fileUniqueId,
                                'original_filename' => $originalFilename,
                                'file_type' => 'public',
                                'source' => 'hr_portal',
                            ]);

                            if ($apiResponse->successful()) {
                                $fileUniqueId = $apiResponse->json()['file_unique_id'] ?? $fileUniqueId;
                                $fileLink = 'https://files.code4each.com/download-file/' . $fileUniqueId;
                                $documentPaths[] = $fileLink;
                            }
                        } catch (\Exception $e) {
                            \Log::error('Large file upload error: ' . $e->getMessage());
                        }
                    }
                }
            }
            if ($request->has('comment_id') && $request->comment_id != null) {
                $existingComment = TicketComments::find($request->comment_id);
                if ($existingComment) {
                    $existingComment->comments = $validate['comment'];
                    if (!empty($documentPaths)) {
                        $existingComment->document = implode(',', $documentPaths);
                    }
                    $existingComment->save();

                    $CommentsData = TicketComments::with('user')->where('id', $existingComment->id)->get();
                    return response()->json([
                        'status' => 200,
                        'CommentsData' => $CommentsData,
                        'Commentmessage' => 'Comment updated successfully.'
                    ]);
                }
            }

            $ticket = TicketComments::create([
                'comments'   => $validate['comment'],
                'ticket_id'  => $validate['id'],
                'document'   => implode(',', $documentPaths),
                'comment_by' => auth()->user()->id,
            ]);

            Notification::create([
                'user_id' => auth()->user()->id,
                'type' => 'comment',
                'message' => "New comment on Ticket #{$validate['id']}",
                'ticket_id' => $validate['id'],
                'is_super_admin' => false
            ]);

            $managerIds = DB::table('managers')
                ->where('user_id', auth()->user()->id)
                ->pluck('parent_user_id');

            foreach ($managerIds as $managerId) {
                Notification::create([
                    'user_id' => $managerId,
                    'ticket_id' => $validate['id'],
                    'type' => 'comment',
                    'message' => 'Ticket #' . $validate['id'] . ' commented by ' . auth()->user()->first_name,
                    'is_read' => false,
                    'is_super_admin' => false
                ]);
            }

            if (auth()->user()->id == 1) {
                Notification::create([
                    'user_id' => auth()->user()->id,
                    'ticket_id' => $validate['id'],
                    'type' => 'assigned',
                    'message' => 'Ticket #' . $validate['id'] . ' assigned to ' . auth()->user()->first_name,
                    'is_read' => false,
                    'is_super_admin' => true
                ]);
            }

            if ($ticket) {
                $currentUser = auth()->user();
                $ticketModel = Tickets::find($validate['id']);
                $ticketName = $ticketModel->title ?? "Ticket #{$validate['id']}";
                $rawComment = $validate['comment'];

                $ticketModel = Tickets::find($validate['id']);
                $ticketName = $ticketModel->title ?? "Ticket #{$validate['id']}";
                $documentText = '';
                if (!empty($documentPaths)) {
                    $documentText .= '<p><strong>Attached Document(s):</strong></p><ul>';
                    foreach ($documentPaths as $docPath) {
                        $fileName = basename($docPath);
                        $documentText .= "<li>{$fileName}</li>"; 
                    }
                    $documentText .= '</ul>';
                }
                
                // Determine who commented
                if ($currentUser->role_id == 6) {
                    // Comment made by client
                    $clientId = $currentUser->client_id;
                    $user = Users::where('client_id', $clientId)->first();

                    $messages["greeting-text"] = "Hello!";
                    $messages["subject"] = "New Comment on \"{$ticketName}\" by - {$user->first_name}";
                    $messages["title"] = "A new comment has been added to Ticket # <strong>{$validate['id']}</strong>";
                    $messages["title-ticketName"] = "<p><strong>Ticket Name:</strong> {$ticketName}</p>";
                    $messages["body-text"] = $rawComment;
                    $messages["url-title"] = "View Ticket";
                    $messages["url"] = "/view/ticket/" . $validate['id'];
    
                    try {
                        // Notify all assigned users
                        $assignedUsers = TicketAssigns::join('users', 'ticket_assigns.user_id', '=', 'users.id')
                            ->where('ticket_id', $validate['id'])
                            ->get(['users.id', 'users.first_name', 'users.email']);
                        
                        foreach ($assignedUsers as $assignedUser) {
                            $assignedUser->notify(new TicketNotification($messages, $documentPaths));
                        }
    
                        // Notify admin (user ID 1)
                        $admin = Users::find(1);
                        if ($admin) {
                            $admin->notify(new TicketNotification($messages, $documentPaths));
                        }
    
                    } catch (\Exception $e) {
                        \Log::error("Error sending notification for client comment on ticket #{$validate['id']}: " . $e->getMessage());
                    }
    
                } else {
                    // Comment made by admin or assigned user
                    $user = Users::find($currentUser->id);
                    // $bccEmail = 'harpreet.developer.02@gmail.com';
                    $bccEmail = '';

                    $messages["greeting-text"] = "Hello!";
                    $messages["subject"] = "New Comment on \"{$ticketName}\" by - {$user->first_name}";
                    $messages["title"] = "A new comment has been added to Ticket # <strong>{$validate['id']}</strong>";
                    $messages["title-ticketName"] = "<p><strong>Ticket Name:</strong> {$ticketName}</p>";
                    $messages["body-text"] = $rawComment;
                    $messages["url-title"] = "View Ticket";
                    $messages["url"] = "/view/ticket/" . $validate['id'];
                    try {
                        // Notify client (get the user with the same client_id)
                        $ticketModel = Tickets::find($validate['id']);
                        $projectId = $ticketModel->project_id;
                        $project = Projects::find($projectId);
    
                        if ($project) {
                            $clientId = $project->client_id;
                        }
                        $client = Users::where('client_id', $clientId)->first();
                        $secondaryEmail = Client::where('id', $clientId)->value('secondary_email');
                        $additional_email = Client::where('id', $clientId)->value('additional_email');
                       
                        if ($client) {
                            $client->notify(new TicketNotification($messages, $documentPaths, $bccEmail));

                            if (!empty($secondaryEmail)) {
                                NotificationFacade::route('mail', $secondaryEmail)
                                    ->notify(new TicketNotification($messages, $documentPaths, $bccEmail));
                            }
                            if (!empty($additional_email)) {
                                NotificationFacade::route('mail', $additional_email)
                                    ->notify(new TicketNotification($messages, $documentPaths, $bccEmail));
                            } 
                        }
    
                    } catch (\Exception $e) {
                        \Log::error("Error sending notification for staff/admin comment on ticket #{$validate['id']}: " . $e->getMessage());
                    }
                }
            }
            $CommentsData = TicketComments::with('user')->where('id', $ticket->id)->get();
            return response()->json([
                'status' => 200,
                'CommentsData' => $CommentsData,
                'Commentmessage' => 'Comment added successfully.'
            ]);
        }
        public function DeleteTicketAssign(request $request)
        {
            $ticketAssign = TicketAssigns::where('id',$request->id)->delete();
            $request->session()->flash('message','TicketAssign deleted successfully.');
            $AssignData = TicketAssigns::where(['ticket_id' => $request->TicketId])->get();

            $user = Users::whereHas('role', function($q){
                $q->where('name', '!=', 'Super Admin');
            })->orderBy('id','desc')->get()->toArray();

        foreach($user as $key1=> $data1)
        {
            foreach($AssignData as $key2=> $data2){
                if($data1['id']==$data2['user_id']){
                    unset($user[$key1]);
                }
            }
        }
            return Response()->json(['status'=>200 ,'user' => $user,'AssignData' => $AssignData]);
    }

    public function deleteTicketFile(Request $request)
    {

        $ticketFile = TicketFiles::where('id',$request->id)->forceDelete();
        $request->session()->flash('message','TicketFile deleted successfully.');
        return Response()->json(['status'=>200]);

    }

        public function viewTicket($ticketId)
    {
        $ticketsAssign = TicketAssigns::where(['ticket_id' => $ticketId])->get();
        $ticket = Tickets::find($ticketId);
        $projectId = $ticket->project_id;
        $project = Projects::find($projectId);

        $projectName = $project ? $project->project_name : 'Project Not Found';
        $client = $project?->client;
        $user = Users::whereHas('role', function($q){
           $q->where('name', '!=', 'Super Admin');
       })->orderBy('id','desc')->get()->toArray();
        $userCount = Users::orderBy('id','desc')->where('status','!=',0)->get();
       foreach($user as $key1=> $data1)
       {
           foreach($ticketsAssign as $key2=> $data2){
               if($data1['id']==$data2['user_id']){
                   unset($user[$key1]);
               }
           }
       }
       $TicketDocuments=TicketFiles::orderBy('id','desc')->where(['ticket_id' => $ticketId])->get();
       $tickets = Tickets::where(['id' => $ticketId])->first();
       $projectId = $tickets->project_id;
       $projects = Projects::where('id', $projectId)->get();
       $ticketAssign = TicketAssigns::with('user')->where('ticket_id',$ticketId)->get();
        $CommentsData = TicketComments::with('user')
                        ->where('ticket_id', $ticketId)
                        ->orderBy('id', 'desc')
                        ->take(10)
                        ->get()
                        ->sortBy('id') // to maintain ascending order in UI
                        ->values(); // reset keys

       $ticketsCreatedByUser = Tickets::with('ticketby')->where('id',$ticketId)->first();
       return view('tickets.ticketdetail', compact('tickets','ticketAssign','user','CommentsData' ,'userCount','TicketDocuments','projects', 'ticketsCreatedByUser',  'projectName', 'client'));

    }
    public function viewDocument($filename)
{
    $filePath = public_path('assets/img/ticketAssets/' . $filename);

    if (!file_exists($filePath)) {
        abort(404);
    }

    $mime = mime_content_type($filePath);

    return response()->file($filePath, [
        'Content-Type' => $mime,
        'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
    ]);
}

public function updateStatus(Request $request, $id)
{
        $request->validate([
        'status' => 'required|in:to_do,in_progress,ready,deployed,complete,invoice_done',
    ]);
    $auth_user =  auth()->user()->id;
    $ticket = Tickets::findOrFail($id);

    $isStatusChanging = isset($request->status) && $ticket->status !== $request->status;
    $hasEstimation = !is_null($ticket->time_estimation);
    $isApproved = TicketEstimationApproval::where('ticket_id', $id)->exists();

    if (
        $isStatusChanging &&
        $hasEstimation &&
        !$isApproved &&
        $request->status !== 'to_do'
    ) {
       return response()->json([
    'success' => false,
    'error' => 'Estimation is not approved yet. You cannot change the status of this ticket.'
     ]);
    }
    $ticket->status = $request->status;
    $ticket->save();



    Notification::create([
        'user_id' => $auth_user,
        'type' => 'status_change',
        'message' => "Ticket #{$ticket->id} status changed to {$ticket->status}",
        'ticket_id' => $ticket->id,
        'is_super_admin' => false
    ]);

    $managerIds = DB::table('managers')
        ->where('user_id', $auth_user)
        ->pluck('parent_user_id');

    foreach ($managerIds as $managerId) {
        Notification::create([
            'user_id' => $managerId,
            'ticket_id' => $ticket->id,
            'type' => 'status_change',
            'message' => 'Ticket #' . $ticket->id . ' status was updated by ' . auth()->user()->first_name,
            'is_read' => false,
            'is_super_admin' => false
        ]);
    }

            if (auth()->user()->id == 1) {
                Notification::create([
                    'user_id' => auth()->user()->id,
                    'ticket_id' => $ticket->id,
                    'type' => 'assigned',
                    'message' => 'Ticket #' . $ticket->id . ' assigned to ' . auth()->user()->first_name,
                    'is_read' => false,
                    'is_super_admin' => true
                ]);
            }

    return response()->json(['success' => true]);
}

public function notifications()
{
    $userId = auth()->id();

    if (request()->ajax()) {
        if ($userId == 1) {

            $notifications = Notification::latest()->take(5)->get();
            $unreadCount = Notification::where('is_read', false)->count();
        } else {

            $notifications = Notification::where('user_id', $userId)
                ->latest()
                ->take(5)
                ->get();

            $unreadCount = Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->count();
        }

        return response()->json([
            'html' => view('notifications.partials._dropdown', compact('notifications', 'unreadCount'))->render(),
            'unreadCount' => $unreadCount,
            'notifications' => $notifications
        ]);
    }

    if ($userId == 1) {
        $notifications = Notification::orderBy('created_at', 'desc')->get();
    } else {
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    return view('notifications.index', compact('notifications'));
}


public function markAsRead($id)
{
    logger("MarkAsRead called with ID: " . $id);

    $notification = Notification::where('id', $id)->first();
    if (!$notification) {
        return response()->json([
            'success' => false,
            'message' => 'Notification not found',
            'id' => $id
        ], 404);
    }

    $notification->update(['is_read' => 1]);

    return response()->json([
        'success' => true,
        'id' => $id
    ]);
}


public function markAllAsRead()
{
    Notification::where('user_id', auth()->id())
        ->where('is_read', false)
        ->update(['is_read' => true]);

    return response()->json(['success' => true]);
}

public function deleteComment($id)
{
    $comment = Ticketcomments::find($id);

    if (!$comment) {
        return response()->json([
            'status' => 404,
            'message' => 'Comment not found.'
        ]);
    }

    $comment->delete();

    return response()->json([
        'status' => 200,
        'message' => 'Comment deleted successfully.',
        'id' => $id
    ]);
}
public function loadMoreComments($ticketId, Request $request)
{
    $lastId = $request->last_id;

    $comments = TicketComments::with('user')
        ->where('ticket_id', $ticketId)
        ->where('id', '<', $lastId)
        ->orderBy('id', 'desc')
        ->limit(10)
        ->get()
        ->sortBy('id')
        ->values();

    $comments->each(function ($comment) {
        $carbonDate = Carbon::parse($comment->created_at)->timezone('Asia/Kolkata');
        $comment->created_at_formatted = $carbonDate->format('M d, Y h:i A');

        $today = Carbon::now()->startOfDay();
        $yesterday = Carbon::yesterday()->startOfDay();
        $commentDate = $carbonDate->startOfDay();

        if ($commentDate->eq($today)) {
            $comment->created_date_label = 'Today';
        } elseif ($commentDate->eq($yesterday)) {
            $comment->created_date_label = 'Yesterday';
        } else {
            $comment->created_date_label = $commentDate->format('M d, Y');
        }

        $comment->project_name = optional($comment->ticket->project)->project_name ?? 'Project Not Assigned';

        $comment->role = $comment->user->role_id == 6
            ? $comment->project_name
            : 'Code4Each';
    });

    return response()->json(['comments' => $comments]);
}

public function approveEstimation($id)
{

    $existing = TicketEstimationApproval::where('ticket_id', $id)->first();

    if ($existing) {
        return back()->with('error', 'Estimation has already been approved.');
    }

    TicketEstimationApproval::create([
        'ticket_id'   => $id,
        'approved_by' => auth()->id(),
        'approved_at' => now(),
    ]);

    $user = auth()->user();
    TicketComments::create([
        'comments'   => "Time estimation approved.",
        'ticket_id'  => $id,
        'comment_by' => $user->id,
        'is_system'  => true,          
    ]);
    
    $messages = [
    "greeting-text" => "Hello!",
    "subject"       => "Time Estimation Approved for Ticket #{$id}",
    "title"         => "Estimation Approved",
    "body-text"     => "Time estimation has been approved for Ticket #{$id} by {$user->first_name}.",
    "url-title"     => "View Ticket",
    "url"           => "/view/ticket/" . $id,
];

try {
    $assignedUsers = TicketAssigns::join('users', 'ticket_assigns.user_id', '=', 'users.id')
        ->where('ticket_assigns.ticket_id', $id)
        ->get(['users.id', 'users.email']);

    $superAdmin = Users::find(1); 

    foreach ($assignedUsers as $assignedUser) {
        $assignedUser->notify(new EstimationApprovedNotification($messages));
    }

    if ($user->role_id == 6 && $superAdmin) {
        $superAdmin->notify(new EstimationApprovedNotification($messages));
    }


} catch (\Exception $e) {
    \Log::error("Error sending estimation approval notification: " . $e->getMessage());
}

    return back()->with('success', 'Estimation approved successfully.');
}


}
