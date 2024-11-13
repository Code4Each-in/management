<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAttendances;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserAttendancesTemporary;
use Carbon\Carbon;

class InternalTimesheetExtension extends Controller
{
    public function validateUser(Request $request)
    {

        $response = [
            'success' => false,
            'status'  => 400,
        ];

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!$credentials) {
            return response()->json(['errors' => 'Validation failed. Please check your inputs.']);
        }


        if (Auth::attempt($credentials)) {
            $user = auth()->user();
            $userDetails = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->first_name. " ".$user->last_name,
                "email"     => $user->email,
            ];

            $response = [
                "message" => "User Authenticated Successfully.",
                "user" => $userDetails,
                'success' => true,
                'status'  => 200,
            ];
        }else{
            $response = [
                "message" => "Failed User Authentication",
            ] ;
        }

        return response()->json($response);

    }


    public function addStatusReport(Request $request)
    {

        $response = [
            'success' => false,
            'status'  => 400,
        ];

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'note' => 'nullable|string',
        ]);
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $validate = $validator->validate();

        if (Users::where('id', $validate['user_id'])->exists()) {
            $attendance_data = UserAttendancesTemporary::where('user_id',$validate['user_id'])
            ->whereNull('out_time_date')
            ->latest()
            ->first(['id', 'in_time', 'date', 'out_time_date']);

            $currentDateTime = now();
            $attendance_data->update([
                'out_time_date' => $currentDateTime,
            ]);
            $currentTime = $currentDateTime->format('H:i:s');
            $attendance = UserAttendances::updateOrCreate(
                [
                    'user_id' => $validate['user_id'],
                    'date' => $attendance_data->date
                ],
                [
                    'in_time' => $attendance_data->in_time,
                    'out_time' => $currentTime,
                    'notes' => $validate['note'],
                    'out_time_date' => now()
                ]
            );

            $attendance->update([
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if($attendance){
                    $response = [
                        'message' => "Status Report Added Successfully.",
                        'success' => true,
                        'status'  => 200,
                    ];
            }else{
                $response = [
                    'message' => "Error In Adding Status Report",
                ];
            }

         }else{
            $response = [
                'message' => "Invalid User",
            ];
         }

        return response()->json($response);
    }

    public function addStartTime(Request $request)
    {
        $response = [
            'success' => false,
            'status'  => 400,
        ];

        $validator = Validator::make($request->all(), [
            'user_id' => 'required'
        ]);
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $validate = $validator->validate();

        if (Users::where('id', $validate['user_id'])->exists()) {
            $currentDateTime = now();
            $currentTime = $currentDateTime->format('H:i:s');
            $attendance = UserAttendancesTemporary::updateOrCreate(
                [
                    'user_id' => $validate['user_id'],
                    'date' =>$currentDateTime->toDateString(),
                    'in_time' => $currentTime
                ]
            );

            $attendance->update([
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if($attendance){
                    $response = [
                        'message' => "Start Time Added Successfully.",
                        'success' => true,
                        'status'  => 200,
                    ];
            }else{
                $response = [
                    'message' => "Error In Adding Status Report",
                ];
            }

         }else{
            $response = [
                'message' => "Invalid User",
            ];
         }

        return response()->json($response);
    }

    public function getStartTime(Request $request)
    {
        $response = [
            'success' => false,
            'status'  => 400,
        ];

        $validator = Validator::make($request->all(), [
            'user_id' => 'required'
        ]);
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $validate = $validator->validate();
        if (Users::where('id', $validate['user_id'])->exists()) {
            $attendance_data = UserAttendancesTemporary::where('user_id',$validate['user_id'])
            // ->whereDate('date', now()->toDateString())
            // ->whereNull('out_time_date')
            ->latest()
            ->first(['in_time', 'out_time_date', 'date']);
            if($attendance_data && is_null($attendance_data->out_time_date)){
                $current_time = Carbon::now();
                // Combine date and time fields to create Carbon instances
                $attendance_datetime = Carbon::createFromFormat('Y-m-d H:i:s', "$attendance_data->date $attendance_data->in_time");
                
                // Calculate the difference
                $diffInSeconds = $attendance_datetime->diffInSeconds($current_time);
                
                // Convert seconds to hours, minutes, and seconds
                $hours = floor($diffInSeconds / 3600);
                $minutes = floor(($diffInSeconds % 3600) / 60);
                $seconds = $diffInSeconds % 60;
                // Format as HH:MM:SS
                $timeDifference = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                $response = [
                    'message' => "Start Time Get Successfully.",
                    'success' => true,
                    'status'  => 200,
                    'data' => ['inTime'=> $attendance_data->in_time, 'timeSpend'=> $timeDifference]
                ];
            }else{
                $response = [
                    'message' => "Error In Getting Start Time",
                ];
            }

         }else{
            $response = [
                'message' => "Invalid User",
            ];
         }

        return response()->json($response);
    }
}
