<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeploymentBug extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bug_code',
        'deployment_ticket_id',
        'title',
        'description',
        'severity',
        'assigned_developer_id',
        'reported_by',
        'steps_to_reproduce',
        'screenshot_path',
        'status',
    ];

    const STATUS_OPEN = 'Open';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_FIXED = 'Fixed';
    const STATUS_READY_FOR_RETEST = 'Ready For Retest';
    const STATUS_RETEST_REQUIRED = 'Retest Required';
    const STATUS_CLOSED = 'Closed';
    const STATUS_REOPENED = 'Reopened';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_OPEN,
            self::STATUS_IN_PROGRESS,
            self::STATUS_FIXED,
            self::STATUS_READY_FOR_RETEST,
            self::STATUS_RETEST_REQUIRED,
            self::STATUS_CLOSED,
            self::STATUS_REOPENED,
        ];
    }

    public function statusBadgeClass(): string
    {
        $map = [
            self::STATUS_OPEN => 'danger',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_FIXED => 'primary',
            self::STATUS_READY_FOR_RETEST => 'warning',
            self::STATUS_RETEST_REQUIRED => 'warning',
            self::STATUS_CLOSED => 'success',
            self::STATUS_REOPENED => 'danger',
        ];

        return $map[$this->status] ?? 'secondary';
    }

    public function severityBadgeClass(): string
    {
        $map = [
            'Low' => 'secondary',
            'Medium' => 'info',
            'High' => 'warning',
            'Critical' => 'danger',
        ];

        return $map[$this->severity] ?? 'secondary';
    }

    public function ticket()
    {
        return $this->belongsTo(DeploymentTicket::class, 'deployment_ticket_id');
    }

    public function developer()
    {
        return $this->belongsTo(\App\Models\Users::class, 'assigned_developer_id');
    }

    public function reporter()
    {
        return $this->belongsTo(\App\Models\Users::class, 'reported_by');
    }

    public function history()
    {
        return $this->hasMany(DeploymentBugHistory::class, 'deployment_bug_id')->orderByDesc('id');
    }

    public function comments()
    {
        return $this->hasMany(DeploymentComment::class, 'deployment_bug_id')->orderByDesc('id');
    }

    public function screenshotUrl(): ?string
    {
        return $this->screenshot_path ? asset('storage/' . $this->screenshot_path) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Per-bug role checks - mirrors DeploymentTicket's logic
    |--------------------------------------------------------------------------
    */

    public function isSuperAdmin(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $user = \App\Models\Users::find($userId);

        return $user && (int) $user->role_id === 1;
    }

    /**
     * The developer fixing THIS bug.
     */
    public function isAssignedDeveloper(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return $this->isSuperAdmin($userId) || (int) $this->assigned_developer_id === (int) $userId;
    }

    /**
     * The QA tester who reported THIS bug - they're the one who verifies/closes it.
     */
    public function isReporter(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return $this->isSuperAdmin($userId) || (int) $this->reported_by === (int) $userId;
    }
}
