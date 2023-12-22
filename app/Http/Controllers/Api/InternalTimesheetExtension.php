<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAttendances;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
            'in_time' => 'required',
            'out_time' => 'required',
            'note' => 'nullable|string',
        ]);
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $validate = $validator->validate();

        if (Users::where('id', $validate['user_id'])->exists()) {
            $attendance = UserAttendances::updateOrCreate(
                [
                    'user_id' => $validate['user_id'],
                    'date' => now()->toDateString(),
                ],
                [
                    'in_time' => $validate['in_time'],
                    'out_time' => $validate['out_time'],
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
}
