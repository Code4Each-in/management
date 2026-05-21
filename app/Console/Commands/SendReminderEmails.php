<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use App\Models\User;
use App\Mail\ReminderMail;
use App\Models\Users;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendReminderEmails extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Send reminder emails';

    public function handle()
    {
        $now = now();

        $this->info('Checking reminders at: ' . $now);

        $reminders = Reminder::where('reminder_date', '<=', $now)
            ->where('email_sent', 0)
            ->get();

        $this->info('Found reminders: ' . $reminders->count());

        foreach ($reminders as $reminder) {

            try {

                $userIds = $reminder->user_id ?? [];

                if (!is_array($userIds)) {
                    $userIds = [$userIds];
                }

                $users = Users::whereIn('id', $userIds)->get();

                foreach ($users as $user) {

                    if (!$user->email) {
                        continue;
                    }

                    Mail::to($user->email)
                        ->send(new ReminderMail($reminder));

                    $this->info("Mail sent to {$user->email}");
                }


                $now = now();

                    $reminder->update([
                        'email_sent' => 1
                    ]);

                $this->info("Reminder updated: {$reminder->id}");

            } catch (\Exception $e) {

                Log::error('Reminder sending failed', [
                    'reminder_id' => $reminder->id,
                    'message' => $e->getMessage(),
                ]);

                $this->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
