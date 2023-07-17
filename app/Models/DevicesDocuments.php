<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevicesDocuments extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'device_id',
        'document',
    ];
}
