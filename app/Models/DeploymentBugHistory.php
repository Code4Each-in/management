<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentBugHistory extends Model
{
    protected $table = 'deployment_bug_history';

    protected $fillable = [
        'deployment_bug_id',
        'changed_by',
        'old_status',
        'new_status',
        'remarks',
    ];

    public function bug()
    {
        return $this->belongsTo(DeploymentBug::class, 'deployment_bug_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(\App\Models\Users::class, 'changed_by');
    }
}
