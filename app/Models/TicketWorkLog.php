<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketWorkLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'hours',
        'log_date',
        'note'
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class);
    }

    public function user()
    {
        return $this->belongsTo(Users::class);
    }
}
