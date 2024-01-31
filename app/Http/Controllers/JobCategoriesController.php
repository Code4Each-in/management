<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobCategories;


class JobCategoriesController extends Controller
{
    public function index()
    {
        $jobCategoriesData = JobCategories::orderBy('id','asc')->get();
        return view('jobCategories.index',compact('jobCategoriesData'));  

    }  

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'title' => 'required',    
            'status' => 'nullable',   
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }

        $title = $request->get('title');
        $status = $request->get('status');

        $jobCategories = JobCategories::create([
            'title' => $title,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
		$request->session()->flash('message','Job Category added successfully.');
        return Response()->json(['status'=>200, 'page'=>$jobCategories]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Devices  $devices
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $deleteJobCategories = JobCategories::find($request->id);

        if (!$deleteJobCategories) {
            return response()->json(['error' => 'Job category not found.'], 404);
        }

        $deleteJobCategories->delete();
        $request->session()->flash('message','Job category deleted successfully.');
        return response()->json(['message' => 'Job category deleted successfully.']);
    }
}
