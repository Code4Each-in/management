<?php

namespace App\Http\Controllers;

use App\Models\Applicants;
use Illuminate\Http\Request;

class ApplicantsController extends Controller
{
    //
    public function index(){
        $applicants=Applicants::where('status',1)
        ->orderBy('id','desc')->get();
        return view('applicants.index', compact('applicants'));
    }
}
