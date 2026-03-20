<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'name',
        'subject',
        'body',
        'category',       // NEW
        'banner_image',   // NEW
    ];

    // Replaces placeholders with actual client data
    public function renderFor(Client $client, $project = null): string
    {
        return str_replace(
            [
                '{{client_name}}',
                '{{company_name}}',
                '{{project_name}}',
                '{{sender_name}}',
            ],
            [
                $client->name,
                config('app.name'),
                $project?->name ?? '',
                auth()->user()?->name ?? config('app.name'),
            ],
            $this->body
        );
    }

    public function schedules()
    {
        return $this->hasMany(ScheduledEmail::class);
    }
}
