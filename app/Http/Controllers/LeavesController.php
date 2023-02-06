<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\UserLeaves;
use Carbon\Carbon;
use App\Http\Requests\StoreUserLeaves;

class LeavesController extends Controller
{
    public function index()
    {
        $leavesData = UserLeaves::orderBy('id','desc')->get();
        return view('leaves.index',compact('leavesData'));  
    }  
    public function store(StoreUserLeaves $request)
    {
        $validated = $request->validate([
            'from' => 'required',
            'to' => 'required'
        ]);
        //dd($validated);      
        $validator = \Validator::make($request->all());

        if ($validator->fails()) {
        
           return response()->json(['errors'=>$validator->errors()->all()]);
        }
    
        $userLeaves=UserLeaves::create([     
            'user_id'=> auth()->user()->id,     
            'from'=>$request->from,
            'to'=>$request->to,
            'type'=>$request->type,
            'notes'=>$request->notes,
           ]);
           
           $request->session()->flash('message','Leaves added successfully.');
           return Response()->json(['status'=>200, 'leaves'=>$userLeaves]);
    }
}