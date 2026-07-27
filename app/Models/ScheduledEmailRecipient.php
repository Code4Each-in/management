<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledEmailRecipient extends Model
{
    protected $fillable = [
        'scheduled_email_id',
        'client_id',
        'user_id',
        'email',
        'name',
        'recipient_type',
        'status',
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
        public function user()
    {
        return $this->belongsTo(Users::class);
    }

    // Resolve email regardless of recipient type
    public function getResolvedEmailAttribute(): ?string
    {
        return match ($this->recipient_type) {
            'client' => $this->client->email ?? null,
            'user'   => $this->user->email ?? null,
            'manual' => $this->email,
            default  => null,
        };
    }

    // Resolve display name regardless of recipient type
    public function getResolvedNameAttribute(): ?string
    {
        return match ($this->recipient_type) {
            'client' => $this->client->name ?? null,
            'user'   => $this->user->name ?? null,
            'manual' => $this->name ?? $this->email,
            default  => null,
        };
    }
}
