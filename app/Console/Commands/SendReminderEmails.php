<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Reminder;
use App\Models\User;
use App\Mail\ReminderMail;
use App\Models\Users;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendReminderEmails extends Command
{
    // protected $signature = 'reminders:send';

    // protected $description = 'Send reminder emails';

    // public function handle()
    // {
    //     $reminders = Reminder::where('reminder_date', '<=', now())
    //         ->where('email_sent', false)
    //         ->get();

    //     foreach ($reminders as $reminder) {

    //         $userIds = json_decode($reminder->user_id, true);

    //         if (!is_array($userIds)) {
    //             $userIds = [$reminder->user_id];
    //         }

    //         $users = Users::whereIn('id', $userIds)->get();

    //         foreach ($users as $user) {

    //             if ($user->email) {

    //                 Mail::to($user->email)
    //                     ->send(new ReminderMail($reminder));
    //             }
    //         }

    //         $reminder->email_sent = true;
    //         $reminder->save();
    //     }

    //     return Command::SUCCESS;
    // }
}
