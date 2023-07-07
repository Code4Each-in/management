<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignedDevices extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'device_id',
        'user_id',
        'from',
        'to',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function device()
    {
        return $this->belongsTo(Devices::class);
    }
}
