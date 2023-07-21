<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLeaves extends Model
{

      /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'from',
        'to',
        'type',  
        'notes',  
        'half_day',
        'leave_day_count', 
    ];

    public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }
	

    use HasFactory;
}