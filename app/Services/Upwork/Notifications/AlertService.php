<?php

namespace App\Services\Upwork\Notifications;

use App\Models\UpworkSetting;
use Illuminate\Support\Facades\Log;

/**
 * Operational alerts (discovery failures, Upwork connection problems, token
 * refresh failures) through the module's existing notification channels.
 *
 * - One alert per problem per cooldown window (alerts.cooldown_minutes); a
 *   repeating failure only increments its counter in between.
 * - One "resolved" notice once a problem that was alerted clears.
 * - Text is scrubbed of anything that looks like a credential before sending.
 * - A failing channel never breaks discovery or token refresh.
 */
class AlertService
{
    const DISCOVERY_FAILED = 'discovery_failed';
    const UPWORK_CONNECTION = 'upwork_connection';
    const TOKEN_REFRESH = 'token_refresh';

    const STATE_KEY = 'alerts.state';

    /** @return bool whether an alert was sent (false while inside the cooldown) */
    public function raise(string $key, string $title, array $lines = []): bool
    {
        $state = (array) UpworkSetting::get(self::STATE_KEY);
        $entry = (array) ($state[$key] ?? []);
        $entry['count'] = (int) ($entry['count'] ?? 0) + 1;
        $entry['first_at'] = $entry['first_at'] ?? now()->toIso8601String();
        $entry['last_at'] = now()->toIso8601String();
        $entry['title'] = self::scrub($title);

        $cooldown = max(1, (int) UpworkSetting::get('alerts.cooldown_minutes'));
        $lastSent = isset($entry['last_sent_at']) ? \Illuminate\Support\Carbon::parse($entry['last_sent_at']) : null;
        $send = !$lastSent || $lastSent->lte(now()->subMinutes($cooldown));

        if ($send) {
            $entry['last_sent_at'] = now()->toIso8601String();
            $suffix = $entry['count'] > 1 ? " (occurred {$entry['count']} times since " . $entry['first_at'] . ')' : '';
            $this->dispatch([
                'subject' => '⚠️ Upwork discovery alert',
                'level' => 'alert',
                'title' => self::scrub($title) . $suffix,
                'url' => route('upwork.config.settings.edit'),
                'lines' => array_map([self::class, 'scrub'], $lines),
            ]);
        }

        $state[$key] = $entry;
        UpworkSetting::set(self::STATE_KEY, $state);

        return $send;
    }

    /** Clear a problem; sends one "resolved" notice if an alert had gone out. */
    public function resolve(string $key, string $title): bool
    {
        $state = (array) UpworkSetting::get(self::STATE_KEY);
        if (!isset($state[$key])) {
            return false;
        }
        $wasAlerted = isset($state[$key]['last_sent_at']);
        unset($state[$key]);
        UpworkSetting::set(self::STATE_KEY, $state);

        if ($wasAlerted) {
            $this->dispatch([
                'subject' => '✅ Upwork discovery recovered',
                'level' => 'resolved',
                'title' => self::scrub($title),
                'url' => route('upwork.config.settings.edit'),
                'lines' => [],
            ]);
        }

        return $wasAlerted;
    }

    /** Labels for open alerts, by key (token refresh keys carry the connection label). */
    const LABELS = [
        self::DISCOVERY_FAILED => 'Discovery failed',
        self::UPWORK_CONNECTION => 'Upwork connection',
        self::TOKEN_REFRESH => 'Token refresh',
    ];

    /**
     * Problems that are still open, newest first, for the PMS pages.
     *
     * @return array[] {key, label, title, count, first_at, last_at, notified}
     */
    public static function open(): array
    {
        $open = [];
        foreach ((array) UpworkSetting::get(self::STATE_KEY) as $key => $entry) {
            $base = explode(':', (string) $key)[0];
            $open[] = [
                'key' => (string) $key,
                'label' => (self::LABELS[$base] ?? 'Alert') . (strpos((string) $key, ':') !== false ? ' (' . ucfirst(substr((string) $key, strlen($base) + 1)) . ')' : ''),
                'title' => (string) ($entry['title'] ?? ''),
                'count' => (int) ($entry['count'] ?? 1),
                'first_at' => isset($entry['first_at']) ? \Illuminate\Support\Carbon::parse($entry['first_at']) : null,
                'last_at' => isset($entry['last_at']) ? \Illuminate\Support\Carbon::parse($entry['last_at']) : null,
                'notified' => isset($entry['last_sent_at']),
            ];
        }
        usort($open, fn($a, $b) => ($b['last_at'] ? $b['last_at']->timestamp : 0) <=> ($a['last_at'] ? $a['last_at']->timestamp : 0));

        return $open;
    }

    /**
     * Whether alerts can reach a person by email: mail channel on, at least one
     * valid recipient, and a mailer that actually sends.
     *
     * @return array{channels: string[], recipients: int, mailer: string, problem: ?string}
     */
    public static function delivery(): array
    {
        $channels = array_values((array) UpworkSetting::get('notifications.channels'));
        $recipients = count(array_filter((array) UpworkSetting::get('notifications.mail_to'), fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));
        $mailer = (string) config('mail.default');
        $driver = (string) config("mail.mailers.{$mailer}.transport", $mailer);

        $problem = null;
        if (!in_array('mail', $channels, true)) {
            $problem = 'the mail channel is off (Agent & discovery → Notification channels)';
        } elseif ($recipients === 0) {
            $problem = 'no valid email recipients are set (Agent & discovery → Email recipients)';
        } elseif (in_array($driver, ['log', 'array', 'null'], true) || $mailer === '') {
            $problem = "the mailer '{$mailer}' does not send email (MAIL_MAILER in .env)";
        }

        return ['channels' => $channels, 'recipients' => $recipients, 'mailer' => $mailer, 'problem' => $problem];
    }

    /** Remove anything credential-like: bearer tokens, token fields, long opaque strings. */
    public static function scrub(string $text): string
    {
        $text = preg_replace('/Bearer\s+\S+/i', 'Bearer [redacted]', $text);
        // API keys, including provider-masked ones such as "sk-proj-****abcd".
        $text = preg_replace('/\b(?:sk|rk|pk)-[A-Za-z0-9_*\-]{4,}/', '[redacted]', $text);
        $text = preg_replace('/\b((?:access|refresh|id)_token|client_secret|code_verifier|authorization|password)(["\']?\s*[:=]\s*["\']?)[^\s"\',&]+/i', '$1$2[redacted]', $text);
        $text = preg_replace('/\b[A-Za-z0-9._~+\/=-]{32,}\b/', '[redacted]', $text);

        return mb_substr($text, 0, 1000);
    }

    private function dispatch(array $message): void
    {
        foreach ((array) UpworkSetting::get('notifications.channels') as $channel) {
            $class = NotificationService::CHANNELS[$channel] ?? null;
            if (!$class) {
                continue;
            }
            try {
                app($class)->send($message);
            } catch (\Throwable $e) {
                Log::warning("[upwork] alert channel '{$channel}' failed: " . self::scrub($e->getMessage()));
            }
        }
    }
}
