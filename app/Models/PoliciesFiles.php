<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoliciesFiles extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'policy_id','document_link','document_name', 'document_type'
    ];
    
}
