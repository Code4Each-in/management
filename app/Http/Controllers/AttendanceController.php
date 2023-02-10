<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAttendances;
use Illuminate\Support\Facades\Redirect;

class AttendanceController extends Controller
{
    
    public function index()
    {
        $attendanceData= UserAttendances::where('user_id',auth()->user()->id)->orderBy('id','desc')->get();
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
    public function showTeamsAttendance()
    {

        $teamAttendance = UserAttendances::join('managers', 'user_attendances.user_id', '=', 'managers.user_id')->join('users', 'user_attendances.user_id', '=', 'users.id')->where('managers.parent_user_id',auth()->user()->id)->get(['user_attendances.*', 'managers.user_id','users.*']);
        
        return view('attendance.team',compact('teamAttendance'));
    }
}