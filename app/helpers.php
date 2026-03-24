<?php

use App\Models\ProjectLog;
use App\Models\ProjectLogSetting;

if (!function_exists('project_log')) {

    function project_log($projectId = null, $type = 'info', $message = '', $context = [], $module = null)
    {
        try {

            $projectId = $projectId ?? config('app.project_log_id');
            $message = $message ?: 'No message provided';

            // Check if logging is enabled for this project
            if ($projectId) {
                $setting = ProjectLogSetting::where('project_id', $projectId)->first();

                if ($setting && !$setting->enabled) {
                    return; // logging disabled, stop here
                }
            }

            ProjectLog::create([
                'project_id' => $projectId,
                'type' => $type,
                'module' => $module,
                'message' => $message,
                'context' => $context ?: null,
                'logged_at' => now()
            ]);

        } catch (\Throwable $e) {
            // prevent crash if logging fails
        }
    }

}

if (!function_exists('test_helper')) {
    function test_helper()
    {
        return 'Helper working';
    }
}
