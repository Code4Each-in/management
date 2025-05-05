<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory, SoftDeletes;  

    protected $table = 'project_messages';  
    protected $fillable = ['project_id', 'user_id', 'message', 'is_read', 'created_at', 'updated_at'];
    protected $dates = ['deleted_at'];
}

