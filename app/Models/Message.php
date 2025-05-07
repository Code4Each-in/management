<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory, SoftDeletes;  

    protected $table = 'project_messages';  
    protected $fillable = ['project_id', 'user_id', 'message', 'from','to','document', 'is_read_from','is_read_to', 'created_at', 'updated_at'];
    protected $dates = ['deleted_at'];


    public function user()
    {
        return $this->belongsTo(Users::class, 'from');
    }
    public function project()
    {
        return $this->belongsTo(Projects::class);
    }

}

