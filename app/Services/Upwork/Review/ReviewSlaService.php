<?php

namespace App\Services\Upwork\Review;

use App\Models\UpworkOpportunity;
use App\Models\UpworkSetting;
use App\Services\Upwork\Notifications\NotificationService;
use Illuminate\Support\Carbon;

/**
 * Thirty-minute review SLA for new APPLY / REVIEW jobs.
 *
 *   eligible job stored -> review_due_at = first_seen_at + 30 min (set once, never moved)
 *   reminders at +10, +20, +25 min -> overdue at +30 -> a reminder every 10 min after that
 *
 * Reminders stop as soon as a person approves, rejects or archives the job,
 * while the job is snoozed, or when it is no longer eligible (e.g. re-scored
 * to SKIP). Every gate requires status "new", so an archived job is never
 * reminded; restoring it starts a fresh deadline (restartAfterRestore).
 * Each reminder level is claimed with a conditional update before it is sent,
 * so overlapping runners never send the same reminder twice; a missed tick
 * sends only the latest level, never a burst. Nothing here approves, rejects,
 * archives or applies to a job, and nothing calls Upwork.
 */
class ReviewSlaService
{
    const SNOOZE_PRESETS = ['1h', '4h', 'tomorrow', 'custom'];

    /** Same criteria as new-opportunity notifications: fully detailed, untouched, worth a look. */
    public static function isEligible(UpworkOpportunity $o): bool
    {
        return in_array($o->recommendation, [UpworkOpportunity::RECOMMENDATION_APPLY, UpworkOpportunity::RECOMMENDATION_REVIEW], true)
            && $o->stage === UpworkOpportunity::STAGE_ENRICHED
            && $o->status === UpworkOpportunity::STATUS_NEW
            && !$o->discovery_ignored
            && empty($o->hard_filters);
    }

    public static function deadlineMinutes(): int
    {
        return max(1, (int) config('upwork.review.deadline_minutes', 30));
    }

    public static function timezone(): string
    {
        return (string) (config('upwork.review.timezone') ?: config('app.timezone'));
    }

    /**
     * Start the review clock once, when an eligible job is first stored. The
     * deadline is first_seen_at + 30 min. A job that only becomes eligible after
     * that moment has passed (for example an old job re-scored by a new ruleset)
     * starts its clock now instead of arriving already overdue.
     */
    public function startIfEligible(UpworkOpportunity $o): bool
    {
        if ($o->review_due_at !== null || !$o->exists || !self::isEligible($o)) {
            return false;
        }

        $now = self::now();
        $start = ($o->first_seen_at ?? $now)->copy()->setTimezone(config('app.timezone'));
        if ($start->copy()->addMinutes(self::deadlineMinutes())->lte($now)) {
            $start = $now->copy();
        }
        $due = $start->addMinutes(self::deadlineMinutes());

        // Conditional: concurrent ingestion of the same job sets the deadline only once.
        if (!UpworkOpportunity::whereKey($o->id)->whereNull('review_due_at')->update(['review_due_at' => $due])) {
            return false;
        }
        $o->review_due_at = $due;
        $o->syncOriginalAttribute('review_due_at');
        $o->log('review_sla_started', null, null, null, 'Review due by ' . self::format($due));

        return true;
    }

    /**
     * Reminder level a job has reached at $at: 0 = none yet, 1..n = the
     * pre-deadline reminders, n+1 = overdue, then +1 per repeat interval.
     */
    public function stageAt(UpworkOpportunity $o, Carbon $at): int
    {
        if (!$o->review_due_at) {
            return 0;
        }
        $deadline = self::deadlineMinutes();
        $elapsed = $o->review_due_at->copy()->subMinutes($deadline)->diffInSeconds($at, false) / 60;
        $offsets = self::reminderOffsets();

        if ($elapsed >= $deadline) {
            $repeat = max(1, (int) config('upwork.review.repeat_minutes', 10));

            return count($offsets) + 1 + (int) floor(($elapsed - $deadline) / $repeat);
        }

        $stage = 0;
        foreach ($offsets as $i => $minutes) {
            if ($elapsed >= $minutes) {
                $stage = $i + 1;
            }
        }

        return $stage;
    }

    public static function overdueStage(): int
    {
        return count(self::reminderOffsets()) + 1;
    }

    public function isSnoozed(UpworkOpportunity $o, ?Carbon $at = null): bool
    {
        return $o->review_snoozed_until !== null && $o->review_snoozed_until->gt($at ?? now());
    }

    /**
     * One scheduler tick: mark newly overdue jobs (database only) and, when
     * reminders are switched on, send each job's latest due reminder once.
     *
     * @return array{overdue: int, sent: int, snoozed: int, capped: bool, enabled: bool}
     */
    public function run(?Carbon $now = null): array
    {
        $now = self::now($now);
        $enabled = (bool) UpworkSetting::get('review.reminders_enabled');
        $max = max(1, (int) config('upwork.review.max_reminders_per_run', 50));
        $firstReminder = self::reminderOffsets()[0] ?? self::deadlineMinutes();
        $stats = ['overdue' => 0, 'sent' => 0, 'snoozed' => 0, 'capped' => false, 'enabled' => $enabled];

        // Newest first: fresh jobs are where the SLA still matters most.
        $candidates = UpworkOpportunity::where('status', UpworkOpportunity::STATUS_NEW)
            ->whereNotNull('review_due_at')
            ->where('review_due_at', '<=', $now->copy()->addMinutes(self::deadlineMinutes() - $firstReminder))
            ->orderByDesc('review_due_at')
            ->limit($max * 10)
            ->get();

        foreach ($candidates as $o) {
            if (!self::isEligible($o)) {
                continue;
            }
            $stage = $this->stageAt($o, $now);

            if ($stage >= self::overdueStage() && $o->review_overdue_at === null
                && UpworkOpportunity::whereKey($o->id)->whereNull('review_overdue_at')->update(['review_overdue_at' => $now])) {
                $o->review_overdue_at = $now;
                $o->log('review_overdue', null, null, null, 'Not reviewed by ' . self::format($o->review_due_at) . '. The job stays new; nothing was changed automatically.');
                $stats['overdue']++;
            }

            if (!$enabled || $stage === 0 || $stage <= (int) $o->review_reminder_stage) {
                continue;
            }
            if ($this->isSnoozed($o, $now)) {
                $stats['snoozed']++;
                continue;
            }
            if ($stats['sent'] >= $max) {
                $stats['capped'] = true;
                break;
            }

            if (!$this->claimReminder($o, $stage, $now)) {
                continue;
            }
            app(NotificationService::class)->deliver($this->message($o, $stage, $now), ['opportunity_id' => $o->id]);
            $o->log('review_reminder', null, null, null, $this->stageLabel($o, $stage, $now));
            $stats['sent']++;
        }

        return $stats;
    }

    /**
     * Claim a reminder level right before sending, in one conditional UPDATE
     * against the database (not the copy loaded at the start of the run): only
     * one runner wins, and a job that was approved, rejected, archived or
     * snoozed since it was loaded is not reminded.
     */
    public function claimReminder(UpworkOpportunity $o, int $stage, Carbon $now): bool
    {
        $claimed = UpworkOpportunity::whereKey($o->id)
            ->where('status', UpworkOpportunity::STATUS_NEW)
            ->where(fn($q) => $q->whereNull('review_reminder_stage')->orWhere('review_reminder_stage', '<', $stage))
            ->where(fn($q) => $q->whereNull('review_snoozed_until')->orWhere('review_snoozed_until', '<=', $now))
            ->update(['review_reminder_stage' => $stage, 'review_reminded_at' => $now]);
        if ($claimed) {
            $o->forceFill(['review_reminder_stage' => $stage, 'review_reminded_at' => $now])->syncOriginal();
        }

        return (bool) $claimed;
    }

    /**
     * Restore policy: a job restored to review gets a fresh deadline (now +
     * 30 min) with reminder state cleared, so it behaves like a newly found
     * job: the first reminder comes at +10 min, never a burst of old levels.
     * The previous deadline, overdue mark and last reminder are written to the
     * activity log first (they were also recorded when it was archived).
     * first_seen_at and every earlier log entry stay as they were. A job that is
     * not eligible (e.g. now SKIP) gets no deadline.
     */
    public function restartAfterRestore(UpworkOpportunity $o, ?int $userId): bool
    {
        $previous = $o->review_due_at
            ? 'previous deadline ' . self::format($o->review_due_at)
                . ($o->review_overdue_at ? ', overdue since ' . self::format($o->review_overdue_at) : '')
                . ($o->review_reminder_stage ? ", last reminder #{$o->review_reminder_stage}" : '')
            : 'no previous deadline';
        $reset = ['review_overdue_at' => null, 'review_reminder_stage' => null, 'review_reminded_at' => null,
            'review_snoozed_until' => null, 'review_snoozed_by' => null];

        if (!self::isEligible($o)) {
            $o->forceFill($reset + ['review_due_at' => null])->save();
            $o->log('review_sla_restarted', $userId, null, null, "Restored; not eligible for review reminders ({$previous}).");

            return false;
        }

        $due = self::now()->addMinutes(self::deadlineMinutes());
        $o->forceFill($reset + ['review_due_at' => $due])->save();
        $o->log('review_sla_restarted', $userId, null, null, 'Restored to review: new deadline ' . self::format($due) . " ({$previous}).");

        return true;
    }

    /** The reminder: what is due, and links to the snooze confirmation page (GET never changes anything). */
    public function message(UpworkOpportunity $o, int $stage, Carbon $now): array
    {
        $overdue = $stage >= self::overdueStage();
        $tomorrow = $this->snoozeUntil('tomorrow', null, $now);

        return [
            'subject' => $overdue ? '🚨 Upwork review overdue' : '⏰ Upwork review reminder',
            'level' => 'reminder',
            'title' => $o->title,
            'url' => route('upwork.opportunities.show', $o),
            'lines' => [
                $this->stageLabel($o, $stage, $now) . '.',
                'Score: ' . ($o->score !== null ? round($o->score, 1) : '—') . '/100 (' . strtoupper((string) $o->recommendation) . ')'
                    . ' · Budget: ' . $o->budget_label . ' · Client: ' . ($o->client_country ?? '—'),
                'First seen: ' . self::format($o->first_seen_at ?? $o->created_at) . ' · Review due: ' . self::format($o->review_due_at),
                'Approve or reject in the PMS to stop these reminders. Overdue jobs are never approved, rejected or archived automatically.',
            ],
            'actions' => [
                'Snooze this job for 1 hour' => route('upwork.opportunities.snooze.confirm', [$o, 'preset' => '1h']),
                'Snooze this job for 4 hours' => route('upwork.opportunities.snooze.confirm', [$o, 'preset' => '4h']),
                'Snooze this job until ' . self::format($tomorrow) => route('upwork.opportunities.snooze.confirm', [$o, 'preset' => 'tomorrow']),
                'Snooze this job until a date and time you choose' => route('upwork.opportunities.snooze.confirm', [$o, 'preset' => 'custom']),
            ],
        ];
    }

    public function stageLabel(UpworkOpportunity $o, int $stage, Carbon $now): string
    {
        $minutes = (int) ceil(abs($o->review_due_at->diffInSeconds($now, false)) / 60);

        return $stage >= self::overdueStage()
            ? "Review overdue by {$minutes} min (reminder {$stage})"
            : "Review due in {$minutes} min (reminder {$stage})";
    }

    // ---- Snooze --------------------------------------------------------------------------------

    /**
     * When a preset ends. "tomorrow" = the next calendar day at morning_hour in
     * the review timezone; "custom" is a datetime-local value in that zone.
     *
     * @throws \DomainException for an unknown preset or an out-of-range time
     */
    public function snoozeUntil(string $preset, ?string $custom = null, ?Carbon $now = null): Carbon
    {
        $now = self::now($now);
        $tz = self::timezone();

        switch ($preset) {
            case '1h':
                return $now->addHour();
            case '4h':
                return $now->addHours(4);
            case 'tomorrow':
                return $now->setTimezone($tz)->addDay()->setTime((int) config('upwork.review.morning_hour', 9), 0)->setTimezone(config('app.timezone'));
            case 'custom':
                try {
                    $until = Carbon::createFromFormat('Y-m-d\TH:i', (string) $custom, $tz);
                } catch (\Throwable $e) {
                    $until = false;
                }
                if (!$until) {
                    throw new \DomainException('Choose a valid date and time.');
                }
                $until = $until->second(0)->setTimezone(config('app.timezone'));
                $maxDays = max(1, (int) config('upwork.review.max_snooze_days', 14));
                if ($until->lte($now->copy()->addMinute())) {
                    throw new \DomainException('Choose a time in the future.');
                }
                if ($until->gt($now->copy()->addDays($maxDays))) {
                    throw new \DomainException("A job can be snoozed for at most {$maxDays} days.");
                }

                return $until;
        }

        throw new \DomainException('Unknown snooze option.');
    }

    /** Silence reminders for this job only. The review deadline does not move. */
    public function snooze(UpworkOpportunity $o, Carbon $until, int $userId, string $preset): void
    {
        if ($o->status === UpworkOpportunity::STATUS_ARCHIVED) {
            throw new \DomainException('This job is archived; it sends no reminders, so there is nothing to snooze.');
        }
        if ($o->status !== UpworkOpportunity::STATUS_NEW) {
            throw new \DomainException('This job has already been reviewed; there is nothing to snooze.');
        }

        $o->forceFill(['review_snoozed_until' => $until, 'review_snoozed_by' => $userId])->save();
        $o->log('review_snoozed', $userId, null, null, 'Reminders snoozed until ' . self::format($until) . " ({$preset})"
            . ($o->review_due_at ? '. Review deadline unchanged: ' . self::format($o->review_due_at) : ''));
    }

    public function unsnooze(UpworkOpportunity $o, int $userId): void
    {
        if (!$this->isSnoozed($o)) {
            throw new \DomainException('Reminders for this job are not snoozed.');
        }

        $o->forceFill(['review_snoozed_until' => null, 'review_snoozed_by' => null])->save();
        $o->log('review_unsnoozed', $userId, null, null, 'Reminders resumed');
    }

    /** Current time in the app timezone: query bindings format a Carbon in its own zone. */
    private static function now(?Carbon $at = null): Carbon
    {
        return ($at ?? now())->copy()->setTimezone(config('app.timezone'));
    }

    public static function format(?Carbon $at): string
    {
        return $at ? $at->copy()->setTimezone(self::timezone())->format('D d M Y, H:i T') : '—';
    }

    /** @return int[] minutes after the clock starts, ascending, all before the deadline */
    private static function reminderOffsets(): array
    {
        $deadline = self::deadlineMinutes();
        $offsets = array_values(array_filter(array_map('intval', (array) config('upwork.review.reminder_minutes', [10, 20, 25])), fn($m) => $m > 0 && $m < $deadline));
        sort($offsets);

        return $offsets;
    }
}
