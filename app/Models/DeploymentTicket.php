<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeploymentTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'deployment_code','deployment_name','project_id','related_ticket_ids',
        'created_by','assigned_developer_id','qa_id','priority',
        'changes_done','files_modified','modules_affected','testing_done','deployment_notes',
        'db_changes_required','migration_details',
        'current_version','new_version','deployment_date',
        'status','qa_approved','fix_attempts',
        'first_submitted_at','qa_completed_at',
    ];

    protected $casts = [
        'db_changes_required' => 'boolean',
        'qa_approved' => 'boolean',
        'deployment_date' => 'date',
        'first_submitted_at' => 'datetime',
        'qa_completed_at' => 'datetime',
    ];

    public function project() { return $this->belongsTo(Projects::class); }
    public function creator() { return $this->belongsTo(Users::class, 'created_by'); }
    public function developers()
    {
        return $this->belongsToMany(Users::class, 'deployment_ticket_developer', 'deployment_ticket_id', 'user_id')
            ->withTimestamps();
    }
    public function qa() { return $this->belongsTo(Users::class, 'qa_id'); }
    public function bugs() { return $this->hasMany(DeploymentBug::class); }
    public function attachments() { return $this->hasMany(DeploymentAttachment::class); }
    public function logs() { return $this->hasMany(DeploymentLog::class)->latest('id'); }

    public function openBugsCount()
    {
        return $this->bugs()->whereIn('status', ['open', 'fixed'])->count();
    }

    /**
     * The ONLY way status should ever be changed.
     * Updates the existing row + writes one log entry. Never creates a new ticket row.
     */
    public function changeStatus(string $newStatus, int $userId): void
    {
        $old = $this->status;

        if ($old === 'draft' && $newStatus !== 'draft' && !$this->first_submitted_at) {
            $this->first_submitted_at = now();
        }

        if ($newStatus === 'approved') {
            $this->qa_approved = true;
            $this->qa_completed_at = now();
        }

        if ($newStatus === 'needs_fix') {
            $this->fix_attempts += 1;
        }

        $this->status = $newStatus;
        $this->save();

        $this->logs()->create([
            'user_id' => $userId,
            'old_status' => $old,
            'new_status' => $newStatus,
        ]);
    }
    public function relatedTicket()
    {
        return $this->belongsTo(Tickets::class, 'related_ticket_ids');
    }
}
