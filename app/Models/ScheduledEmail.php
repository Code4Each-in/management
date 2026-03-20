<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledEmail extends Model
{
    protected $fillable = [
        'template_id',
        'project_id',   // NEW
        'send_at',
        'status',       // scheduled / sent / failed
    ];

    protected $casts = [
        'send_at' => 'datetime',  // NEW — so you can use ->send_at->format()
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function project()
    {
        return $this->belongsTo(Projects::class);  // NEW
    }

    public function recipients()
    {
        return $this->hasMany(ScheduledEmailRecipient::class);
    }
}
