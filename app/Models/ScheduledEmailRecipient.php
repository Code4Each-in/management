<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledEmailRecipient extends Model
{
    protected $fillable = [
        'scheduled_email_id',
        'client_id',
        'status',    // pending / sent / failed
        'sent_at',
        'error',
    ];

    protected $casts = [
        'sent_at' => 'datetime',  // NEW
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function scheduledEmail()
    {
        return $this->belongsTo(ScheduledEmail::class);  // NEW — reverse relation
    }
}
