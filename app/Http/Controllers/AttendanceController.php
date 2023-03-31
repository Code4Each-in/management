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
         $attendanceCheck= UserAttendances::where('user_id',auth()->user()->id)->whereDate('created_at',date('Y-m-d'))->first();
   
      $attendenceData=[
       'user_id'=> auth()->user()->id,     
       'in_time'=>$validate['intime'],
        'out_time'=>$validate['outtime'],
        'notes'=>$validate['notes']
            ];
     if (!empty($attendanceCheck))
       {
         $attendenceData['updated_at']=date('Y-m-d H:i:s');
           UserAttendances::where('id', $attendanceCheck->id)
           ->update($attendenceData);
        }
        else
         {
           $attendenceData['created_at']=date('Y-m-d H:i:s');
           $users =UserAttendances::create($attendenceData); 
         }
        $request->session()->flash('message','Attendance added successfully.');
              return redirect()->intended('attendance');
          }

          public function showTeamsAttendance()
          {
            if (auth()->user()->role_id==env('SUPER_ADMIN'))
              {
                $teamAttendance= UserAttendances::join('users', 'user_attendances.user_id', '=', 'users.id')->orderBy('id','desc')->get(['user_attendances.*','users.first_name']);;
              }
            else
            {
            $teamAttendance = UserAttendances::join('managers', 'user_attendances.user_id', '=', 'managers.user_id')->join('users', 'user_attendances.user_id', '=', 'users.id')->where('managers.parent_user_id',auth()->user()->id)->get(['user_attendances.*', 'managers.user_id','users.first_name']);
            }
            return view('attendance.team',compact('teamAttendance'));
        }


        public function edit(request $request){
          $attendance = UserAttendances::where(['id' => $request->id])->first();
          return Response()->json(['attendance' =>$attendance]);
         }
         
          public function update(request $request){
          $validator = \Validator::make($request->all(),[
          'edit_intime'=>'required', 
          'edt_outtime'=>'required|after:edit_intime',
          ],
                [
                  'edt_outtime.after' => 'The outtime must be greater than from intime.',
                ]
              );
              if ($validator->fails())
              {
                  return Redirect::back()->withErrors($validator);
              } 
               
                $validate = $validator->valid();  
                
              $UserAttendance =  UserAttendances::where('id', $validate['id'])
                ->update([
               'in_time'=>$validate['InTime'],
               'out_time'=>$validate['outTime'],
               'notes'=>$validate['notes'],
                ]);
               $request->session()->flash('message','Attendances updated successfully.');
              return Response()->json(['UserAttendance' => $UserAttendance]);
        }
    }
    