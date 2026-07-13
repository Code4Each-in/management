<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ScheduledEmail;
use App\Models\Client;
use App\Notifications\EmailTemplateNotification;
use Carbon\Carbon;

class SendEmailToClientTemplate extends Command
{
 
    protected $signature = 'SendMailToClient';
    protected $description = 'Command description';


    public function __construct() 
    {
        parent::__construct();
    }

    public function handle()
    {

        $scheduled_mails = ScheduledEmail::with([
                            'template',
                            'recipients.client.allprojects'
                        ])
                        ->where('status', 'scheduled')
                        ->where('send_at', '<=', now())
                        ->whereHas('recipients', function ($q) {
                            $q->where('status', 'pending');
                        })
                        ->get();

        foreach ($scheduled_mails as $mail) {
            $subject = $mail->subject;

            $template = $mail->template;

            foreach ($mail->recipients->where('status', 'pending') as $recipient) {

                $client = $recipient->client;

                if (!$client) continue;

                // Get project names
                $projectNames = $client->allprojects
                    ->pluck('project_name')
                    ->implode(', ');

                // Prepare placeholders dynamically per client
                $placeholders = [
                    config('app.placeholders.client_name')  => $client->name,
                    config('app.placeholders.company_name') => $client->company ?? '',
                    config('app.placeholders.project_name') => $projectNames ?: 'N/A',
                ];

                // ✅ Use the body saved on scheduled_emails, not the template's raw body
                $body = str_replace(
                    array_keys($placeholders),
                    array_values($placeholders),
                    $mail->body
                );

                $message = [
                    'client_name' => $client->name,
                    'subject'     => $subject,
                    'content'     => $body,
                    'banner_img'  => $template->banner_image,
                ];

                 

                try {
                    $client->notify(new EmailTemplateNotification($message));

                    $recipient->update([
                        'status' => 'sent',
                    ]);

                } catch (\Exception $e) {

                    $recipient->update([
                        'status' => 'failed',
                        'error'  => $e->getMessage(),
                    ]);

                }
            }

             
            // ✅ optional: mark main email as completed
            if ($mail->recipients()->where('status', 'pending')->count() === 0) {
                $mail->update(['status' => 'sent']);
            }
        }
    }
}