<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentAttachment extends Model
{
    protected $fillable = ['deployment_ticket_id','type','file_path','original_name'];

    public function ticket() { return $this->belongsTo(DeploymentTicket::class, 'deployment_ticket_id'); }
}
