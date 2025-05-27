<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessageRead extends Model
{
    use HasFactory;

    protected $table = 'group_message_reads';

    protected $fillable = [
        'project_id',
        'user_id',
    ];

    // Relationships (optional but useful)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}