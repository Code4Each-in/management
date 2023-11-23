<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\SoftDeletes;

class Users extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable ,SoftDeletes;

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
        'employee_id',
		'phone',
        'joining_date',
        'birth_date',
        'profile_picture',
		'address',
		'city',
		'state',
		'zip',
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
	   $this->attributes['password'] = Hash::make($value);
	}
	
	public function role()
    {
        return $this->belongsTo(Roles::class, 'role_id');
    }
	
	public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }
    //   public function permissions()
    // {
    //     return $this->belongsToMany(RolePermission::class, 'role_id');
    // }
    // public function hasPermission($permission)
    // {
    //     // dd($permission->id);
    //     // Logic to check if the user has the specified permissiond
    //     // dd($this->permissions()->where('role_id', $permission->id)->exists());
    //     return $this->permissions()->where('role_id', $permission->id)->exists();
    // }
   
    // public function hasRole($role)
    // {
    //     // Logic to check if the user has the specified role
    //     return $this->role()->where('name', $role->name)->exists();   
    // }



    public function isAdmin()
    {
        if($this->role_id === 1)
        { 
            return true; 
        } 
        else 
        { 
            return false; 
        }
    }

    public function documents()
    {
        return $this->hasMany(UserDocuments::class, 'user_id');
    }


    public function assignedDevices()
    {
        return $this->hasMany(AssignedDevices::class);
    }
    
    public function userAttendances()
    {
        return $this->hasMany(UserAttendance::class, 'user_id');
    }
}