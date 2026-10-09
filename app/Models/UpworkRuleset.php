<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A version of the scoring configuration: component weights, thresholds,
 * settings and rules. Only a draft can be edited. Publishing freezes it under
 * the next version number and archives the previous published version.
 */
class UpworkRuleset extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Score components: key => [opportunity column, label, default weight].
     * The set is fixed (each has a column); the weights are configurable.
     */
    const COMPONENTS = [
        'technical_fit' => ['technical_fit_score', 'Technical / project fit', 20],
        'experience_fit' => ['experience_fit_score', 'Proven Code4Each experience', 20],
        'client_quality' => ['client_quality_score', 'Client quality & hiring behaviour', 20],
        'commercial_value' => ['commercial_value_score', 'Budget / commercial value', 15],
        'long_term' => ['long_term_score', 'Long-term potential', 10],
        'ai_automation' => ['ai_automation_score', 'AI / automation strategic value', 10],
        'competition' => ['competition_score', 'Competition / win probability', 5],
    ];

    protected $fillable = ['weights', 'thresholds', 'settings', 'notes', 'based_on_id', 'created_by'];

    protected $casts = [
        'weights' => 'array',
        'thresholds' => 'array',
        'settings' => 'array',
        'snapshot' => 'array',
        'published_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected static function booted()
    {
        static::updating(function (UpworkRuleset $ruleset) {
            $wasDraft = $ruleset->getOriginal('status') === self::STATUS_DRAFT;
            $archiving = $ruleset->getOriginal('status') === self::STATUS_PUBLISHED
                && $ruleset->status === self::STATUS_ARCHIVED
                && array_diff(array_keys($ruleset->getDirty()), ['status', 'updated_at']) === [];

            if (!$wasDraft && !$archiving) {
                throw new \DomainException("Ruleset v{$ruleset->version} is {$ruleset->getOriginal('status')} and cannot be changed. Create a new draft instead.");
            }
        });

        static::deleting(function (UpworkRuleset $ruleset) {
            if ($ruleset->status !== self::STATUS_DRAFT) {
                throw new \DomainException('Only draft rulesets can be deleted.');
            }
        });
    }

    public function rules()
    {
        return $this->hasMany(UpworkRule::class, 'ruleset_id')->orderBy('sort')->orderBy('id');
    }

    public function basedOn()
    {
        return $this->belongsTo(self::class, 'based_on_id');
    }

    public function publisher()
    {
        return $this->belongsTo(Users::class, 'published_by');
    }

    /** The ruleset new opportunities are scored with. */
    public static function current(): ?self
    {
        return static::where('status', self::STATUS_PUBLISHED)->orderByDesc('version')->first();
    }

    public static function draft(): ?self
    {
        return static::where('status', self::STATUS_DRAFT)->latest('id')->first();
    }

    /** The existing draft, or a new draft copied from the current published version. */
    public static function draftOrCreate(?int $userId = null): self
    {
        if ($draft = static::draft()) {
            return $draft;
        }

        $current = static::current();

        return DB::transaction(function () use ($current, $userId) {
            $draft = static::create([
                'weights' => $current->weights ?? static::defaultWeights(),
                'thresholds' => $current->thresholds ?? ['apply' => 80, 'review' => 70],
                'settings' => $current->settings ?? [],
                'based_on_id' => $current->id ?? null,
                'created_by' => $userId,
            ]);

            foreach ($current ? $current->rules : [] as $rule) {
                $draft->rules()->create($rule->only([
                    'key', 'name', 'type', 'component', 'points', 'classification', 'conditions', 'reason', 'enabled', 'sort',
                ]));
            }

            return $draft;
        });
    }

    public static function defaultWeights(): array
    {
        return array_map(fn($component) => $component[2], self::COMPONENTS);
    }

    /**
     * Weights scaled so they always total 100, whatever the admin entered.
     *
     * @return array<string, float>
     */
    public function normalizedWeights(): array
    {
        $weights = [];
        foreach (array_keys(self::COMPONENTS) as $key) {
            $weights[$key] = max(0.0, (float) ($this->weights[$key] ?? 0));
        }

        $sum = array_sum($weights);
        if ($sum <= 0) {
            return array_map(fn() => 0.0, $weights);
        }

        return array_map(fn($w) => round($w * 100 / $sum, 4), $weights);
    }

    public function setting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * @return string[] Problems that block publishing; empty when publishable.
     */
    public function validationErrors(): array
    {
        $errors = [];

        $weights = $this->weights ?? [];
        foreach (array_keys(self::COMPONENTS) as $key) {
            if (!isset($weights[$key]) || !is_numeric($weights[$key]) || $weights[$key] < 0) {
                $errors[] = "Weight for '{$key}' must be a number >= 0.";
            }
        }
        if (array_diff(array_keys($weights), array_keys(self::COMPONENTS))) {
            $errors[] = 'Weights contain unknown components: ' . implode(', ', array_diff(array_keys($weights), array_keys(self::COMPONENTS))) . '.';
        }
        if (!$errors && array_sum(array_map('floatval', $weights)) <= 0) {
            $errors[] = 'At least one component weight must be greater than 0.';
        }

        $apply = $this->thresholds['apply'] ?? null;
        $review = $this->thresholds['review'] ?? null;
        if (!is_numeric($apply) || !is_numeric($review) || $review < 0 || $apply > 100 || $review >= $apply) {
            $errors[] = 'Thresholds need 0 <= review < apply <= 100.';
        }

        foreach ($this->rules as $rule) {
            foreach ($rule->validationErrors() as $error) {
                $errors[] = "Rule '{$rule->key}': {$error}";
            }
        }

        // Classification rules must assign a label from the catalog (catches typos like QUIK_WIN).
        $catalog = UpworkClassification::pluck('code')->all();
        if ($catalog) {
            foreach ($this->rules->where('type', UpworkRule::TYPE_CLASSIFICATION) as $rule) {
                if (!in_array($rule->classification, $catalog, true)) {
                    $errors[] = "Rule '{$rule->key}': classification '{$rule->classification}' is not in the classification catalog.";
                }
            }
        }

        return $errors;
    }

    /** Freeze this draft as the next version and archive the previous published one. */
    public function publish(?int $userId = null): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \DomainException('Only a draft can be published.');
        }

        $this->load('rules');
        if ($errors = $this->validationErrors()) {
            throw new \DomainException('Ruleset cannot be published: ' . implode(' ', $errors));
        }

        DB::transaction(function () use ($userId) {
            $nextVersion = (int) static::lockForUpdate()->max('version') + 1;

            static::where('status', self::STATUS_PUBLISHED)->get()->each(function (UpworkRuleset $old) {
                $old->status = self::STATUS_ARCHIVED;
                $old->save();
            });

            $this->version = $nextVersion;
            $this->status = self::STATUS_PUBLISHED;
            $this->published_at = now();
            $this->published_by = $userId;
            $this->snapshot = [
                'version' => $nextVersion,
                'weights' => $this->weights,
                'normalized_weights' => $this->normalizedWeights(),
                'thresholds' => $this->thresholds,
                'settings' => $this->settings,
                'rules' => $this->rules->map->only([
                    'key', 'name', 'type', 'component', 'points', 'classification', 'conditions', 'reason', 'enabled', 'sort',
                ])->values()->all(),
            ];
            $this->save();
        });
    }

    public function getLabelAttribute(): string
    {
        return $this->version ? "v{$this->version}" : 'Draft';
    }
}
