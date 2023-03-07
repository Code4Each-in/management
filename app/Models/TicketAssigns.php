<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketAssigns extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_id',
        'user_id',
  
    ];
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id','id');
    }
}