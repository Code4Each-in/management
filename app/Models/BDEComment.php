<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BDEComment extends Model
{
    use HasFactory;

    protected $table = 'bde_comments';

    protected $fillable = [
        'task_id',
        'comment_by',
        'comments',
        'document',
    ];

    
    public function user()
    {
        return $this->belongsTo(Users::class, 'comment_by');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
