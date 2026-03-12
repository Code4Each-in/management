<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectLog;
use App\Models\Projects;

class ApiLogController extends Controller
{
    public function store(Request $request)
    {

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'message' => 'required|string',
        ]);

        $project = Projects::find($request->project_id);

        ProjectLog::create([
            'project_id' => $project->id,
            'type'       => $request->type ?? 'error',
            'module'     => $request->module,
            'message'    => $request->message,
            'context'    => $request->context,
            'logged_at'  => now()
        ]);

        return response()->json([
            'status' => 'success'
        ]);
    }
}
