<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One AI request (or its reservation). Costs are estimates from configured prices. */
class UpworkAiUsage extends Model
{
    const STATUS_RESERVED = 'reserved';
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';
    const STATUS_ABANDONED = 'abandoned';

    protected $table = 'upwork_ai_usage';

    protected $fillable = [
        'provider', 'model', 'request_type', 'prompt_version', 'opportunity_id', 'status', 'attempt',
        'reserved_cost_usd', 'reserved_tokens', 'input_tokens', 'cached_input_tokens', 'output_tokens', 'reasoning_tokens',
        'estimated_cost_usd', 'duration_ms', 'error_code', 'error',
    ];

    protected $casts = [
        'reserved_cost_usd' => 'float',
        'estimated_cost_usd' => 'float',
    ];

    public function opportunity()
    {
        return $this->belongsTo(UpworkOpportunity::class, 'opportunity_id');
    }

    /** Cost counted against budgets: the reservation until the call finishes, then the actual estimate. */
    public function getBudgetCostAttribute(): float
    {
        return in_array($this->status, [self::STATUS_RESERVED, self::STATUS_ABANDONED], true)
            ? (float) $this->reserved_cost_usd
            : (float) $this->estimated_cost_usd;
    }
}
