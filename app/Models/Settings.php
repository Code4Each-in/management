<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $fillable = [
        'is_active',
        'start_time',
        'end_time',
        'skip_weekends'
    ];
}