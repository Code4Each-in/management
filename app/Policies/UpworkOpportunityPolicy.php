<?php

namespace App\Policies;

use App\Models\Modules;
use App\Models\RolePermission;
use App\Models\UpworkOpportunity;
use App\Models\Users;
use Illuminate\Auth\Access\HandlesAuthorization;
use Laravel\Sanctum\PersonalAccessToken;

class UpworkOpportunityPolicy
{
    use HandlesAuthorization;

    /**
     * Route name used to grant access to other roles through the existing
     * Pages/Modules permission screens.
     */
    const MODULE_ROUTE = 'upwork.opportunities.index';

    /**
     * Route name of the module that lets a role EDIT the Upwork configuration
     * (search profiles, technologies, scoring, classifications, agent settings).
     * Register it under Management -> Pages / Modules and grant it to a role.
     */
    const CONFIG_MODULE_ROUTE = 'upwork.config.manage';

    public function viewAny(Users $user): bool
    {
        return $this->hasAccess($user);
    }

    public function view(Users $user, UpworkOpportunity $opportunity): bool
    {
        return $this->hasAccess($user);
    }

    public function approve(Users $user, UpworkOpportunity $opportunity): bool
    {
        return $this->hasAccess($user);
    }

    public function reject(Users $user, UpworkOpportunity $opportunity): bool
    {
        return $this->hasAccess($user);
    }

    /** Archive or restore jobs, one or in bulk (people who can review them). */
    public function archive(Users $user, ?UpworkOpportunity $opportunity = null): bool
    {
        return $this->hasAccess($user);
    }

    /** Snooze or resume review reminders for one job (people who can review it). */
    public function snooze(Users $user, UpworkOpportunity $opportunity): bool
    {
        return $this->hasAccess($user);
    }

    /** Correct a job's location eligibility (people who can review it; audited). */
    public function overrideLocation(Users $user, UpworkOpportunity $opportunity): bool
    {
        return $this->hasAccess($user);
    }

    public function manageProposal(Users $user, UpworkOpportunity $opportunity): bool
    {
        return $this->hasAccess($user);
    }

    /** Ask for an AI analysis (budget-guarded; never touches Upwork). */
    public function analyze(Users $user, UpworkOpportunity $opportunity): bool
    {
        return $this->hasAccess($user);
    }

    /** Anyone with Upwork access may read the configuration. */
    public function viewConfig(Users $user): bool
    {
        return $this->hasAccess($user);
    }

    /** Starting a discovery run from the PMS (read-only Upwork calls): Super Admin only. */
    public function runDiscovery(Users $user): bool
    {
        return $user->isAdmin();
    }

    /** Changing configuration: Super Admin, or a role granted the configuration module. */
    public function manageConfig(Users $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $moduleId = Modules::where('route_name', self::CONFIG_MODULE_ROUTE)->value('id');

        return $moduleId !== null
            && RolePermission::where('role_id', $user->role_id)->where('module_id', $moduleId)->exists();
    }

    /**
     * Agent API: only a Sanctum personal access token carrying the ingest ability.
     */
    public function ingest(Users $user): bool
    {
        return $user->currentAccessToken() instanceof PersonalAccessToken
            && $user->tokenCan(config('upwork.agent_token_ability'));
    }

    private function hasAccess(Users $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (in_array($user->designation, config('upwork.designations', []), true)) {
            return true;
        }

        $moduleId = Modules::where('route_name', self::MODULE_ROUTE)->value('id');

        return $moduleId !== null
            && RolePermission::where('role_id', $user->role_id)->where('module_id', $moduleId)->exists();
    }
}
