<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use App\Models\WebsiteContactUs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactUsMail;
use App\Models\Captchas;

class ContactUsController extends Controller
{
    public function contactUs(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'note' => 'required',
            'phone' => 'required',
            'captcha' => 'required',
        ], [
            'name.required' => 'The Name field is required.',
            'email.required' => 'The Email field is required.',
            'email.email' => 'Please enter a valid Email address.',
            'note.required' => 'The Message field is required.',
            'phone.required' => 'The Phone field is required.',
            'captcha.required' => 'The Captcha field is required.',
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

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }


        $validate = $validator->valid();
        $websiteContactUs =WebsiteContactUs::create([
            'name' => $validate['name'],
            'email' => $validate['email'],
            'phone' => $validate['phone'],
            'message' => $validate['note'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $data = $websiteContactUs;
        $subject = "Contact us from Code4Each website";
        $data->subject = $subject;
        Mail::to(array("info@code4each.com", "hr@code4each.com"))->send(new ContactUsMail($data));

        return Response()->json(['status'=>200, 'contactus'=>$websiteContactUs]);
    }
}
