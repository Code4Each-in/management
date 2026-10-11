<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpworkTechnologyRelation extends Model
{
    protected $fillable = ['technology_id', 'related_technology_id', 'relation_type', 'weight'];

    protected $casts = [
        'weight' => 'float',
    ];

    public function technology()
    {
        return $this->belongsTo(UpworkTechnology::class, 'technology_id');
    }

    public function relatedTechnology()
    {
        return $this->belongsTo(UpworkTechnology::class, 'related_technology_id');
    }
}
