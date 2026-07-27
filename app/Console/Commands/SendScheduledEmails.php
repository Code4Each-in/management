<?php
// app/Console/Commands/SendScheduledEmails.php
namespace App\Console\Commands;

use App\Mail\ScheduledEmailMailable;
use App\Models\ScheduledEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledEmails extends Command
{
    protected $signature   = 'emails:send-scheduled';
    protected $description = 'Process and send all due scheduled emails';

    public function handle()
    {
        // Get all scheduled emails where send_at <= now
        $due = ScheduledEmail::with(['template', 'recipients.client', 'project'])
            ->where('status', 'scheduled')
            ->where('send_at', '<=', now())
            ->get();
    // dd($due->count(), now()->toString(), $due->toArray());

        if ($due->isEmpty()) {
            $this->info('No emails due.');
            return;
        }

        $this->info("Found {$due->count()} scheduled email(s) to send.");

        foreach ($due as $scheduled) {
            $allSent = true;

            // foreach ($scheduled->recipients as $recipient) {
            //     // Skip already sent recipients
            //     if ($recipient->status === 'sent') continue;

            //     try {
            //         Mail::to($recipient->client->email)
            //             ->send(new ScheduledEmailMailable(
            //                 $scheduled->template,
            //                 $recipient->client,
            //                 $scheduled->project
            //             ));

            //         $recipient->update([
            //             'status'  => 'sent',
            //             'sent_at' => now(),
            //             'error'   => null,
            //         ]);

            //         $this->info("  Sent to: {$recipient->client->email}");

            //     } catch (\Exception $e) {
            //         $allSent = false;

            //         $recipient->update([
            //             'status' => 'failed',
            //             'error'  => $e->getMessage(),
            //         ]);

            //         $this->error("  Failed for: {$recipient->client->email} — {$e->getMessage()}");
            //     }
            // }
            foreach ($scheduled->recipients as $recipient) {
                if ($recipient->status === 'sent') continue;

                $toEmail = $recipient->resolved_email;

                if (!$toEmail) {
                    $recipient->update(['status' => 'failed', 'error' => 'No valid email found.']);
                    continue;
                }

                try {
                    Mail::to($toEmail)->send(new ScheduledEmailMailable(
                        $scheduled->template,
                        $recipient,          // pass the recipient itself now, not just ->client
                        $scheduled->project
                    ));

                    $recipient->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
                    $this->info("  Sent to: {$toEmail}");
                } catch (\Exception $e) {
                    $recipient->update(['status' => 'failed', 'error' => $e->getMessage()]);
                    $this->error("  Failed for: {$toEmail} — {$e->getMessage()}");
                }
            }
            // Mark the whole scheduled email as sent or failed
            $scheduled->update([
                'status' => $allSent ? 'sent' : 'failed'
            ]);
        }

        $this->info('Done.');
    }
}
