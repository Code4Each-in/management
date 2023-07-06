<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Devices extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
    'name',
    'brand',
    'serial_number',
    'device_model',
    'buying_date',
    ];
    protected $casts = [
        'status' => 'boolean'
    ];

    public function assignedDevices()
    {
        return $this->hasMany(AssignedDevices::class);
    }
}
