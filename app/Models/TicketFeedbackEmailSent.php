<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class TicketFeedbackEmailSent extends Model
{
    use SoftDeletes; // ✅ ADD

    protected $table = 'ticket_feedback_emails_sent';

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'sent_at',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }
}