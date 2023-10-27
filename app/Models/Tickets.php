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
        'project_id',
        'assign',
        'eta', 
        'upload',   
        'status', 
        'status_changed_by', 
        'created_by', 
        'priority',
        'comment',   
    ];
    public function ticketAssigns()
    {
        return $this->hasMany(TicketAssigns::class, 'ticket_id','id');
    }

    public function ticketRelatedTo()
    {
        return $this->belongsTo(Projects::class, 'project_id', 'id');
    }

    public function ticketby() 
    {
        return $this->belongsTo(Users::class, 'created_by', 'id');
    }
}