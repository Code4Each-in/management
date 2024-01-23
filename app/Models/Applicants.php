<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applicants extends Model
{
    use HasFactory ,SoftDeletes;
    protected $table = 'applicants';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'resume',
        'links',
        'otp',
        'expired_at',
        'status',
        'job_id'
    ];
}
