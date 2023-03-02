<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketComments extends Model
{
    use HasFactory;
    protected $fillable = [
        'ticket_id',
        'comments',
        'comment_by',

    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'comment_by','id');
    }
}