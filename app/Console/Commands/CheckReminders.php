<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckReminders extends Command
{
    protected $signature = 'reminders:check';
    protected $description = 'Check reminders, trigger them, and update next run date automatically';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Cron started at: ' . Carbon::now());

        $now = Carbon::now();

        $reminders = Reminder::whereNotNull('reminder_date')
            ->where('reminder_date', '<=', $now)
            ->get();

        Log::info("Found " . $reminders->count() . " reminders due.");

        foreach ($reminders as $reminder) {
            Log::info('Triggering reminder: ' . $reminder->description);
            $this->triggerReminderNotification($reminder);
            $this->updateNextReminderDate($reminder);
        }

        $this->info('Reminder check completed!');
    }

    protected function triggerReminderNotification($reminder)
    {
        Log::info('Notification triggered for reminder: ' . $reminder->description);
    }

    protected function updateNextReminderDate($reminder)
    {
        $currentReminderDate = Carbon::parse($reminder->reminder_date);
        if ($reminder->type === 'daily') {
            $nextDate = $currentReminderDate->addDay()->startOfDay();
        } elseif ($reminder->type === 'weekly') {
            $nextDate = $currentReminderDate->addWeek()->startOfDay();
        } elseif ($reminder->type === 'monthly') {
            $nextDate = $currentReminderDate->addMonth()->startOfDay();
        } else {
            Log::warning("Unknown reminder type: " . $reminder->type);
            return;
        }
        $reminder->reminder_date = $nextDate;
        $reminder->save();

        Log::info('Updated next reminder date to: ' . $nextDate->toDateTimeString());
    }
}
