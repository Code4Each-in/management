<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Applicants;
use App\Mail\VerificationMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;


class ApplicantController extends Controller
{
    public function store(Request $request){
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'resume.*' => 'file|mimes:doc,docx,pdf|max:5000'
        ], [
            'name.required' => 'The Name field is required.',
            'email.required' => 'The Email field is required.',
            'email.email' => 'Please enter a valid Email address.',
            'phone.required' => 'The Phone field is required.',
            'resume.required' => 'The Phone field is required.'
        ],[
            'resume.*.file' => 'The :attribute must be a file.',
            'resume.*.mimes' => 'The :attribute must be a file of type: doc, pdf.',
            'resume.*.max' => 'The :attribute may not be greater than :max kilobytes.',
            'resume.*.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',

        ]);
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $validate = $validator->valid();
        $ifApplicantExist = Applicants::where('email', $request->email)
                    ->count();
        if($ifApplicantExist==0 ){
            $applicant = Applicants::create([
                'name' => $validate['name'],
                'email' => $validate['email'],
                'phone' => $validate['phone'],
                'links' => $request->links,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if($request->hasfile('resume')){

                $file=$request->file('resume');
                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $dateString = date('YmdHis');
                $name = $dateString . '_' . $fileName . '.' . $file->extension();
                $file->move(public_path('assets/docs/ApplicantResume'), $name);
                $path='ApplicantResume/'.$name;
                $applicant->update(['resume' => $path]);

           }
        }
        else{
            $applicant = Applicants::where('email', $request->email)
            ->first();
        }

         //OTP generate
         $otp = rand(1000, 9999); // Generate a 4-digit OTP
         $currentDateTime = Carbon::now();
         $expired_at=$currentDateTime->addMinutes(5)->toDateTimeString();
         //update otp and expired time in applicants
         $applicant->update(['otp' => $otp]);
         $applicant->update(['expired_at' => $expired_at]);
         if($applicant){
            $email=$validate['email'];
            $subject = "OTP verification";
            $this->sendVerificationEmail($otp,$email,$subject);
            $message='We have sent an OTP to your email. Please confirm the OTP. Note that it is valid for only five minutes.';

            return Response()->json(['status'=>200, 'message'=>$message,'email'=>$email]);
       }
       else{
            return Response()->json(['message'=>'Error In Submitting Your Application']);
       }

    }

    public function update(Request $request){
        $email = $request->input('email');
        $otp = $request->input('otp');
        $applicant = Applicants::where('email', $email)
                    ->where('expired_at', '>', Carbon::now())
                    ->where('status', 0)
                    ->first();
         if($applicant && $applicant->count()>0){
            if($otp==$applicant->otp){
                $applicant->update(['status' => 1]);
                return Response()->json(['status'=>200, 'message'=>'Thank you. Your application has been submitted successfully.We will contact you soon']);
            }
            else{
                return Response()->json(['message'=>'Invalid OTP. Please try again.']);
            }
         }
    }

    protected function sendVerificationEmail($otp,$email,$subject)
    {
        $data = ['otp' => $otp,'subject'=>$subject];
        Mail::to($email)->send(new VerificationMail($data));
    }

    public function resentOtp(Request $request){
        $email=$request->email;
        $applicant = Applicants::where('email', $email)
        ->where('status', 0)
        ->first();

        if($applicant && $applicant->count()>0){
            $otp = rand(1000, 9999); // Generate a 4-digit OTP
            $currentDateTime = Carbon::now();
            $expired_at=$currentDateTime->addMinutes(5)->toDateTimeString();
            //update otp and expired time in applicants
            $applicant->update(['otp' => $otp]);
            $applicant->update(['expired_at' => $expired_at]);
            $subject = "OTP verification";
            $this->sendVerificationEmail($otp,$email,$subject);
            $message='We have sent an OTP to your email. Please confirm the OTP. Note that it is valid for only five minutes.';
            return Response()->json(['status'=>200, 'message'=>$message,'email'=>$email]);
        }

    }
}
