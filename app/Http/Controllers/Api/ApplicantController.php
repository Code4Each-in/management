<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\JobNotification;
use App\Mail\ThankyouMail;
use Illuminate\Http\Request;
use App\Models\Applicants;
use App\Mail\VerificationMail;
use App\Models\Jobs;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;


class ApplicantController extends Controller
{
    public function store(Request $request){
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|digits_between:10,10',
            'job_id' => 'required',
            'resume' => 'required|file|mimes:doc,docx,pdf|max:5000'
        ], [
            'name.required' => 'The Name field is required.',
            'email.required' => 'The Email field is required.',
            'email.email' => 'Please enter a valid Email address.',
            'phone.required' => 'The Phone field is required.',
            'job_id.required' => 'The Job  field is required.',
            'resume.required' => 'The Resume field is required.'
        ],[
            'resume.*.file' => 'The :attribute must be a file.',
            'resume.*.mimes' => 'The :attribute must be a file of type: doc, pdf.',
            'resume.*.max' => 'The :attribute may not be greater than :max kilobytes.',
            'resume.*.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',
            'resume.mimes' => 'The Resume must be a file of type: doc, docx, pdf.',

        ]);
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->toArray()]);
        }
        $validate = $validator->valid();
        // $ifApplicantExist = Applicants::where('email', $request->email)
        //             ->count();
        // if($ifApplicantExist==0 ){
            $applicant = Applicants::create([
                'name' => $validate['name'],
                'email' => $validate['email'],
                'phone' => $validate['phone'],
                'links' => $request->links,
                'job_id'=> $validate['job_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'application_status'=>'pending'
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
        // }
        // else{
        //     $applicant = Applicants::where('email', $request->email)
        //     ->first();
        //     $applicant->update(['phone' => $validate['phone']]);
        //     $applicant->update(['name' => $validate['name']]);
        //     $applicant->update(['links' => $validate['links']]);
        //     $applicant->update(['links' => $validate['links']]);
        // }

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
            $message='We have sent an OTP to your email: <strong>' . $email . '</strong>. Please confirm the OTP. Note that it is valid for only five minutes.';

            return Response()->json(['status'=>200, 'message'=>$message,'email'=>$email]);
       }
       else{
            return Response()->json(['errors'=>['Error In Submitting Your Application']]);
       }

    }

    public function update(Request $request){
        $validator = \Validator::make($request->all(), [
            'otp' => 'required',
        ], [
            'otp.required' => 'Please enter an otp.'

        ]);
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }

        $email = $request->input('email');
        $otp = $request->input('otp');
        $applicant = Applicants::where('email', $email)
                    ->where('expired_at', '>', Carbon::now())
                    ->where('status', 0)
                    ->latest()
                    ->first();
        $jobName=Jobs::select('title')
        ->where('id',$applicant->job_id)
        ->first();
         if($applicant && $applicant->count()>0){
            if($otp==$applicant->otp){
                $applicant->update(['status' => 1]);
                $this->sendThankyouEmail($applicant,$jobName->title);
                $this->sendJobNotificatonEmail($applicant,$jobName->title);
                return Response()->json(['status'=>200, 'message'=>'Thank you. Your application has been submitted successfully.We will contact you soon']);
            }
            else{

                return Response()->json(['errors'=>['Invalid OTP. Please try again.']]);
            }
         }
    }
    protected function sendThankyouEmail($applicant_data,$title)
    {
        $subject ='Job Application';
        $data = ['applicant' => $applicant_data,'title' => $title,'subject'=>$subject];
        Mail::to($applicant_data->email)->send(new ThankyouMail($data));
    }

    protected function sendJobNotificatonEmail($applicant_data,$title)
    {
        $hr_email='kalyani@code4each.com';
        $subject ='New Job Application';
        $data = ['applicant' => $applicant_data,'title' => $title,'subject'=>$subject];
        Mail::to($hr_email)->send(new JobNotification($data));
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
        ->latest()
        ->first();;

        if($applicant && $applicant->count()>0){
            $otp = rand(1000, 9999); // Generate a 4-digit OTP
            $currentDateTime = Carbon::now();
            $expired_at=$currentDateTime->addMinutes(5)->toDateTimeString();
            //update otp and expired time in applicants
            $applicant->update(['otp' => $otp]);
            $applicant->update(['expired_at' => $expired_at]);
            $subject = "OTP verification";
            $this->sendVerificationEmail($otp,$email,$subject);
            $message='We have sent an OTP to your email : <strong>' . $email . '</strong>. Please confirm the OTP. Note that it is valid for only 5 minutes.';
            return Response()->json(['status'=>200, 'message'=>$message,'email'=>$email]);
        }

    }
}
