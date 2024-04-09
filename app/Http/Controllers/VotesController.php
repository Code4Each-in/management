<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Votes;

class VotesController extends Controller
{
    public function index()
    {
        // .
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

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }
        $validatedData = $validator->validated();
        // Get the current month and year
        $currentMonth = date('n');
        $currentYear = date('Y');

        // Check if the user has already voted in the current month and year
        $existingVote = Votes::where('from', $request->from)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->exists();

        if ($existingVote) {
            $request->session()->flash('error', 'You have already voted.');
            return response()->json(['error' => 'You have already voted.']);
        }

        // Insert the vote data into the database
        Votes::create([
            'from' => $validatedData['from'],
            'to' => $validatedData['to'],
            'month' => $currentMonth,
            'year' => $currentYear,
            'notes' => $validatedData['notes'],
        ]);

        $request->session()->flash('message', 'Vote submitted successfully.');
        return response()->json(['message' => 'Vote submitted successfully.']);
    }
}
