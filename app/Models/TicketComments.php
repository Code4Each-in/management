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
        'document',
        'comment_by',
        'is_system'

    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'comment_by','id');
    }
    public function ticket()
{
    return $this->belongsTo(Tickets::class, 'ticket_id');
}
public function project()
{
    return $this->belongsTo(Projects::class, 'project'); 
}
}