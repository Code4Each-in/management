<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentStatusHistory extends Model
{
    protected $table = 'deployment_status_history';

    protected $fillable = [
        'deployment_ticket_id',
        'changed_by',
        'field_changed',
        'old_value',
        'new_value',
    ];

    public function ticket()
    {
        return $this->belongsTo(DeploymentTicket::class, 'deployment_ticket_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(\App\Models\Users::class, 'changed_by');
    }
}
