<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pages extends Model
{
    use HasFactory;
	
	 protected $fillable =[
        'name',     
    ];
	
	public function module()
    {
        return $this->hasMany(Modules::class, 'page_id');
    }
}
