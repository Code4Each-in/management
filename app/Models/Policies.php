<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Policies extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name', 'policy_text',
    ];

    public function PolicyDocuments()
    {
        return $this->hasMany(PoliciesFiles::class, 'policy_id');
    }
}
