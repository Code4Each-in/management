<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpworkOpportunityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['upwork_opportunity_id', 'user_id', 'action', 'old_status', 'new_status', 'note', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($log) {
            $log->created_at = $log->created_at ?? now();
        });
    }

    public function opportunity() { return $this->belongsTo(UpworkOpportunity::class, 'upwork_opportunity_id'); }
    public function user() { return $this->belongsTo(Users::class); }
}
