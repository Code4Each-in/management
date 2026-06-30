<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentBug extends Model
{
    protected $fillable = ['deployment_ticket_id','title','description','severity','screenshot','status'];

    public function ticket() { return $this->belongsTo(DeploymentTicket::class, 'deployment_ticket_id'); }
}
