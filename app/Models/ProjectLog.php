<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Projects;

class ProjectLog extends Model
{
    use HasFactory;

    protected $table = 'project_logs';

    protected $fillable = [
        'project_id',
        'type',
        'module',
        'message',
        'context',
        'logged_at'
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'context' => 'array'
    ];

    public function project()
    {
        return $this->belongsTo(Projects::class);
    }
}
