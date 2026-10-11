<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpworkTechnologyAlias extends Model
{
    protected $fillable = ['technology_id', 'alias', 'alias_normalized'];

    protected static function booted()
    {
        static::saving(function (UpworkTechnologyAlias $alias) {
            $alias->alias = trim(preg_replace('/\s+/u', ' ', $alias->alias));
            $alias->alias_normalized = self::normalize($alias->alias);
        });
    }

    public function technology()
    {
        return $this->belongsTo(UpworkTechnology::class, 'technology_id');
    }

    /** Lowercase, single-spaced form used for uniqueness and matching. */
    public static function normalize(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($text)));
    }
}
