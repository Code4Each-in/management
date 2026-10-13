<?php

namespace App\Console\Commands;

use App\Services\Upwork\Review\ReviewSlaService;
use Illuminate\Console\Command;

/**
 * One review-SLA tick: mark overdue jobs and send due reminders (only when
 * reminders are switched on in the PMS). Never changes a job's status.
 */
class UpworkReviewReminders extends Command
{
    protected $signature = 'upwork:review-reminders';
    protected $description = 'Mark overdue Upwork reviews and send due review reminders';

    public function handle(ReviewSlaService $sla)
    {
        $out = $sla->run();

        $this->line(sprintf('Review SLA: %d newly overdue, %d reminders sent, %d snoozed%s%s',
            $out['overdue'], $out['sent'], $out['snoozed'],
            $out['enabled'] ? '' : ' (reminders are switched off; deadlines are still tracked)',
            $out['capped'] ? ' (per-run cap reached; the rest follow next minute)' : ''));

        return 0;
    }
}
