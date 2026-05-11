<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class TicketComments extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'ticket_id',
        'comments',
        'document',
        'comment_by',
        'is_system',
        'pinned_by',
        'reply_to'

    ];
  protected $dates = ['deleted_at'];
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
    public function parent()
    {
        return $this->belongsTo(TicketComments::class, 'reply_to');
    }
    public function pinnedByUser()
    {
        return $this->belongsTo(Users::class, 'pinned_by');
    }
}
