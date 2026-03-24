<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory ,SoftDeletes;
    
    protected $fillable = [
        'title',
        'message',
        'is_active',
        'end_date',
        'show_to_client'
    ];

    protected $casts = [
        'end_date' => 'date',
        'show_to_client' => 'boolean',
    ];
}
