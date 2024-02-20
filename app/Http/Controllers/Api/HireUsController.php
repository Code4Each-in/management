<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\HireUsNotification;
use App\Mail\HireUsThankyouMail;
use App\Models\Captchas;
use App\Models\HireUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class HireUsController extends Controller
{
    public function store(Request $request){
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|digits_between:10,10',
            'message' => 'required',
            'captcha' => 'required',
        ], [
            'name.required' => 'The Name field is required.',
            'email.required' => 'The Email field is required.',
            'email.email' => 'Please enter a valid Email address.',
            'phone.required' => 'The Phone field is required.',
            'message.required' => 'The message  field is required.',
            'captcha.required' => 'The Captcha field is required.'

        ]);

        $validator->after(function ($validator) use ($request) {
            $captcha = $request->input('captcha');

            if (!empty($captcha)) {
                $getcaptcha = Captchas::where('captcha_id', $request->input('captcha_id'))->first();

                if (!$getcaptcha || $getcaptcha->captcha_string != $captcha) {
                    $validator->errors()->add('captcha', 'You entered an incorrect Captcha.');
                }
            }
        });
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->toArray()]);
        }
        $validate = $validator->valid();

        $hire =HireUs::create([
            'name' => $validate['name'],
            'email' => $validate['email'],
            'phone' => $validate['phone'],
            'message' => $validate['message'],
            'skill' => $request->skill,
            'status'=>'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->sendEmailToClient($hire);
        $this->sendEmailToAdmin($hire);
        return Response()->json(['status'=>200, 'message'=>'Thank you! Your query has been submitted succesfully']);
    }

    public function sendEmailToClient($dt){
        $data = $dt;
        $subject = "Thank You for Your Interest in Our Tech Services!";
        $data->subject = $subject;
        Mail::to($dt->email)->send(new HireUsThankyouMail($data));
    }
    public function sendEmailToAdmin($dt){
        $data = $dt;
        $subject = "New Client Inquiry";
        $data->subject = $subject;
        Mail::to(array("info@code4each.com", "hr@code4each.com"))->send(new HireUsNotification($data));

    }
}
