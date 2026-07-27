<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\ScheduledEmail;
use App\Models\Client;
use App\Notifications\EmailTemplateNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

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
                            'recipients.client.allprojects',
                            'recipients.user',
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

                $toEmail       = null;
                $recipientName = '';
                $companyName   = '';
                $projectNames  = 'N/A';

                switch ($recipient->recipient_type) {
                    case 'client':
                        $client = $recipient->client;
                        if (!$client) {
                            $recipient->update(['status' => 'failed', 'error' => 'Client not found.']);
                            continue 2;
                        }
                        $toEmail       = $client->email;
                        $recipientName = $client->name;
                        $companyName   = $client->company ?? '';
                        $projectNames  = $client->allprojects->pluck('project_name')->implode(', ') ?: 'N/A';
                        break;

                    case 'user':
                        $user = $recipient->user;
                        if (!$user) {
                            $recipient->update(['status' => 'failed', 'error' => 'User not found.']);
                            continue 2;
                        }
                        $toEmail       = $user->email;
                        $recipientName = $user->first_name ?? '';
                        break;

                    case 'manual':
                        if (!$recipient->email) {
                            $recipient->update(['status' => 'failed', 'error' => 'Manual email missing.']);
                            continue 2;
                        }
                        $toEmail       = $recipient->email;
                        $recipientName = $recipient->name ?? $recipient->email;
                        break;

                    default:
                        $recipient->update(['status' => 'failed', 'error' => 'Unknown recipient type.']);
                        continue 2;
                }

                // ✅ Prepare placeholders using the resolved values from the switch above
                $placeholders = [
                    '{{client_name}}'    => $recipientName,
                    '{{ client_name }}'  => $recipientName,
                    '{{company_name}}'   => $companyName,
                    '{{ company_name }}' => $companyName,
                    '{{project_name}}'   => $projectNames,
                    '{{ project_name }}' => $projectNames,
                ];

                // ✅ Use the body saved on scheduled_emails, not the template's raw body
                $body = str_replace(
                    array_keys($placeholders),
                    array_values($placeholders),
                    $mail->body
                );

                $message = [
                    'client_name' => $recipientName,
                    'subject'     => $subject,
                    'content'     => $body,
                    'banner_img'  => $template ? $template->banner_image : null,
                    'cc_email'    => $mail->cc_email,
                    'bcc_email'   => $mail->bcc_email,
                ];

                try {
                    Notification::route('mail', $toEmail)
                        ->notify(new EmailTemplateNotification($message));

                    $recipient->update([
                        'status'  => 'sent',
                        'sent_at' => now(),
                        'error'   => null,
                    ]);

                    $this->info("  Sent to: {$toEmail}");

                } catch (\Exception $e) {

                    $recipient->update([
                        'status' => 'failed',
                        'error'  => $e->getMessage(),
                    ]);

                    $this->error("  Failed for: {$toEmail} — {$e->getMessage()}");

                    Log::error('Scheduled email send failed', [
                        'recipient_id' => $recipient->id,
                        'email'        => $toEmail,
                        'error'        => $e->getMessage(),
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
