<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An Upwork client (job poster), identified by Upwork's own client id.
 * Not to be confused with App\Models\Client (Code4Each customers in the PMS).
 */
class UpworkClient extends Model
{
    /** Figures whose change creates a new snapshot. */
    const TRACKED = ['rating', 'reviews_count', 'total_spent', 'hire_rate', 'jobs_posted', 'jobs_hired'];

    protected $fillable = [
        'fingerprint',
        'external_id', 'country', 'timezone', 'rating', 'reviews_count', 'total_spent', 'hire_rate', 'jobs_posted',
        'jobs_hired', 'payment_verified', 'open_jobs', 'work_history', 'last_client_score', 'first_seen_at', 'last_seen_at',
    ];

    protected $casts = [
        'rating' => 'float',
        'total_spent' => 'float',
        'hire_rate' => 'float',
        'payment_verified' => 'boolean',
        'work_history' => 'array',
        'last_client_score' => 'float',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function opportunities()
    {
        return $this->hasMany(UpworkOpportunity::class, 'upwork_client_id');
    }

    public function snapshots()
    {
        return $this->hasMany(UpworkClientSnapshot::class, 'upwork_client_id')->orderBy('recorded_at')->orderBy('id');
    }

    public function getLabelAttribute(): string
    {
        return 'Client ' . $this->external_id . ($this->country ? " · {$this->country}" : '');
    }
}
