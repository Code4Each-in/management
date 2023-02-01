<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Users extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable =[
        'first_name',
		'last_name',
        'email',
        'password',
		'salary',
		'phone',
		'address',
		'role_id',
		'department_id',
		'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
		
		/**
	 * Always encrypt the password when it is updated.
	 *
	  * @param $value
	 * @return string
	 */
	public function setPasswordAttribute($value)
	{
	   $this->attributes['password'] = bcrypt($value);
	}
	
	public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }
	
	public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }
}
