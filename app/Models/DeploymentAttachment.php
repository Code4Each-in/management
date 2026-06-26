<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeploymentAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'deployment_ticket_id',
        'type',
        'original_name',
        'file_path',
        'mime_type',
        'uploaded_by',
    ];

    public function ticket()
    {
        return $this->belongsTo(DeploymentTicket::class, 'deployment_ticket_id');
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\Users::class, 'uploaded_by');
    }

    public function url(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
