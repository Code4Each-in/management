<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeploymentComment extends Model
{
    use SoftDeletes;

    protected $table = 'deployment_comments';

    protected $fillable = [
        'deployment_ticket_id',
        'deployment_bug_id',
        'user_id',
        'comment',
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
}
