<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
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

    public function fetch_all(){
        $job_category = DB::select('
        SELECT job_categories.id, job_categories.title, job_categories.image,COUNT(*) as count
        FROM jobs
        JOIN job_categories ON job_categories.id = jobs.job_category_id
        WHERE jobs.deleted_at IS NULL AND jobs.status=1
        GROUP BY job_categories.id, job_categories.title, job_categories.image;
    ');
        $jobs=Jobs::where('status',1)
        ->get();
        return Response()->json(['status'=>200, 'jobs_category' => $job_category,'jobs'=>$jobs]);
    }
    public function fetch_jobs(){

        $jobs=Jobs::all();
        return Response()->json(['status'=>200, 'jobs'=>$jobs]);
    }

    public function jobByCategory(Request $request){
        if($request->categoryId==0){
            $jobs = DB::select("
            SELECT jobs.title,job_categories.title as category,jobs.experience,jobs.id as jobId
            FROM jobs
            JOIN job_categories ON job_categories.id = jobs.job_category_id
            WHERE jobs.deleted_at IS NULL AND jobs.status=1 ORDER BY jobs.id DESC;
        ");
        }
        else{
            $jobs = DB::select("
            SELECT jobs.title,job_categories.title as category,jobs.experience,jobs.id as jobId
            FROM jobs
            JOIN job_categories ON job_categories.id = jobs.job_category_id
            WHERE jobs.deleted_at IS NULL AND jobs.status=1 AND jobs.job_category_id='+$request->categoryId+' ORDER BY jobs.id DESC;
        ");
        }

    return Response()->json(['status'=>200, 'jobs'=>$jobs]);
    }

    public function jobDescription(Request $request){
        // $jobs=Jobs::select('*')
        // ->where('id',$request->jobId)
        // ->first();
        $jobs = Jobs::select('*','jobs.title as jobTitle','job_categories.title as category','jobs.id as job_id' ,'jobs.updated_at as jobdate')
        ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
        ->where('jobs.id', $request->jobId)
        ->first();
        return Response()->json(['status'=>200, 'jobs'=>$jobs]);
    }
    public function show(Request $request){
        $jobs = Jobs::select('*','jobs.title as jobTitle','job_categories.title as category','jobs.id as job_id')
        ->join('job_categories', 'jobs.job_category_id', '=', 'job_categories.id')
        ->where('jobs.id', $request->id)
        ->first();
        return view('jobs.details',compact('jobs'));
    }
}
