<?php

namespace App\Http\Controllers;

use App\Models\HireUs;
use Illuminate\Http\Request;

class HiringUsController extends Controller
{
    public function index(){
        $hire = HireUs::select('*')
        ->orderBy('id','desc')
        ->get();
        return view('hireus.index', compact('hire'));
    }

    public function edit(Request $request){
        $hire  = HireUs::where(['id' => $request->id])->first();
        return Response()->json(['data' =>$hire]);
    }

    public function update(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'edit_name' => 'required',
            'edit_email' => 'required|email',
            'edit_phone' => 'required',
            'edit_skill' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()]);
        }

        HireUs::where('id', $request->id)
        ->update([
            'name' => $request->edit_name,
            'email' => $request->edit_email,
            'phone' => $request->edit_phone,
            'skill' => $request->edit_skill,
            'status' => $request->status,
        ]);
		$request->session()->flash('message','Hire Us updated successfully.');
        return Response()->json(['status'=>200]);
    }

    public function delete(Request $request){
        $hire_us = HireUs::where('id',$request->id)->delete();
        $request->session()->flash('message','Hire US deleted successfully.');

		return Response()->json(['status'=>200]);
    }

}
