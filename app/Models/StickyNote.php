<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Users;

class StickyNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sticky_notes'; // table name

    protected $fillable = [
        'recordcreated',
        'userid',
        'notes',
    ];

    protected $dates = [
        'recordcreated',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'userid');
    }
}
