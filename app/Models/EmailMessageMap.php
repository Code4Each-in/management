<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailMessageMap extends Model
{
    protected $table = 'email_message_map';

    protected $fillable = [
        'ticket_comment_id',
        'recipient_email',
        'email_message_id',
    ];

    public function comment()
    {
        return $this->belongsTo(
            TicketComments::class,
            'ticket_comment_id'
        );
    }
}
