<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One Upwork search the agent executed during a run, with its outcome counts. */
class UpworkDiscoveryQuery extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'run_id', 'search_profile_id', 'profile_version', 'query_type', 'query_text', 'params', 'page', 'status',
        'results_count', 'new_count', 'existing_count', 'duplicate_count', 'ignored_count', 'hard_rejected_count',
        'error', 'duration_ms',
    ];

    protected $casts = [
        'params' => 'array',
    ];

    public function run()
    {
        return $this->belongsTo(UpworkDiscoveryRun::class, 'run_id');
    }

    public function searchProfile()
    {
        return $this->belongsTo(UpworkSearchProfile::class, 'search_profile_id');
    }
}
