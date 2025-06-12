<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketEstimationApproval extends Model
{
    protected $fillable = [
        'ticket_id',
        'approved_by',
        'approved_at',
    ];


    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }
}

