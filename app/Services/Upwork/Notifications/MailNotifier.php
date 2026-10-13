<?php

namespace App\Services\Upwork\Notifications;

use App\Models\UpworkSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** Plain-text email to the addresses in the notifications.mail_to setting. */
class MailNotifier implements OpportunityNotifier
{
    public function send(array $message): void
    {
        $recipients = array_values(array_filter((array) UpworkSetting::get('notifications.mail_to'), fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
        if (!$recipients) {
            Log::warning('[upwork] mail notification skipped: no recipients configured (notifications.mail_to).');
            return;
        }

        $actions = '';
        foreach ((array) ($message['actions'] ?? []) as $label => $url) {
            $actions .= "\n- {$label}: {$url}";
        }

        $body = $message['title'] . "\n\n" . implode("\n", $message['lines']) . "\n\nOpen in the PMS: " . $message['url']
            . ($actions !== '' ? "\n\nNot now? Each link opens a confirmation page in the PMS (sign-in required); nothing changes until you confirm there:" . $actions : '')
            . (in_array($message['level'] ?? null, ['alert', 'resolved'], true)
                ? "\n\nThis is an operational notice from the PMS. Nothing has been sent to Upwork."
                : "\n\nNothing has been sent to Upwork. Review and decide in the PMS.");

        Mail::raw($body, function ($mail) use ($recipients, $message) {
            $mail->to($recipients)->subject($message['subject'] . ': ' . $message['title']);
        });
    }
}
