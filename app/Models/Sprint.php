<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sprint extends Model
{
    use HasFactory, Notifiable,SoftDeletes;
    protected $fillable = [
        'name',
        'eta',
        'start_date',
        'client',
        'project',
        'description',
        'status'
    ];
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id','id');
    }
}