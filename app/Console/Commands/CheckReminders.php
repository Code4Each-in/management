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
            if ($reminder->clicked_at) {
                // Manual close
                Log::info('Triggering reminder: ' . $reminder->description);
                $this->triggerReminderNotification($reminder);
                $this->updateNextReminderDate($reminder);
            } else {
                // Missed reminder
                Log::info('Auto-closing missed reminder: ' . $reminder->description);
                $this->autoUpdateMissedReminderAndMarkClicked($reminder);
            }
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
        } elseif ($reminder->type === 'biweekly') {
            $nextDate = $currentReminderDate->addWeeks(2)->startOfDay();
        } 
        elseif ($reminder->type === 'monthly') {
            $nextDate = $currentReminderDate->addMonth()->startOfDay();
        } else {
            Log::warning("Unknown reminder type: " . $reminder->type);
            return;
        }
        $reminder->reminder_date = $nextDate;
        $reminder->clicked_at = null;
        $reminder->save();

        Log::info('Updated next reminder date to: ' . $nextDate->toDateTimeString());
    }

    protected function autoUpdateMissedReminderAndMarkClicked($reminder)
    {
        $now = Carbon::now()->startOfDay();
        $reminderDate = Carbon::parse($reminder->reminder_date)->startOfDay();

        if ($reminderDate->gte($now)) {
            return;
        }

        switch ($reminder->type) {
            case 'daily':
                while ($reminderDate->lt($now)) {
                    $reminderDate->addDay();
                }
                break;

            case 'weekly':
                if (!empty($reminder->weekly_day)) {
                    $targetDay = ucfirst(strtolower($reminder->weekly_day));
                    $reminderDate = Carbon::parse('next ' . $targetDay);
                } else {
                    while ($reminderDate->lt($now)) {
                        $reminderDate->addWeek();
                    }
                }
                break;

            case 'biweekly':
                while ($reminderDate->lt($now)) {
                    $reminderDate->addWeeks(2);
                }
                break;

            case 'monthly':
                while ($reminderDate->lt($now)) {
                    $reminderDate->addMonth();
                }
                break;

            default:
                Log::warning("Unknown reminder type for auto update: " . $reminder->type);
                return;
        }

        $reminder->reminder_date = $reminderDate->startOfDay();
        $reminder->clicked_at = null; 
        $reminder->save();

        Log::info("Auto-updated missed reminder ({$reminder->description}), next date: " . $reminderDate . ", marked clicked_at as now.");
    }
}
