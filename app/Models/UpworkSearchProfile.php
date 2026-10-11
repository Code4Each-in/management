<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A configurable discovery search. Editing anything that changes what the
 * agent searches for or keeps bumps `version`, so every discovery run and
 * opportunity can record exactly which profile definition found it.
 */
class UpworkSearchProfile extends Model
{
    /** List fields: stored as de-duplicated, trimmed JSON arrays. */
    const LIST_FIELDS = [
        'keywords', 'exact_phrases', 'required_keywords', 'optional_keywords', 'excluded_keywords', 'excluded_phrases',
    ];

    /** Changing any of these changes search behaviour, so it bumps the version. */
    const VERSIONED_FIELDS = [
        'keywords', 'exact_phrases', 'required_keywords', 'optional_keywords', 'excluded_keywords',
        'excluded_phrases', 'upwork_filters', 'min_score', 'category', 'max_results', 'search_related',
    ];

    /** Upwork find_jobs search filters a profile may set (read-only search parameters only). */
    const UPWORK_FILTERS = [
        'job_type', 'experience_level', 'verified_payment_only', 'rate_min', 'rate_max', 'budget_min', 'budget_max',
        'workload', 'duration', 'proposals_max', 'client_hires_min', 'category', 'subcategory', 'location', 'sort', 'pages',
    ];

    const TIERS = [
        'hot' => 'Hot',
        'normal' => 'Normal',
        'deep' => 'Deep',
        'exploratory' => 'Exploratory',
    ];

    protected $fillable = [
        'name', 'slug', 'description', 'enabled', 'priority', 'category', 'tier', 'daily_call_budget', 'max_results', 'search_related',
        'keywords', 'exact_phrases', 'required_keywords', 'optional_keywords', 'excluded_keywords', 'excluded_phrases',
        'upwork_filters', 'min_score', 'frequency_minutes', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'search_related' => 'boolean',
        'keywords' => 'array',
        'exact_phrases' => 'array',
        'required_keywords' => 'array',
        'optional_keywords' => 'array',
        'excluded_keywords' => 'array',
        'excluded_phrases' => 'array',
        'upwork_filters' => 'array',
        'min_score' => 'float',
        'last_run_at' => 'datetime',
        'last_success_at' => 'datetime',
        'backoff_until' => 'datetime',
    ];

    protected $attributes = [
        'enabled' => true,
        'priority' => 100,
        'tier' => 'normal',
        'frequency_minutes' => 120,
        'daily_call_budget' => 30,
        'max_results' => 10,
        'search_related' => false,
        'version' => 1,
    ];

    protected static function booted()
    {
        static::saving(function (UpworkSearchProfile $profile) {
            if (!$profile->slug) {
                $profile->slug = Str::slug($profile->name);
            }
            if (!array_key_exists($profile->tier, self::TIERS)) {
                throw new \InvalidArgumentException("Unknown search tier '{$profile->tier}'.");
            }

            foreach (self::LIST_FIELDS as $field) {
                $profile->{$field} = self::cleanList($profile->{$field});
            }

            if (is_array($profile->upwork_filters)) {
                $profile->upwork_filters = array_intersect_key($profile->upwork_filters, array_flip(self::UPWORK_FILTERS)) ?: null;
            }

            if ($profile->exists && $profile->isDirty(self::VERSIONED_FIELDS)) {
                $profile->version = (int) $profile->getOriginal('version') + 1;
            }
        });
    }

    public function technologies()
    {
        return $this->belongsToMany(UpworkTechnology::class, 'upwork_search_profile_technology', 'search_profile_id', 'technology_id');
    }

    /** Replace related technologies; bumps the version when the set changes. */
    public function syncTechnologies(array $technologyIds): void
    {
        $changes = $this->technologies()->sync(array_values(array_unique(array_map('intval', $technologyIds))));

        if ($changes['attached'] || $changes['detached']) {
            $this->increment('version');
        }
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('name');
    }

    public static function cleanList($values): ?array
    {
        if ($values === null) {
            return null;
        }

        $clean = [];
        foreach ((array) $values as $value) {
            $value = trim(preg_replace('/\s+/u', ' ', (string) $value));
            if ($value !== '' && !in_array(mb_strtolower($value), array_map('mb_strtolower', $clean), true)) {
                $clean[] = $value;
            }
        }

        return $clean ?: null;
    }
}
