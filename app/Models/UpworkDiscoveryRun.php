<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One discovery cycle of the Mac mini agent. Holds a lease so two runs can
 * never overlap, the query plan the PMS handed out, and the run's audit stats.
 */
class UpworkDiscoveryRun extends Model
{
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'uuid', 'status', 'trigger', 'agent_host', 'ruleset_version', 'started_at', 'heartbeat_at',
        'lease_expires_at', 'finished_at', 'plan', 'stats', 'error', 'user_id',
    ];

    protected $casts = [
        'plan' => 'array',
        'stats' => 'array',
        'ruleset_version' => 'integer',
        'started_at' => 'datetime',
        'heartbeat_at' => 'datetime',
        'lease_expires_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function queries()
    {
        return $this->hasMany(UpworkDiscoveryQuery::class, 'run_id');
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_RUNNING && $this->lease_expires_at && $this->lease_expires_at->isFuture();
    }
}
