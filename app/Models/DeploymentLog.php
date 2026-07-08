<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['deployment_ticket_id','user_id','old_status','new_status','created_at'];

    protected $casts = [
        'created_at' => 'datetime', // <-- add this
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($log) {
            $log->created_at = $log->created_at ?? now();
        });
    }

    public function ticket() { return $this->belongsTo(DeploymentTicket::class, 'deployment_ticket_id'); }
    public function user() { return $this->belongsTo(Users::class); }
}
