<?php

namespace App\Services;

use App\Models\DeploymentTicket;
use App\Models\DeploymentBug;

class DeploymentCodeGenerator
{
    public static function nextDeploymentCode(): string
    {
        $last = DeploymentTicket::withTrashed()->orderByDesc('id')->first();
        $nextNumber = $last ? ($last->id + 1) : 1;

        return 'DEP-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    public static function nextBugCode(): string
    {
        $last = DeploymentBug::withTrashed()->orderByDesc('id')->first();
        $nextNumber = $last ? ($last->id + 1) : 1;

        return 'BUG-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
