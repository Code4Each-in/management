<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = ['job_title', 'job_link', 'source', 'profile', 'status', 'bdesprint_id', 'created_by'];

    public function creator()
{
    return $this->belongsTo(Users::class, 'created_by');
}
}
