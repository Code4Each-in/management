<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyLeaves extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'leaves_count',
    ];
}
