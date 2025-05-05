<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['user_id', 'type', 'message', 'is_read', 'ticket_id', 'is_super_admin' ];


    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }

}

