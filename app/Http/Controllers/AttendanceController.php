<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAttendances;
use Illuminate\Support\Facades\Redirect;

class AttendanceController extends Controller
{
    
    public function index()
    {
        $attendanceData= UserAttendances::orderBy('id','desc')->get();
        return view('attendance.index',compact('attendanceData'));   
    }
    
    public function store(Request $request)
	{	
        $validator = \Validator::make($request->all(),[
			'intime'=>'required', 
            'outtime'=>'required|after:intime', 
        ],
        [
            'outtime.after' => 'The outtime must be greater than from intime.',
        ]
    );

        if ($validator->fails())
        {
            return Redirect::back()->withErrors($validator);
        }  
         $validate = $validator->valid();	
           $users =UserAttendances::create([     
            'user_id'=> auth()->user()->id,     
            'in_time'=>$validate['intime'],
            'out_time'=>$validate['outtime'],
            'notes'=>$validate['notes'],
       
           ]);
        $request->session()->flash('message','Attendance added successfully.');
        return redirect()->intended('attendance');
    }
}