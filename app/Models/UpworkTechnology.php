<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One entry in the Code4Each capability catalog. The tier is what the scoring
 * engine and reviewers see: PROVEN must be backed by recorded evidence so the
 * system never claims experience Code4Each does not have.
 */
class UpworkTechnology extends Model
{
    const TIER_PROVEN = 'proven';
    const TIER_ADJACENT = 'adjacent';
    const TIER_EMERGING = 'emerging';
    const TIER_EXCLUDED = 'excluded';

    const TIERS = [
        self::TIER_PROVEN => 'Proven',
        self::TIER_ADJACENT => 'Adjacent',
        self::TIER_EMERGING => 'Emerging',
        self::TIER_EXCLUDED => 'Excluded',
    ];

    protected $fillable = [
        'name', 'slug', 'category', 'tier', 'search_weight', 'scoring_weight', 'evidence', 'notes', 'enabled',
    ];

    protected $casts = [
        'search_weight' => 'float',
        'scoring_weight' => 'float',
        'enabled' => 'boolean',
    ];

    protected $attributes = [
        'tier' => self::TIER_ADJACENT,
        'enabled' => true,
    ];

    protected static function booted()
    {
        static::saving(function (UpworkTechnology $technology) {
            if (!array_key_exists($technology->tier, self::TIERS)) {
                throw new \InvalidArgumentException("Unknown technology tier '{$technology->tier}'.");
            }
            if ($technology->tier === self::TIER_PROVEN && trim((string) $technology->evidence) === '') {
                throw new \DomainException("'{$technology->name}' cannot be PROVEN without recorded evidence.");
            }
            if (!$technology->slug) {
                $technology->slug = Str::slug($technology->name);
            }
        });
    }

    public function aliases()
    {
        return $this->hasMany(UpworkTechnologyAlias::class, 'technology_id');
    }

    public function relations()
    {
        return $this->hasMany(UpworkTechnologyRelation::class, 'technology_id');
    }

    public function relatedTechnologies()
    {
        return $this->belongsToMany(self::class, 'upwork_technology_relations', 'technology_id', 'related_technology_id')
            ->withPivot(['relation_type', 'weight'])
            ->withTimestamps();
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function addAlias(string $alias): UpworkTechnologyAlias
    {
        return $this->aliases()->firstOrCreate(
            ['alias_normalized' => UpworkTechnologyAlias::normalize($alias)],
            ['alias' => trim($alias)]
        );
    }

    public function relateTo(UpworkTechnology $related, float $weight = 0.5, string $type = 'related'): UpworkTechnologyRelation
    {
        if ($related->id === $this->id) {
            throw new \InvalidArgumentException('A technology cannot be related to itself.');
        }

        return $this->relations()->updateOrCreate(
            ['related_technology_id' => $related->id],
            ['weight' => max(0, min(1, $weight)), 'relation_type' => $type]
        );
    }

    public function getTierLabelAttribute(): string
    {
        return self::TIERS[$this->tier] ?? ucfirst($this->tier);
    }
}
