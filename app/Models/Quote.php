<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    protected $table = 'quotes';  
    protected $fillable = ['quote', 'start_date', 'end_date']; 
    protected $dates = ['start_date', 'end_date', 'created_at', 'updated_at'];
}
