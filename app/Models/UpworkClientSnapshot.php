<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** The client's Upwork-reported figures at one point in time (written only when they change). */
class UpworkClientSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'upwork_client_id', 'opportunity_id', 'rating', 'reviews_count', 'total_spent', 'hire_rate', 'jobs_posted', 'jobs_hired', 'recorded_at',
    ];

    protected $casts = [
        'rating' => 'float',
        'total_spent' => 'float',
        'hire_rate' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(UpworkClient::class, 'upwork_client_id');
    }
}
