<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectLogSetting extends Model
{
    protected $fillable = [
        'project_id',
        'updated_by',
        'enabled'
    ];
}
