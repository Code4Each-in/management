<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAccessRequest extends Model
{
    use HasFactory;

    protected $table = 'client_access_requests'; 

    protected $fillable = [
        'user_id',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(Users::class);
    }
}
