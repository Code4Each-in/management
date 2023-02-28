<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;
use App\Models\Tickets;
use App\Models\TicketAssigns;

class TicketsController extends Controller
{
    public function index()
    {
        $user = Users::where('users.role_id','!=',env('SUPER_ADMIN'))->orderBy('id','desc')->get();	
        $tickets=Tickets::orderBy('id','desc')->get();  //database query
         
        return view('tickets.index',compact('user','tickets'));   
    }
    public function store(Request $request) 
	{ 
        $validator = \Validator::make($request->all(),[
            'title' => 'required', 
            'description'=>'required', 
            'assign'=>'required', 
            'status'=>'required', 
             'priority'=>'required'
            ]);

            if ($validator->fails())
            {
                return response()->json(['errors'=>$validator->errors()->all()]);
            }
            
    		$validate = $validator->valid();//getting all data from db
            $document = time().'.'.$validate['upload']->extension(); 
            $validate['upload']->move(public_path('assets/img/upload'), $document);
            $path ='upload/'.$document;

            $tickets =Tickets::create([
                'title' => $validate['title'],
                'description' => $validate['description'], 
                'status'=>$validate ['status'],
                'priority'=>$validate ['priority'],
                'upload'=> $path,
                'comment'=>$validate['comment'],   			
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
              
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
            $request->session()->flash('message','Tickets added successfully.');
    		return Response()->json(['status'=>200, 'tickets'=>$tickets]);
    }
    public function getTicketAssign(Request $request)
	 {
        $ticketAssigns= TicketAssigns::join('users', 'ticket_assigns.user_id', '=', 'users.id')->where('ticket_id',$request->id)->orderBy('id','desc')->get(['ticket_assigns.*','users.first_name', 'users.profile_picture']);
       
        return Response()->json(['status'=>200, 'ticketAssigns'=> $ticketAssigns]);
     }
     public function editTicketAssign(Request $request)
     {   
      
        $tickets = Tickets::where(['id' => $request->id])->first();
        $ticketAssign= TicketAssigns::where(['ticket_id'=> $request->id])->get();
		return Response()->json(['tickets' =>$tickets,'ticketAssign'=>$ticketAssign]);
     }     
}