<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class DeploymentTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'deployment_code',
        'deployment_name',
        'project_id',
        'related_ticket_ids',
        'created_by',
        'assigned_developer_id',
        'reviewer_id',
        'qa_tester_id',
        'priority',
        'changes_done',
        'files_modified',
        'modules_affected',
        'testing_done',
        'deployment_notes',
        'db_changes_required',
        'migration_details',
        'current_version',
        'new_version',
        'deployment_date',
        'status',
        'code_review_approved',
        'qa_approved',
        'review_attempts',
        'first_submitted_for_review_at',
        'review_completed_at',
    ];

    protected $casts = [
        'db_changes_required' => 'boolean',
        'code_review_approved' => 'boolean',
        'qa_approved' => 'boolean',
        'deployment_date' => 'date',
        'first_submitted_for_review_at' => 'datetime',
        'review_completed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Status groups - used for dashboard widgets & badge styling
    |--------------------------------------------------------------------------
    */
    const STATUS_DRAFT = 'Draft';
    const STATUS_REVIEW_PENDING = 'Review Pending';
    const STATUS_REVIEW_IN_PROGRESS = 'Review In Progress';
    const STATUS_CHANGES_REQUESTED = 'Changes Requested';
    const STATUS_REVIEW_APPROVED = 'Review Approved';
    const STATUS_REVIEW_REJECTED = 'Review Rejected';
    const STATUS_TESTING_IN_PROGRESS = 'Testing In Progress';
    const STATUS_TESTING_FAILED = 'Testing Failed';
    const STATUS_TESTING_PASSED = 'Testing Passed';
    const STATUS_READY_FOR_DEPLOYMENT = 'Ready For Deployment';
    const STATUS_DEPLOYMENT_APPROVED = 'Deployment Approved';
    const STATUS_DEPLOYMENT_REJECTED = 'Deployment Rejected';
    const STATUS_DEPLOYED = 'Deployed';
    const STATUS_ROLLBACK_REQUIRED = 'Rollback Required';
    const STATUS_ROLLED_BACK = 'Rolled Back';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_REVIEW_PENDING,
            self::STATUS_REVIEW_IN_PROGRESS,
            self::STATUS_CHANGES_REQUESTED,
            self::STATUS_REVIEW_APPROVED,
            self::STATUS_REVIEW_REJECTED,
            self::STATUS_TESTING_IN_PROGRESS,
            self::STATUS_TESTING_FAILED,
            self::STATUS_TESTING_PASSED,
            self::STATUS_READY_FOR_DEPLOYMENT,
            self::STATUS_DEPLOYMENT_APPROVED,
            self::STATUS_DEPLOYMENT_REJECTED,
            self::STATUS_DEPLOYED,
            self::STATUS_ROLLBACK_REQUIRED,
            self::STATUS_ROLLED_BACK,
        ];
    }

    /**
     * Bootstrap badge color per status - used in views.
     */
    public function statusBadgeClass(): string
    {
        $map = [
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_REVIEW_PENDING => 'info',
            self::STATUS_REVIEW_IN_PROGRESS => 'info',
            self::STATUS_CHANGES_REQUESTED => 'warning',
            self::STATUS_REVIEW_APPROVED => 'primary',
            self::STATUS_REVIEW_REJECTED => 'danger',
            self::STATUS_TESTING_IN_PROGRESS => 'info',
            self::STATUS_TESTING_FAILED => 'danger',
            self::STATUS_TESTING_PASSED => 'primary',
            self::STATUS_READY_FOR_DEPLOYMENT => 'primary',
            self::STATUS_DEPLOYMENT_APPROVED => 'success',
            self::STATUS_DEPLOYMENT_REJECTED => 'danger',
            self::STATUS_DEPLOYED => 'success',
            self::STATUS_ROLLBACK_REQUIRED => 'warning',
            self::STATUS_ROLLED_BACK => 'dark',
        ];

        return $map[$this->status] ?? 'secondary';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function project()
    {
        return $this->belongsTo(\App\Models\Projects::class, 'project_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\Users::class, 'created_by');
    }

    public function developer()
    {
        return $this->belongsTo(\App\Models\Users::class, 'assigned_developer_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\Users::class, 'reviewer_id');
    }

    public function qaTester()
    {
        return $this->belongsTo(\App\Models\Users::class, 'qa_tester_id');
    }

    public function attachments()
    {
        return $this->hasMany(DeploymentAttachment::class);
    }

    public function reviewHistory()
    {
        return $this->hasMany(DeploymentReviewHistory::class)->orderByDesc('id');
    }

    public function bugs()
    {
        return $this->hasMany(DeploymentBug::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(DeploymentStatusHistory::class)->orderByDesc('id');
    }

    public function activityLogs()
    {
        return $this->hasMany(DeploymentActivityLog::class)->orderByDesc('id');
    }

    public function comments()
    {
        return $this->hasMany(DeploymentComment::class)->whereNull('deployment_bug_id')->orderByDesc('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeOpenBugsCount(Builder $query)
    {
        return $query->withCount(['bugs' => function ($q) {
            $q->whereNotIn('status', ['Closed']);
        }]);
    }

    public function hasOpenBugs(): bool
    {
        return $this->bugs()->whereNotIn('status', ['Closed'])->exists();
    }

    /**
     * Whether this ticket meets all gates required to move to Ready For Deployment.
     */
    public function isEligibleForDeploymentApproval(): bool
    {
        return $this->code_review_approved
            && $this->qa_approved
            && ! $this->hasOpenBugs();
    }

    public function isSuperAdmin(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $user = \App\Models\Users::find($userId);

        return $user && (int) $user->role_id === 1;
    }

    public function isAssignedDeveloper(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return $this->isSuperAdmin($userId) || (int) $this->assigned_developer_id === (int) $userId;
    }

    public function isAssignedReviewer(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return $this->isSuperAdmin($userId) || (int) $this->reviewer_id === (int) $userId;
    }

    public function isAssignedQaTester(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return $this->isSuperAdmin($userId) || (int) $this->qa_tester_id === (int) $userId;
    }

    /**
     * Anyone who has SOME role on this ticket (developer, reviewer, QA, or admin).
     * Used to decide whether to show the ticket at all in "My Tickets" type views.
     */
    public function isInvolved(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return $this->isAssignedDeveloper($userId)
            || $this->isAssignedReviewer($userId)
            || $this->isAssignedQaTester($userId);
    }
}
