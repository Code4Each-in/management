<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAttendances;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserAttendancesTemporary;

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
            $inTime = UserAttendancesTemporary::where('user_id',$validate['user_id'])
            ->whereDate('date', now()->toDateString())
            ->latest()
            ->value('in_time');
            $currentDateTime = now();
            $currentTime = $currentDateTime->format('H:i:s');
            $attendance = UserAttendances::updateOrCreate(
                [
                    'user_id' => $validate['user_id'],
                    'date' => $currentDateTime->toDateString(),
                ],
                [
                    'in_time' => $inTime,
                    'out_time' => $currentTime,
                    'notes' => $validate['note'],
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
            $inTime = UserAttendancesTemporary::where('user_id',$validate['user_id'])
            ->whereDate('date', now()->toDateString())
            ->latest()
            ->value('in_time');
            if($inTime){
                    $response = [
                        'message' => "Start Time Get Successfully.",
                        'success' => true,
                        'status'  => 200,
                        'data' => ['inTime'=> $inTime]
                    ];
            }else{
                $response = [
                    'message' => "Error In Adding Start Time",
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
