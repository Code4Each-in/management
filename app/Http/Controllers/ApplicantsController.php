<?php

namespace App\Http\Controllers;

use App\Models\Applicants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicationStatus;
class ApplicantsController extends Controller
{
    //
    public function index(){
        $applicants = Applicants::join('jobs', 'jobs.id', '=', 'applicants.job_id')
        ->select('jobs.*','applicants.*','jobs.id as job_id')
        ->where('applicants.status', 1)
        ->orderBy('applicants.id', 'desc')
        ->get();
        return view('applicants.index', compact('applicants'));
    }
    public function update_application_status(Request $request)
    {
        $applicant_status=Applicants::where('id',$request->applicantId)
        ->update([
        'application_status' =>$request->application_status
         ]);

         $applicants=Applicants::join('jobs', 'jobs.id', '=', 'applicants.job_id')
         ->select('jobs.*','applicants.*','jobs.id as job_id')
         ->where('applicants.id',$request->applicantId)->first();
         $data = $applicants;
            $subject = "Application Status - ".ucfirst($applicants->application_status);
            $data->subject = $subject;
            $userEmail = $applicants->email;
            if($applicant_status > 0 && $request->application_status=='rejected'){
                Mail::to($userEmail)->send(new ApplicationStatus($data));
             }

			 $request->session()->flash('message', 'Applicant status updated' );

             return response()->json(['status'=>200]);
    }

}
