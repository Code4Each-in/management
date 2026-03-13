<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Projects;
use App\Models\Users;

class ProjectLogSetting extends Model
{
    protected $fillable = [
        'project_id',
        'updated_by',
        'enabled'
    ];
    // Relationship with project
    public function project()
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }

    // Relationship with user who updated
    public function user()
    {
        return $this->belongsTo(Users::class, 'updated_by');
    }
}
