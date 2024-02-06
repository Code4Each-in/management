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
            'category_img' => 'file|mimes:jpg,jpeg,png|max:5000',
        ],[
            'category_img.file' => 'The :attribute must be a file.',
            'category_img.mimes' => 'The :attribute must be a file of type: jpeg, png.',
            'category_img.max' => 'The :attribute may not be greater than :max kilobytes.',
            'category_img.max.file' => 'The :attribute failed to upload. Maximum file size allowed is :max kilobytes.',

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
        if($request->hasfile('category_img')){

            $file=$request->file('category_img');
            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $dateString = date('YmdHis');
            $name = $dateString . '_' . $fileName . '.' . $file->extension();
            $file->move(public_path('assets/img/jobsAssets'), $name);
            $path='jobsAssets/'.$name;
            $jobCategories->update(['image' => $path]);


       }
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
