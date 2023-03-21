<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tickets extends Model
{
    use HasFactory ,SoftDeletes;
 /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'assign',
        'eta', 
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