<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssigns extends Model
{
    use HasFactory;
    protected $fillable = [
        'project_id',
        'user_id',
  
    ];
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id','id');
    }
}
