<?php

namespace App\Services\Upwork\Notifications;

/**
 * A notification channel (log, mail; later Slack, Telegram, ...).
 * Implementations must not call Upwork and must not include secrets.
 */
interface OpportunityNotifier
{
    /** @param array $message subject, level, title, url, lines[] */
    public function send(array $message): void;
}
