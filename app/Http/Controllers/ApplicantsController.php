<?php

namespace App\Http\Controllers;

use App\Models\Applicants;
use Illuminate\Http\Request;

class ApplicantsController extends Controller
{
    //
    public function index(){
        $applicants = Applicants::join('jobs', 'jobs.id', '=', 'applicants.job_id')
        ->where('applicants.status', 1)
        ->orderBy('applicants.id', 'desc')
        ->get();
        return view('applicants.index', compact('applicants'));
    }
}
