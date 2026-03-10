<?php

use App\Models\ProjectLog;


if (!function_exists('project_log')) {

    function project_log($projectId = null, $type = 'info', $message = '', $context = [], $module = null)
    {

        try {

            $projectId = $projectId ?? config('app.project_log_id');

            ProjectLog::create([
                'project_id' => $projectId,
                'type' => $type,
                'module' => $module,
                'message' => $message,
                'context' => !empty($context) ? json_encode($context) : null,
                'logged_at' => now()
            ]);

        } catch (\Throwable $e) {
            // prevent crash if logging fails
        }
    }

}
