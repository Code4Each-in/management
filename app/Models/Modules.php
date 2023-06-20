<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modules extends Model
{
    use HasFactory;
	protected $fillable =[
        'page_id',  
		'module_name',
        'route_name',
    ];
    // protected $casts = [
    //     'route_name' => 'nullable',
    // ];

    public function page()
    {
        return $this->belongsTo(Pages::class,);
    }
}
