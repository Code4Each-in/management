<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  

class Feedback extends Model
{
    use HasFactory, SoftDeletes;  
    protected $table = 'feedbacks';
    protected $fillable = [
        'developer_id', 
        'feedback', 
        'created_by', 
    ];
    public function developer()
{
    return $this->belongsTo(Users::class, 'developer_id');
}
public function client()
{
    return $this->belongsTo(Users::class, 'created_by');
}

}
