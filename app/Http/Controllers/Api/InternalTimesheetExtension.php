<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketComments;
use App\Models\UserAttendances;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserAttendancesTemporary;
use Carbon\Carbon;
use App\Models\CommentStatus;

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

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'validation_error' => $validator->errors()
            ]);
        }

        $validate = $validator->validated();

        // CHECK USER EXISTS
        if (!Users::where('id', $validate['user_id'])->exists()) {
            return response()->json([
                'error' => 'Invalid User'
            ]);
        }

        // CHECK ATTENDANCE
        $attendance_data = UserAttendancesTemporary::where('user_id', $validate['user_id'])
            ->whereNull('out_time_date')
            ->latest()
            ->first();

        if (!$attendance_data) {
            return response()->json([
                'error' => 'No active attendance found'
            ]);
        }

        // ACKNOWLEDGEMENT CHECK
        $pendingAcknowledgement = CommentStatus::whereIn('status', ['pending', 'replied'])
            ->whereIn('ticket_id', function ($q) use ($validate) {
                $q->select('ticket_id')
                ->from('ticket_assigns')
                ->where('user_id', $validate['user_id']);
            })
            ->exists();

        if ($pendingAcknowledgement) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'First complete all pending/replied acknowledgements before submitting the report.'
            ]);
        }

        //  UPDATE ATTENDANCE
        $attendance_data->update([
            'out_time_date' => now(),
        ]);

        $attendance = UserAttendances::updateOrCreate(
            [
                'user_id' => $validate['user_id'],
                'date' => $attendance_data->date
            ],
            [
                'in_time' => $attendance_data->in_time,
                'out_time' => now()->format('H:i:s'),
                'notes' => $validate['note'],
                'out_time_date' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Status Report Added Successfully'
        ]);
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
