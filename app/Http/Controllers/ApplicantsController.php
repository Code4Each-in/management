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
        $applicant_status=Applicants::where('id',$request->applicant_id)
        ->update([
        'application_status' =>$request->status,
        'note'=>$request->notes
         ]);

         $applicants=Applicants::join('jobs', 'jobs.id', '=', 'applicants.job_id')
         ->select('jobs.*','applicants.*','jobs.id as job_id')
         ->where('applicants.id',$request->applicant_id)->first();
         $data = $applicants;
            $subject = "Application Status";
            $data->subject = $subject;
            $userEmail = $applicants->email;
            if($applicant_status > 0 && ($request->status=='rejected' || $request->status=='selected')){
                Mail::to($userEmail)->send(new ApplicationStatus($data));
             }

			 $request->session()->flash('message', 'Applicant status updated' );

             return response()->json(['status'=>200]);
    }

}
