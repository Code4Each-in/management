<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivateComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'private_comments';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'update_date',
        'update_text',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
}
