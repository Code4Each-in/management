<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use Illuminate\Http\Request;
use App\Models\Users;
use App\Models\Tickets;
use App\Models\TicketAssigns;
use App\Models\TicketComments;
use App\Models\TicketFiles;
use App\Notifications\EmailNotification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
//use Dotenv\Validator;


class TicketsController extends Controller
{
    public function index()
    {
            $ticketsFilter = request()->all() ;
            $allTicketsFilter = $ticketsFilter['all_tickets'] ?? '';
            $projectFilter =  $ticketsFilter['project_filter'] ?? '';
            $completeTicketsFilter = $ticketsFilter['complete_tickets'] ?? '';
            $user = Users::whereHas('role', function($q){
                $q->where('name', '!=', 'Super Admin');
            })->where('status','!=',0)->orderBy('id','desc')->get();	
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
        
            return view('tickets.index',compact('user','tickets', 'ticketStatus','projects','allTicketsFilter','completeTicketsFilter'));   
    }
    public function store(Request $request) 
	{ 
        $validator = Validator::make($request->all(),[
            'title' => 'required',
            'description'=>'required',
            // 'assign'=>'required',
            // 'eta_to' => 'required',
             'project_id' => 'required',
             'status'=>'required', 
             'priority'=>'required',
             'add_document.*' => 'file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:5000',
            ],[
                'add_document.*.file' => 'The :attribute must be a file.', 
                'add_document.*.mimes' => 'The :attribute must be a file of type: jpeg, png, pdf.',
                'add_document.*.max' => 'The :attribute may not be greater than :max kilobytes.',
                'add_document.*.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',

            ]);
            $validator->setAttributeNames([
                'add_document.*' => 'document',
            ]);

            if ($validator->fails())
            {
                return response()->json(['errors'=>$validator->errors()->all()]);
            }
            
    		$validate = $validator->valid();
            $eta = date("Y-m-d H:i:s",strtotime($request['eta'])); 

            $tickets =Tickets::create([
                'title' => $validate['title'],
                'description' => $validate['description'],
                'project_id' => $validate['project_id'], 
                'status'=> $validate ['status'],
                'priority'=> $validate ['priority'],
                'eta'=> $eta,
                'status_changed_by'=> auth()->user()->id,
                'created_by'=> auth()->user()->id,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'user_id'=> auth()->user()->id,     
            ]);
            
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
                $name = $dateString . '_' . $fileName . '.' . $file->extension();
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
                $ticket_eta = "Not Mentioned";
                if ($tickets->eta){
                    $ticket_eta = $tickets->eta;
                }
                $assignee_ids = TicketAssigns::where('ticket_id', $ticket_id)->pluck('user_id as id');
                foreach ($assignee_ids as $id) {
                    $assignedUsers = Users::where('id',$id)->get();
                }
                if($tickets){
                    foreach ($assignedUsers as $assignedUser) {
                        $messages["subject"] = "New Ticket #{$tickets->id} Has Been Created - {$authorName}";
                        $messages["title"] = "The New Ticket #{$tickets->id} has been created for project '{$projectDetail->project_name}' subject  '{$tickets->title}' and priority level '{$tickets->priority}' end time is '{$ticket_eta}'.";
                        $messages["body-text"] = "We kindly request you to review the ticket details and take necessary actions or provide a response if needed. ";
                        $messages["action-message"] = "To Preview The Change, Click on the link provided below.";
                        $messages["url-title"] = "View Ticket";
                        $messages["url"] = "/edit/ticket/" . $tickets->id;
                        $assignedUser->notify(new EmailNotification($messages));
                    }
                }
            }
            $request->session()->flash('message','Tickets added successfully.');
    		return Response()->json(['status'=>200, 'tickets'=>$tickets]);
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
        $projects = Projects::all();
        $ticketAssign = TicketAssigns::with('user')->where('ticket_id',$ticketId)->get();
        $CommentsData= TicketComments::with('user')->orderBy('id','Asc')->where(['ticket_id' => $ticketId])->get();  //database query
        $ticketsCreatedByUser = Tickets::with('ticketby')->where('id',$ticketId)->first();
        // dd($ticketsCreatedByUser);
        return view('tickets.edit',compact('tickets','ticketAssign','user','CommentsData' ,'userCount','TicketDocuments','projects', 'ticketsCreatedByUser'));   	
     }     
     public function updateTicket( Request $request ,$ticketId)
     {
        $validator = Validator::make($request->all(),[
            'title' => 'required', 
            'description'=>'required', 
            'status'=>'required',
            'edit_project_id' => 'required',
            'priority'=>'required',
            'edit_document.*' => 'file|mimes:jpg,jpeg,png,doc,docx,xls,xlsx,pdf|max:5000',
            ],[
                'edit_document.*.file' => 'The :attribute must be a file.', 
                'edit_document.*.mimes' => 'The :attribute must be a file of type: jpeg, png, pdf.',
                'edit_document.*.max' => 'The :attribute may not be greater than :max kilobytes.',
                'edit_document.*.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',

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

            $tickets=   Tickets::where('id', $ticketId)  
            ->update([
            'title' => $validate['title'],        
            'description' => $validate['description'],
            'project_id' => $validate['edit_project_id'],
            'status' => $validate['status'],
            'status_changed_by'=> auth()->user()->id,
            'priority' => $validate['priority'],
            'eta'=>$request['eta'],
            ]);
            

            if($tickets && $ticketData->status != $validate['status']){
                foreach ($assignedUsers as $assignedUser) {
                    $messages["subject"] = "Status Of #{$assignedUser->ticket_id} Changed By - {$changed_by}";
                    $messages["title"] = "The status of Ticket #{$assignedUser->ticket_id} has been updated to  '{$validate['status']}' by {$changed_by}.";
                    $messages["body-text"] = "To Preview The Change, Click on the link provided below.";
                    $messages["url-title"] = "View Ticket";
                    $messages["url"] = "/edit/ticket/" . $assignedUser->ticket_id;
                    $assignedUser->notify(new EmailNotification($messages));
                }
            }

            if (isset($request->assign))
            {				
                foreach($request->assign as $data)
                {				
                    $newTicketAssign =TicketAssigns::create([					
                        'ticket_id' => $ticketId,
                        'user_id' => $data,
                    ]);
                    if($newTicketAssign){
                        $messages["subject"] = "New Ticket #{$ticketId} Assigned By - {$changed_by}";
                        $messages["title"] = "You have been assigned a new ticket #{$ticketId} by {$changed_by}.";
                        $messages["body-text"] = " Please review and take necessary action.";
                        $messages["url-title"] = "View Ticket";
                        $messages["url"] = "/edit/ticket/" . $ticketId;
                        $user = Users::find($data);
                        $user->notify(new EmailNotification($messages));
                    }	
                }
               	
            }
            if($request->hasfile('edit_document')){
                foreach($request->file('edit_document') as $file)
                {
                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateString = date('YmdHis');
                $name = $dateString . '_' . $fileName . '.' . $file->extension();
                $file->move(public_path('assets/img/ticketAssets'), $name);  
                $path='ticketAssets/'.$name;
                    $documents = TicketFiles::create([
                    'document' => $path,
                    'ticket_id'=> $ticketId,
                    ]); 
                }
               
           }

            $request->session()->flash('message','Ticket updated successfully.');
    		return redirect()->back()->with('tickets', $tickets);
     }
     public function destroy(Request $request)
     {
         $tickets = Tickets::where('id',$request->id)->delete(); 
         $request->session()->flash('message','Ticket deleted successfully.');
         return Response()->json($tickets);
     }
       
    public function addComments( request $request )
    {
        $validator = Validator::make($request->all(),[
            'comment' => 'required', 
            ]);
            
            if ($validator->fails())
            {
                return response()->json(['errors'=>$validator->errors()->all()]);
            }
           $validate = $validator->valid();
            $ticket =TicketComments::create([
            'comments' => $validate['comment'],
            'ticket_id'=>$validate['id'],
            'comment_by'=> auth()->user()->id,     
        ]);
        if($ticket){
            $id = auth()->user()->id;
            $user = Users::find($id);
            $messages["subject"] = "New Comment On #{$validate['id']} By - {$user->first_name}";
            $messages["title"] = "A new comment has been added to Ticket #{$validate['id']}.Where You are assigned to this ticket.";
            $messages["body-text"] = "Please review the comment and provide a response if necessary.";
            $messages["url-title"] = "View Ticket";
            $messages["url"] = "/edit/ticket/" .$validate['id'];
            $assignedUsers= TicketAssigns::join('users', 'ticket_assigns.user_id', '=', 'users.id')->where('ticket_id',$validate['id'])->get(['ticket_assigns.*','users.first_name','users.email']);
            foreach ($assignedUsers as $assignedUser) {
                $assignedUser->notify(new EmailNotification($messages));    
            }
            
        }


        $CommentsData = TicketComments::with('user')->where('id',$ticket->id)->get();
        return Response()->json(['status'=>200,'CommentsData' => $CommentsData,'Commentmessage' => 'Comments added successfully.']); 
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

}