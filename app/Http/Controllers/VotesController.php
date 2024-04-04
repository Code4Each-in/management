<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users; 
use App\Models\Votes; 
class VotesController extends Controller
{
    public function index()
    {
        //
    }
    public function SubmitVote(Request $request)
    {
        // Validate the request data
        $validator = \Validator::make($request->all(), [
            'from' => 'required|integer',
            'to' => 'required|integer',
            'month' => 'required|integer',
            'year' => 'required|integer',
            'notes' => 'required|string',
        ]);
        if ($validator->fails())
		{
			return response()->json(['errors'=>$validator->errors()->all()]);
		}

        // Insert the vote data into the database
        Votes::create([
            'from' => $request->from,
            'to' => $request->to,
            'month' => $request->month,
            'year' => $request->year,
            'notes' => $request->notes,
        ]);
        $request->session()->flash('message','Vote submitted successfully.');
        return response()->json(['message' => 'Vote submitted successfully']);
    }
}