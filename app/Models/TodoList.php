<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoList extends Model
{
    use HasFactory;
    protected $table = 'todo_lists';
    protected $fillable = [
        'title',
        'created_at',
        'completed_at',
        'user_id',
        'status',
    ];
}
