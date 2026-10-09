<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;

/**
 * Upwork module scheduler entries (shared hosting: one cron line runs
 * `schedule:run` every minute). Registered only when
 * UPWORK_SCHEDULER_ENABLED=true; each command also checks its own PMS switch.
 */
class UpworkSchedule
{
    public static function register(Schedule $schedule): void
    {
        if (!config('upwork.scheduler.enabled')) {
            return;
        }

        // Discovery: checked every 5 minutes; actually runs only when switched on in
        // the PMS and the configured interval has passed. Never overlaps.
        $schedule->command('upwork:discover')->everyFiveMinutes()->withoutOverlapping(30)->name('upwork-discover');

        // AI queue: short-lived worker, no daemon (shared hosting friendly).
        $schedule->command('queue:work upwork --queue=upwork --stop-when-empty --max-time=50 --tries=1 --timeout=240')
            ->everyMinute()->withoutOverlapping(10)->name('upwork-ai-queue');

        // Keep the two-week Upwork refresh token alive.
        $schedule->command('upwork:token-refresh')->dailyAt('03:15')->withoutOverlapping(10)->name('upwork-token-refresh');
    }
}
