<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tickets extends Model
{
    use HasFactory;
 /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'assign',
        'eta_from',
        'eta_to',
        'upload',   
        'status', 
        'priority',
        'comment',   
    ];
    public function ticketAssigns()
    {
        return $this->hasMany(TicketAssigns::class, 'ticket_id','id');
    }
}