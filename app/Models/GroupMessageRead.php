<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessageRead extends Model
{
    use HasFactory;

    protected $table = 'group_message_reads';

    protected $fillable = [
        'user_id',
        'message_id',
        'read_at'
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

    public function message()
    {
        return $this->belongsTo(GroupMessage::class, 'message_id');
    }

}