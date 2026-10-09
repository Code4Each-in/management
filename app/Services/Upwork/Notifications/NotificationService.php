<?php

namespace App\Services\Upwork\Notifications;

use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use Illuminate\Support\Facades\Log;

/**
 * Notifies reviewers about new APPLY / REVIEW opportunities once they are
 * enriched (full details, so every hard filter has run). SKIP never notifies;
 * each opportunity notifies once per level (a REVIEW that later becomes APPLY
 * notifies again). Channel failures never break ingestion.
 */
class NotificationService
{
    /** Channel name => notifier class. Add Slack/Telegram here. */
    const CHANNELS = [
        'log' => LogNotifier::class,
        'mail' => MailNotifier::class,
    ];

    const SUBJECTS = [
        UpworkOpportunity::RECOMMENDATION_APPLY => '🔥 New high-priority Upwork opportunity',
        UpworkOpportunity::RECOMMENDATION_REVIEW => 'New Upwork opportunity for review',
    ];

    public function maybeNotify(UpworkOpportunity $opportunity): ?string
    {
        $level = $opportunity->recommendation;
        // Only fully detailed jobs: search rows lack facts some hard filters need
        // (contractor type, hires/offers on the posting), so they could still be rejected.
        if (!isset(self::SUBJECTS[$level])
            || $opportunity->stage !== UpworkOpportunity::STAGE_ENRICHED
            || $opportunity->status !== UpworkOpportunity::STATUS_NEW
            || $opportunity->discovery_ignored
            || !empty($opportunity->hard_filters)
            || $opportunity->notified_level === $level
            || $opportunity->notified_level === UpworkOpportunity::RECOMMENDATION_APPLY) {
            return null;
        }

        $message = $this->message($opportunity, $level);
        foreach ((array) UpworkSetting::get('notifications.channels') as $channel) {
            $class = self::CHANNELS[$channel] ?? null;
            if (!$class) {
                continue;
            }
            try {
                app($class)->send($message);
            } catch (\Throwable $e) {
                Log::warning("[upwork] notification channel '{$channel}' failed: " . $e->getMessage(), ['opportunity_id' => $opportunity->id]);
            }
        }

        $opportunity->forceFill(['notified_at' => now(), 'notified_level' => $level])->saveQuietly();

        return $level;
    }

    public function message(UpworkOpportunity $o, string $level): array
    {
        $lines = [
            'Score: ' . ($o->score !== null ? round($o->score, 1) : '—') . '/100 (' . strtoupper($level) . ')',
            'Budget: ' . $o->budget_label . ' · Client: ' . ($o->client_country ?? '—')
                . ($o->client_rating !== null ? ' · rating ' . number_format($o->client_rating, 2) : '')
                . ($o->client_total_spent !== null ? ' · spent ' . UpworkOpportunity::money($o->client_total_spent) : ''),
        ];
        if ($o->classifications) {
            $lines[] = 'Classifications: ' . implode(', ', $o->classifications);
        }
        if ($o->strategic_score !== null) {
            $lines[] = 'Strategic value: ' . round($o->strategic_score) . '/100';
        }
        if ($o->expected_value !== null) {
            $lines[] = 'Expected value (estimate): ~' . UpworkOpportunity::money($o->expected_value) . ' at ~' . round($o->win_probability) . '% win probability';
        }

        return [
            'subject' => self::SUBJECTS[$level],
            'level' => $level,
            'title' => $o->title,
            'url' => route('upwork.opportunities.show', $o),
            'lines' => $lines,
        ];
    }
}
