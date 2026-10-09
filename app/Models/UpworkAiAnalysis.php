<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One stored AI answer about an opportunity. Answers are never overwritten:
 * a regenerated analysis is a new row, and the opportunity points at the
 * one currently in use.
 */
class UpworkAiAnalysis extends Model
{
    const STATUS_SUCCEEDED = 'succeeded';
    const STATUS_FAILED = 'failed';

    const TYPE_QUALIFY = 'qualify';
    const TYPE_DEEP = 'deep';

    protected $table = 'upwork_ai_analyses';

    protected $guarded = ['id'];

    protected $casts = [
        'result' => 'array',
        'confidence' => 'float',
        'fit_score' => 'integer',
    ];

    public function opportunity()
    {
        return $this->belongsTo(UpworkOpportunity::class, 'opportunity_id');
    }

    public function usage()
    {
        return $this->belongsTo(UpworkAiUsage::class, 'usage_id');
    }

    public function requester()
    {
        return $this->belongsTo(Users::class, 'requested_by');
    }

    /** Claims of one kind (FACT, INFERENCE, ASSUMPTION, UNKNOWN). */
    public function claims(string $kind): array
    {
        return array_values(array_filter($this->result['claims'] ?? [], fn($c) => ($c['kind'] ?? null) === $kind));
    }
}
