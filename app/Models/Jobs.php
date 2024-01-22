<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jobs extends Model
{
    use HasFactory, SoftDeletes;

      /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'experience',
        'job_category_id',
        'status',
        'location',
        'salary',
        'skills' 
    ];

    public function jobcategory()
    {
        return $this->belongsTo(JobCategories::class, 'job_category_id');
    }
}
