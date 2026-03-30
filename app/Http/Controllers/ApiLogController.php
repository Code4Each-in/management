<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProjectLog;
use App\Models\Projects;
use Illuminate\Support\Facades\Log;

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

    public function storeBulk(Request $request)
    {
        $request->validate([
            'logs' => 'required|array',
        ]);

        $logs = $request->input('logs');
        $insertData = [];

        foreach ($logs as $log) {

            if (!isset($log['project_id'], $log['message'])) {
                continue;
            }

            $projectExists = Projects::where('id', $log['project_id'])->exists();
            if (!$projectExists) continue;

            $insertData[] = [
                'logger_id' => $log['logger_id'],
                'project_id' => $log['project_id'],
                'type'       => $log['type'] ?? 'error',
                'module'     => $log['module'] ?? null,
                'message'    => $log['message'],
                'context'    => $log['context'] ?? null,
                'logged_at'  => $log['logged_at'] ?? now(),
            ];
        }

        $totalInserted = 0;
    
        foreach (array_chunk($insertData, 500) as $chunk) {
            ProjectLog::insert($chunk);
            $totalInserted += count($chunk);
        }

        return response()->json([
            'status' => 'success',
            'inserted' => $totalInserted
        ]);
    }
}
