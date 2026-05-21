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
        'completed_at',
        'user_id',
        'status',
        'ticket_id',
        'created_by',
        'completed_by',
    ];
    public function assignedTo()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(Users::class, 'created_by');
    }

    public function completedUser()
    {
        return $this->belongsTo(Users::class, 'completed_by');
    }
}

