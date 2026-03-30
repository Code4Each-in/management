<?php
namespace App\Mail;

use App\Models\Client;
use App\Models\EmailTemplate;
use App\Models\Project;
use App\Models\Projects;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScheduledEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

    public EmailTemplate $template;
    public Client $client;
    public ?Projects $project;

    public function __construct(EmailTemplate $template, Client $client, ?Projects $project = null)
    {
        $this->template = $template;
        $this->client   = $client;
        $this->project  = $project;
    }

    public function build()
    {
        // Replace all placeholders with real values
        $body = str_replace(
            [
                '{{client_name}}',
                '{{company_name}}',
                '{{project_name}}',
                '{{sender_name}}',
                '{{meeting_date}}',
            ],
            [
                $this->client->name    ?? '',
                config('app.name')     ?? '',
                $this->project->name   ?? '',
                config('app.name')     ?? '',
                now()->format('d M Y') ?? '',
            ],
            $this->template->body
        );

        // Banner URL if image exists
        $bannerUrl = $this->template->banner_image
            ? asset('storage/' . $this->template->banner_image)
            : null;

        return $this->subject($this->template->subject)
                    ->view('emails.scheduled')   // uses blade view
                    ->with([
                        'body'      => $body,
                        'bannerUrl' => $bannerUrl,
                    ]);
    }
}
