<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAttendances;
use App\Models\Users;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;

class AttendanceController extends Controller
{

    public function index(Request $request)
    {
      $validator = \Validator::make($request->all(),[
        'date_from'=>'required_with:date_to|date|nullable',
        'date_to'=>'required_with:date_from|date|nullable',
          ],
        [
            'date_from.required_with' => 'The date from field is required.',
            'date_to.required_with' => 'The date to field is required.',
        ]
        );
      if ($validator->fails())
       {
           return Redirect::back()->withErrors($validator);
        }
        $attendanceData= UserAttendances::where('user_id',auth()->user()->id)
        ->orderBy('created_at','desc')
        ->when($request->has('intervals_filter'), function ($query) use ($request) {
          if($request->input('intervals_filter') == 'last_week'){
          return $query->whereRaw('date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)');
          }
          if($request->input('intervals_filter') == 'last_month'){
          return $query->whereRaw('date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)');
          }
          if($request->input('intervals_filter') == 'yesterday'){
            return $query->whereRaw('DATE(date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
            }
            if($request->input('intervals_filter') == 'custom_intervals'){
             return $query->whereBetween('date', [$request->get('date_from'), $request->get('date_to')]);
              }
         }, function ($query) {
          return $query->whereRaw('date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)');
      })->get();

        return view('attendance.index',compact('attendanceData'));
    }

    // public function store(Request $request)
	//   {
    //     $validator = \Validator::make($request->all(),[
	// 		'intime'=>'required',
    //         'outtime'=>'required|after:intime',
    //     ],
    //     [
    //         'outtime.after' => 'The outtime must be greater than from intime.',
    //     ]
    //   );
    // if ($validator->fails())
    //  {
    //      return Redirect::back()->withErrors($validator);
    //   }
    //      $validate = $validator->valid();
    //      $attendanceCheck= UserAttendances::where('user_id',auth()->user()->id)->whereDate('created_at',date('Y-m-d'))->first();

    //   $attendenceData=[
    //    'user_id'=> auth()->user()->id,
    //    'in_time'=>$validate['intime'],
    //     'out_time'=>$validate['outtime'],
    //     'notes'=>$validate['notes']
    //         ];
    //  if (!empty($attendanceCheck))
    //    {
    //      $attendenceData['updated_at']=date('Y-m-d H:i:s');
    //        UserAttendances::where('id', $attendanceCheck->id)
    //        ->update($attendenceData);
    //     }
    //     else
    //      {
    //        $attendenceData['created_at']=date('Y-m-d H:i:s');
    //        $users =UserAttendances::create($attendenceData);
    //      }
    //     $request->session()->flash('message','Attendance added successfully.');
    //           return redirect()->intended('attendance');
    // }

    public function storeTeamAttendance(Request $request)
    {
        // dd($request);
      $validator = \Validator::make($request->all(),[
          'member_id'=>'required',
          'intime'=>'required',
          'att_date'=>'required',
          'outtime'=>'required|after:intime',
      ],
      [

          'outtime.after' => 'The outtime must be greater than from intime.',
      ]
    );
  if ($validator->fails())
   {
    //    return Redirect::back()->withErrors($validator);
    return response()->json(['errors'=>$validator->errors()->all()]);
    }
       $attendance_date=$request['att_date'].' ' .$request->intime . ':00';
       $validate = $validator->valid();
       $attendanceCheck= UserAttendances::where('user_id',$validate['member_id'])->whereDate('date',$request['att_date'])->first();

    $attendenceData=[
     'user_id'=> $validate['member_id'],
     'in_time'=>$validate['intime'],
      'out_time'=>$validate['outtime'],
      'notes'=>$validate['notes'],
      'date'=>$validate['att_date']
          ];
        //   $attendanceCheck->in_time = $validate['intime'];
        //   $attendanceCheck->save();
   if (!empty($attendanceCheck))
     {
    //    $attendenceData['updated_at']=$attendance_date;
         UserAttendances::where('id', $attendanceCheck->id)
         ->update($attendenceData);
      }
      else
       {

        //  $attendenceData['created_at']=date('Y-m-d H:i:s', strtotime($attendance_date));
         $users =UserAttendances::create($attendenceData);
       }
      $request->session()->flash('message','Attendance added successfully.');
            // return redirect()->intended('attendance');
            return Response()->json(['status'=>200]);
  }



          public function showTeamsAttendance(Request $request)
          {
            $users = Users::with('role')->whereRelation('role', 'name', '!=',  'Super Admin')->where('status',1)->get();
            if (auth()->user()->role->name == 'Super Admin')
              {
                $teamAttendance = UserAttendances::
                select('user_attendances.*', 'users.first_name')
                ->join('users', 'user_attendances.user_id', '=', 'users.id')
                ->when($request->has('team_member_filter'), function ($query) use ($request) {
                  $query->where('user_attendances.user_id', $request->get('team_member_filter'));
                })
                ->when($request->has('intervals_filter'), function ($query) use ($request) {
                  if ($request->input('intervals_filter') == 'last_week') {
                      return $query->whereRaw('user_attendances.date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)');
                  }
                  if ($request->input('intervals_filter') == 'last_month') {
                      return $query->whereRaw('user_attendances.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)');
                  }
                  if ($request->input('intervals_filter') == 'yesterday') {
                      return $query->whereRaw('DATE(user_attendances.date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
                  }
                  if ($request->input('intervals_filter') == 'today') {
                    $currentDate = Carbon::now()->toDateString();
                    return $query->whereDate('user_attendances.date', '=', $currentDate);
                }
                  if ($request->input('intervals_filter') == 'custom_intervals') {
                      return $query->whereBetween('user_attendances.date', [$request->get('date_from'), $request->get('date_to')]);
                  }
              }, function ($query) {
                  return $query->whereRaw('user_attendances.date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
              })
                ->orderByDesc('user_attendances.date')
                ->get();
              }
              // If Role Is HR Manager Fetch Data Accordingly
              elseif (auth()->user()->role->name == 'HR Manager') {
                $teamAttendance = UserAttendances::
                select('user_attendances.*', 'users.first_name')
                ->join('users', 'user_attendances.user_id', '=', 'users.id')
                ->where('user_attendances.user_id','!=', auth()->user()->id)
                ->when($request->has('team_member_filter'), function ($query) use ($request) {
                  $query->where('user_attendances.user_id', $request->get('team_member_filter'));
                })
                ->when($request->has('intervals_filter'), function ($query) use ($request) {
                  if ($request->input('intervals_filter') == 'last_week') {
                      return $query->whereRaw('user_attendances.date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)');
                  }
                  if ($request->input('intervals_filter') == 'last_month') {
                      return $query->whereRaw('user_attendances.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)');
                  }
                  if ($request->input('intervals_filter') == 'yesterday') {
                      return $query->whereRaw('DATE(user_attendances.date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
                  }
                  if ($request->input('intervals_filter') == 'today') {
                    $currentDate = Carbon::now()->toDateString();
                    return $query->whereDate('user_attendances.date', '=', $currentDate);
                }
                  if ($request->input('intervals_filter') == 'custom_intervals') {
                      return $query->whereBetween('user_attendances.date', [$request->get('date_from'), $request->get('date_to')]);
                  }
              }, function ($query) {
                  return $query->whereRaw('user_attendances.date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
              })
                ->orderByDesc('user_attendances.date')
                ->get();
              }
            else
            {
              $teamAttendance = UserAttendances::join('managers', 'user_attendances.user_id', '=', 'managers.user_id')
              ->join('users', 'user_attendances.user_id', '=', 'users.id')
              ->orderBy('created_at', 'desc')
              ->where('managers.parent_user_id', auth()->user()->id)
              ->when($request->has('team_member_filter'), function ($query) use ($request) {
                $query->where('user_attendances.user_id', $request->get('team_member_filter'));
              })
              ->when($request->has('intervals_filter'), function ($query) use ($request) {
                if ($request->input('intervals_filter') == 'last_week') {
                    return $query->whereRaw('user_attendances.date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)');
                }
                if ($request->input('intervals_filter') == 'last_month') {
                    return $query->whereRaw('user_attendances.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)');
                }
                if ($request->input('intervals_filter') == 'yesterday') {
                    return $query->whereRaw('DATE(user_attendances.date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
                }
                if ($request->input('intervals_filter') == 'today') {
                  $currentDate = Carbon::now()->toDateString();
                  return $query->whereDate('user_attendances.date', '=', $currentDate);
              }
                if ($request->input('intervals_filter') == 'custom_intervals') {
                    return $query->whereBetween('user_attendances.date', [$request->get('date_from'), $request->get('date_to')]);
                }
            }, function ($query) {
                return $query->whereRaw('user_attendances.date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
            })
              ->get(['user_attendances.*', 'managers.user_id', 'users.first_name']);
            }
            return view('attendance.team',compact('teamAttendance','users'));
        }


        public function edit(request $request){

          $attendance = UserAttendances::where(['id' => $request->id])->first();
          return Response()->json(['attendance' =>$attendance]);
         }

          public function update(request $request){
          $validator = \Validator::make($request->all(),[
          'edit_intime'=>'required',
          'edit_outtime'=>'required|after:edit_intime',
          ],
                [
                  'edit_outtime.after' => 'The outtime must be greater than from intime.',
                ]
              );

              if ($validator->fails())
              {
                  return Redirect::back()->withErrors($validator);
              }
                $validate = $validator->valid();
              $UserAttendance =  UserAttendances::where('id', $validate['id'])
                ->update([
               'in_time'=>$validate['edit_intime'],
               'out_time'=>$validate['edit_outtime'],
               'notes'=>$validate['notes'],
                ]);
               $request->session()->flash('message','Attendances updated successfully.');

              return Response()->json(['UserAttendance' => $UserAttendance]);
        }

        public function delete(Request $request){
            $Users = UserAttendances::where('id',$request->id)->delete();
		    $request->session()->flash('message','Attendance deleted successfully.');
		    return Response()->json(['status'=>200]);
        }
    }
