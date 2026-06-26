<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentActivityLog extends Model
{
    protected $table = 'deployment_activity_logs';

    protected $fillable = [
        'deployment_ticket_id',
        'loggable_type',
        'loggable_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
        'description',
    ];

    public function ticket()
    {
        return $this->belongsTo(DeploymentTicket::class, 'deployment_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\Users::class, 'user_id');
    }

    public function loggable()
    {
        return $this->morphTo();
    }
}
