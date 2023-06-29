<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class TicketAssigns extends Model
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'ticket_id',
        'user_id',
  
    ];
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id','id');
    }
}