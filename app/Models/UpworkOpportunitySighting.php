<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Which search profile found an opportunity, when, and how often. One row per (job, profile). */
class UpworkOpportunitySighting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'opportunity_id', 'search_profile_id', 'profile_version', 'last_run_id', 'first_seen_at', 'last_seen_at',
        'times_seen', 'ignored', 'ignore_reason',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'ignored' => 'boolean',
    ];

    public function opportunity()
    {
        return $this->belongsTo(UpworkOpportunity::class, 'opportunity_id');
    }

    public function searchProfile()
    {
        return $this->belongsTo(UpworkSearchProfile::class, 'search_profile_id');
    }
}
