<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;

class TicketsController extends Controller
{
    public function index()
    {
        $user = Users::where('users.role_id','!=',env('SUPER_ADMIN'))->orderBy('id','desc')->get();	
        return view('tickets.index',compact('user')); 
    }
    public function store(Request $request) 
	{
        $validator = \Validator::make($request->all(),[
            'title' => 'required', 
            'description'=>'required', 
            'assign'=>'required', 
            'status'=>'required', 
             'comment'=>'required',
    
            ]);
            if ($validator->fails())
            {
                return response()->json(['errors'=>$validator->errors()->all()]);
            }
            
    		$validate = $validator->valid(); //getting all data from db
          
            $tickets =Tickets::create([
                'title' => $validate['title'],
                'description' => $validate['description'], 
                'assign' => $validate['assign'],
                'status'=>$validate ['status'],
                'comment'=>$validate['comment'],   			
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    
    }
}