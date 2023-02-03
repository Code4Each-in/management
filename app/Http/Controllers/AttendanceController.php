<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAttendance;

class AttendanceController extends Controller
{
    
    public function index()
    {
        return view('attendance.index');   
    }
    
    public function store(Request $request )
	{	
        
// dd(auth()->user()->id);
           $users =UserAttendance::create([     
            'user_id'=> auth()->user()->id,     
            'in_time'=>$request->intime,
            'out_time'=>$request->outtime,
            'notes'=>$request->notes,
            
           ]);
        return redirect()->intended('attendance');
    }
}