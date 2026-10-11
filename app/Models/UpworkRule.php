<?php

namespace App\Models;

use App\Services\Upwork\Scoring\RuleConditions;
use Illuminate\Database\Eloquent\Model;

/**
 * A structured rule inside a ruleset:
 *  - score: adds (or with negative points, subtracts) points to one component
 *  - hard_filter: rejects the job before scoring, recording the reason
 *  - classification: assigns a label/archetype (QUICK_WIN, STRATEGIC, ...)
 * Rules of a published ruleset are frozen.
 */
class UpworkRule extends Model
{
    const TYPE_SCORE = 'score';
    const TYPE_HARD_FILTER = 'hard_filter';
    const TYPE_CLASSIFICATION = 'classification';

    const TYPES = [
        self::TYPE_SCORE => 'Score rule',
        self::TYPE_HARD_FILTER => 'Hard filter',
        self::TYPE_CLASSIFICATION => 'Classification',
    ];

    protected $fillable = [
        'ruleset_id', 'key', 'name', 'type', 'component', 'points', 'classification', 'conditions', 'reason', 'enabled', 'sort',
    ];

    protected $casts = [
        'points' => 'float',
        'conditions' => 'array',
        'enabled' => 'boolean',
    ];

    protected $attributes = [
        'enabled' => true,
        'points' => 0,
        'sort' => 0,
    ];

    protected static function booted()
    {
        static::saving(function (UpworkRule $rule) {
            $rule->guardDraft();

            if ($errors = $rule->validationErrors()) {
                throw new \InvalidArgumentException("Invalid rule '{$rule->key}': " . implode(' ', $errors));
            }
        });

        static::deleting(function (UpworkRule $rule) {
            $rule->guardDraft();
        });
    }

    public function ruleset()
    {
        return $this->belongsTo(UpworkRuleset::class, 'ruleset_id');
    }

    /**
     * @return string[]
     */
    public function validationErrors(): array
    {
        $errors = [];

        if (!preg_match('/^[a-z0-9_]{2,64}$/', (string) $this->key)) {
            $errors[] = 'Key must be 2-64 characters of a-z, 0-9 and _.';
        }
        if (!array_key_exists($this->type, self::TYPES)) {
            $errors[] = "Unknown type '{$this->type}'.";
        }
        if ($this->type === self::TYPE_SCORE && !array_key_exists((string) $this->component, UpworkRuleset::COMPONENTS)) {
            $errors[] = 'Score rules need a valid component.';
        }
        if ($this->type === self::TYPE_CLASSIFICATION && !preg_match('/^[A-Z0-9_]{2,50}$/', (string) $this->classification)) {
            $errors[] = 'Classification rules need a label like QUICK_WIN (A-Z, 0-9, _).';
        }
        if ($this->type === self::TYPE_HARD_FILTER && trim((string) $this->reason) === '') {
            $errors[] = 'Hard filters need a reason so rejections are explainable.';
        }

        foreach (RuleConditions::validate($this->conditions) as $error) {
            $errors[] = $error;
        }

        return $errors;
    }

    private function guardDraft(): void
    {
        $status = $this->relationLoaded('ruleset')
            ? optional($this->ruleset)->status
            : UpworkRuleset::whereKey($this->ruleset_id)->value('status');

        if ($status !== UpworkRuleset::STATUS_DRAFT) {
            throw new \DomainException('Rules can only be changed in a draft ruleset.');
        }
    }
}
