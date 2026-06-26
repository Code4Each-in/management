<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentReviewHistory extends Model
{
    protected $table = 'deployment_review_history';

    protected $fillable = [
        'deployment_ticket_id',
        'reviewer_id',
        'action',
        'comments',
        'attempt_number',
        'time_spent_minutes',
    ];

    public function ticket()
    {
        return $this->belongsTo(DeploymentTicket::class, 'deployment_ticket_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\Users::class, 'reviewer_id');
    }

    public function badgeClass(): string
    {
        $map = [
            'Submitted' => 'info',
            'Approved' => 'success',
            'Rejected' => 'danger',
            'Changes Requested' => 'warning',
        ];

        return $map[$this->action] ?? 'secondary';
    }
}
