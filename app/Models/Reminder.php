<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Users;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'daily_hour', 'daily_minute', 'weekly_day', 'weekly_hour', 'weekly_minute',
        'monthly_date', 'monthly_hour', 'monthly_minute', 'description', 'reminder_date', 'is_active', 'clicked_at', 'user_id','custom_date'
    ];

    protected $casts = [
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'reminder_date' => 'datetime',
        'clicked_at'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
}
