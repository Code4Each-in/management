<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAttendancesTemporary extends Model
{
    use HasFactory;
    protected $table = 'user_attendances_temporary';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'date',
        'in_time',
        'out_time_date'
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id');
    }
}
