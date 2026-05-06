<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tickets;
use App\Models\Users;


class TicketFeedback extends Model
{
    use SoftDeletes; // ✅ ADD

    protected $table = 'ticket_feedbacks';

    protected $casts = [
        'assigned_dev_id' => 'array',
    ];

    protected $fillable = [
        'ticket_id',
        'feedback_by',
        'assigned_dev_id',
        'rating',
        'comments',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }

   public function developers()
    {
        return Users::whereIn('id', $this->assigned_dev_id ?? [])->get();
    }
}