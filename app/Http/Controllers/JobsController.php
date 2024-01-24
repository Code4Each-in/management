<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jobs;
use App\Models\JobCategories;

class JobsController extends Controller
{
    public function index()
    {
        $jobsData = Jobs::with('jobcategory')->orderBy('id','desc')->get();
        $jobCategories = JobCategories::orderBy('id', 'asc')->where('status', 1)->get();
        return view('jobs.index',compact('jobsData', 'jobCategories'));  

    }  
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'title' => 'required',    
            'description' => 'required',    
            'experience' => 'required',    
            'job_category_id' => 'required',    
            'location' => 'required',    
            'status' => 'required',    
            'salary' => 'nullable',   
            'skills' => 'required'
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }

        $title = $request->get('title');
        $description = $request->get('description');
        $experience = $request->get('experience');
        $job_category_id = $request->get('job_category_id');
        $location = $request->get('location');
        $status = $request->get('status');
        $salary = $request->get('salary');
        $skills = $request->get('skills');


        $addJob = Jobs::create([
            'title' => $title,
            'description' => $description,
            'experience' => $experience,
            'job_category_id' => $job_category_id,
            'status' => $status,
            'location' => $location,
            'salary' => $salary,
            'skills' => $skills,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
		$request->session()->flash('message','Job added successfully.');
        return Response()->json(['status'=>200, 'page'=>$addJob]);
    }


    public function destroy(Request $request)
    {
        $deleteJobs = Jobs::find($request->id);

        if (!$deleteJobs) {
            return response()->json(['error' => 'Job not found.'], 404);
        }

        $deleteJobs->delete();
        $request->session()->flash('message','Job deleted successfully.');
        return response()->json(['message' => 'Job deleted successfully.']);
    }

    public function edit(Request $request)
    {
        $getJobData = Jobs::where(['id' => $request->id])->first();
        return Response()->json(['jobs' =>$getJobData]);
    }

    public function update(Request $request)
    {  
        $validator = \Validator::make($request->all(), [
            'edit_title' => 'required',    
            'edit_description' => 'required',    
            'edit_experience' => 'required',    
            'edit_job_category_id' => 'required',    
            'edit_location' => 'required',    
            'edit_status' => 'required',    
            'edit_salary' => 'nullable',   
            'edit_skills' => 'required'
        ]);
 
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }
        $updateJobs = Jobs::where('id', $request->id)
        ->update([
            'title' => $request->edit_title,
            'description' => $request->edit_description,
            'experience' => $request->edit_experience,
            'job_category_id' => $request->edit_job_category_id,
            'status' => $request->edit_status,
            'location' => $request->edit_location,
            'salary' => $request->edit_salary,
            'skills' => $request->edit_skills,
        ]);

		$request->session()->flash('message','Job updated successfully.');
        return Response()->json(['status'=>200, 'device' => $updateJobs]);
    }
}
