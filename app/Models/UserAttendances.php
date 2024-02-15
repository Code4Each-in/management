<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAttendances extends Model
{
    use HasFactory;
    protected $table = 'user_attendances';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'in_time',
        'out_time',
        'notes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
