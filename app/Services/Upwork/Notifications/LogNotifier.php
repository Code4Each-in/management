<?php

namespace App\Services\Upwork\Notifications;

use Illuminate\Support\Facades\Log;

class LogNotifier implements OpportunityNotifier
{
    public function send(array $message): void
    {
        Log::info('[upwork] ' . $message['subject'], [
            'level' => $message['level'],
            'title' => $message['title'],
            'url' => $message['url'],
            'details' => $message['lines'],
        ]);
    }
}
