<?php

namespace App\Services;

use App\Models\DeploymentActivityLog;
use App\Models\DeploymentTicket;
use Illuminate\Support\Facades\Auth;

class DeploymentActivityLogger
{
    /**
     * Record an activity log entry against a deployment ticket.
     *
     * @param  DeploymentTicket  $ticket
     * @param  string  $action          e.g. "Deployment Created", "Review Approved"
     * @param  mixed  $oldValue
     * @param  mixed  $newValue
     * @param  string|null  $description
     * @param  \Illuminate\Database\Eloquent\Model|null  $loggable  Optional related model (e.g. DeploymentBug)
     */
    public static function log(
        DeploymentTicket $ticket,
        string $action,
        $oldValue = null,
        $newValue = null,
        ?string $description = null,
        $loggable = null
    ): DeploymentActivityLog {
        return DeploymentActivityLog::create([
            'deployment_ticket_id' => $ticket->id,
            'loggable_type' => $loggable ? get_class($loggable) : null,
            'loggable_id' => $loggable ? $loggable->id : null,
            'user_id' => Auth::id(),
            'action' => $action,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'description' => $description,
        ]);
    }
}
