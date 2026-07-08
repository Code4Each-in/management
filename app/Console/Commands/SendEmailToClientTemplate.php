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
    Log::info('SendMailToClient command started');
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
           Log::info('Scheduled mails', [
    'mails' => $scheduled_mails->toArray()
]);

    
        foreach ($scheduled_mails as $mail) {

            $template = $mail->template;

            foreach ($mail->recipients->where('status', 'pending') as $recipient) {

                $client = $recipient->client;

                if (!$client) continue;

                // Get project names
                $projectNames = $client->allprojects
                    ->pluck('project_name')
                    ->implode(', ');

                // Prepare placeholders
                $placeholders = [
                    '{{ client_name }}'  => $client->name,
                    '{{ company_name }}' => $client->company ?? '',
                    '{{ project_name }}' => $projectNames ?: 'N/A',
                ];

                // Replace placeholders
                $body = str_replace(
                    array_keys($placeholders),
                    array_values($placeholders),
                    $template->body
                );

                $message = [
                    'client_name' => $client->name,
                    'subject'     => $template->subject,
                    'content'     => $body,
                    'banner_img'  => $template->banner_image,
                ];

                try {
                    // ✅ send to real client email

                    Log::info('Before notify', [
    'client' => $client->email,
]);

     
                    $client->notify(new EmailTemplateNotification($message));

                    Log::info('After notify');

                    // mark as sent
                    $recipient->update([
                        'status'  => 'sent',
                        
                    ]);

                } catch (\Throwable $e) {

                    // mark as failed
                    $recipient->update([
                        'status' => 'failed',
                        'error'  => $e->getMessage(),
                    ]);

                    // \Log::error('Email sending failed', [
                    //     'client_id' => $client->id,
                    //     'email'     => $client->email,
                    //     'error'     => $e->getMessage(),
                    // ]);

                         Log::error('Email failed', [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
    ]);

    throw $e;
                }
            }

             
            // ✅ optional: mark main email as completed
            if ($mail->recipients()->where('status', 'pending')->count() === 0) {
                $mail->update(['status' => 'sent']);
            }
        }
    }
}
