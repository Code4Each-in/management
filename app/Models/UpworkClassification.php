<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A label classification rules may assign. Archetypes describe the kind of
 * opportunity (QUICK_WIN, STRATEGIC, ...); categories describe the technology
 * or domain (LARAVEL, SAAS, ...). An opportunity can carry several.
 */
class UpworkClassification extends Model
{
    const KIND_ARCHETYPE = 'archetype';
    const KIND_CATEGORY = 'category';

    protected $fillable = ['code', 'label', 'kind', 'description', 'color', 'enabled', 'surface', 'sort'];

    protected $casts = [
        'enabled' => 'boolean',
        'surface' => 'boolean',
    ];

    /** @return string[] enabled codes that keep a job in the default list even when its score says SKIP */
    public static function surfacedCodes(): array
    {
        return static::where('enabled', true)->where('surface', true)->pluck('code')->all();
    }

    protected static function booted()
    {
        static::saving(function (UpworkClassification $c) {
            $c->code = strtoupper(trim($c->code));
            if (!preg_match('/^[A-Z0-9_]{2,50}$/', $c->code)) {
                throw new \InvalidArgumentException("Classification code '{$c->code}' must be 2-50 characters of A-Z, 0-9 and _.");
            }
            if (!in_array($c->kind, [self::KIND_ARCHETYPE, self::KIND_CATEGORY], true)) {
                throw new \InvalidArgumentException("Unknown classification kind '{$c->kind}'.");
            }
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort')->orderBy('label');
    }

    /** code => model, for rendering chips. */
    public static function catalog(): array
    {
        return static::ordered()->get()->keyBy('code')->all();
    }
}
