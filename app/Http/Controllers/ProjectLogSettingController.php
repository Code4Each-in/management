<?php

namespace App\Http\Controllers;

use App\Models\ProjectLogSetting;
use App\Models\Projects;
use Illuminate\Http\Request;

class ProjectLogSettingController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->role_id == 6) { // Client

            $projects = Projects::leftJoin('project_log_settings','projects.id','=','project_log_settings.project_id')
                ->where('projects.client_id', $user->client_id)
                ->where('status', 'active')
                ->select(
                    'projects.id',
                    'projects.project_name',
                    'project_log_settings.enabled',
                    'project_log_settings.updated_by'
                )
                ->get();

        } else { // Admin

            $projects = Projects::leftJoin('project_log_settings','projects.id','=','project_log_settings.project_id')
                ->select(
                    'projects.id',
                    'projects.project_name',
                    'project_log_settings.enabled',
                    'project_log_settings.updated_by'
                )
                ->where('status', 'active')
                ->get();

                $logNotifications = ProjectLogSetting::with(['project','user'])
                ->latest('updated_at')
                ->get();

        }

        return view('logs.settings', compact('projects','logNotifications'));
    }

  public function toggle($projectId)
{
    $setting = ProjectLogSetting::firstOrCreate([
        'project_id' => $projectId
    ]);

    $setting->enabled = !$setting->enabled;
    $setting->updated_by = auth()->id();
    $setting->save();

    return response()->json([
        'enabled' => $setting->enabled,
        'message' => 'Log setting updated'
    ]);
}
}
