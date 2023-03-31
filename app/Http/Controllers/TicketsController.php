<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;
use App\Models\Tickets;
use App\Models\TicketAssigns;
use App\Models\TicketComments;
use App\Models\TicketFiles;


use Illuminate\Support\Facades\Redirect;

class TicketsController extends Controller
{
    public function index()
    {
        $user = Users::where('users.role_id','!=',env('SUPER_ADMIN'))->where('status','!=',0)->orderBy('id','desc')->get();	
        $tickets=Tickets::orderBy('id','desc')->get(); 
        if (!empty($tickets)){
        foreach ($tickets as $key=>$data) 
        {
            $ticketAssigns= TicketAssigns::join('users', 'ticket_assigns.user_id', '=', 'users.id')->where('ticket_id',$data->id)->orderBy('id','desc')->get(['ticket_assigns.*','users.first_name', 'users.profile_picture']);
            $tickets[$key]->ticketassign = !empty($ticketAssigns)? $ticketAssigns:null;
        }}
       
        return view('tickets.index',compact('user','tickets'));   
    }
    public function store(Request $request) 
	{ 
        $validator = \Validator::make($request->all(),[
            'title' => 'required',
            'description'=>'required',
            // 'assign'=>'required',
            // 'eta_to' => 'required',
             'status'=>'required', 
             'priority'=>'required',
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
                'status'=>$validate ['status'],
                'priority'=>$validate ['priority'],
                'eta'=>$eta,
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
                $name = time().rand(1,100).'.'.$file->extension();
                $file->move(public_path('assets/img/ticketAssets'), $name);  
                $path='ticketAssets/'.$name;
                    $documents = TicketFiles::create([
                    'document' => $path,
                    'ticket_id'=> $tickets->id,
                    ]); 
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
         $user = Users::where('users.role_id','!=',env('SUPER_ADMIN'))->orderBy('id','desc')->get()->toArray();	
         $userCount = Users::orderBy('id','desc')->where('status','!=',0)->get();
        foreach($user as $key1=> $data1)
        {
            foreach($ticketsAssign as $key2=> $data2){
                if($data1['id']==$data2['user_id']){
                    unset($user[$key1]);
                }
            }
        }
        $TicketDocuments=TicketFiles::orderBy('id','desc')->get();
        $tickets = Tickets::where(['id' => $ticketId])->first();
     
        $ticketAssign = TicketAssigns::with('user')->where('ticket_id',$ticketId)->get();
        $CommentsData=TicketComments::with('user')->orderBy('id','Asc')->where(['ticket_id' => $ticketId])->get();  //database query
        return view('tickets.edit',compact('tickets','ticketAssign','user','CommentsData' ,'userCount','TicketDocuments'));   	
     }     
     public function updateTicket( Request $request ,$ticketId)
     {
        $validator = \Validator::make($request->all(),[
            'title' => 'required', 
            'description'=>'required', 
             'status'=>'required',
             'priority'=>'required',
            ]);
            if ($validator->fails())
            {
                return Redirect::back()->withErrors($validator);
            }
           $validate = $validator->valid();
           
         $tickets=   Tickets::where('id', $ticketId)  
            ->update([
            'title' => $validate['title'],        
            'description' => $validate['description'],
            'status' => $validate['status'],
            'priority' => $validate['priority'],
            'eta'=>$request['eta'],
            ]);

            if (isset($request->assign))
            {				
                foreach($request->assign as $data)
                {				
                    $data =TicketAssigns::create([					
                        'ticket_id' => $ticketId,
                        'user_id' => $data,
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
        $validator = \Validator::make($request->all(),[
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
        $CommentsData = TicketComments::with('user')->where('id',$ticket->id)->get();
        return Response()->json(['status'=>200,'CommentsData' => $CommentsData,'Commentmessage' => 'Comments added successfully.']); 
    }
    public function DeleteTicketAssign(request $request)
    {
        $ticketAssign = TicketAssigns::where('id',$request->id)->delete();
        $request->session()->flash('message','TicketAssign deleted successfully.');
        $AssignData = TicketAssigns::where(['ticket_id' => $request->TicketId])->get();
        
        $user = Users::where('users.role_id','!=',env('SUPER_ADMIN'))->orderBy('id','desc')->get()->toArray();	
    
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
}