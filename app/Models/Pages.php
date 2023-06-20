<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pages extends Model
{
    use HasFactory;
	
	 protected $fillable =[
        'name',  'parent_id',
    ];
    
    public $timestamps = true;
	
	public function module()
    {
        return $this->hasMany(Modules::class, 'page_id');
    }


    public function parentpage()
    {
        return $this->belongsTo(Pages::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Pages::class, 'parent_id');
    }


}
