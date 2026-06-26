<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentNotification extends Model
{
    protected $table = 'deployment_notifications';

    protected $fillable = [
        'deployment_ticket_id',
        'deployment_bug_id',
        'user_id',
        'type',
        'title',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(DeploymentTicket::class, 'deployment_ticket_id');
    }

    public function bug()
    {
        return $this->belongsTo(DeploymentBug::class, 'deployment_bug_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\Users::class, 'user_id');
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
