<?php

namespace App\Notifications;
use Illuminate\Support\HtmlString;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MessageNotification extends Notification
{
    use Queueable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->data['subject'])
            ->view('emails.custom_message', [
                'subject' => $this->data['subject'],
                'title'   => $this->data['title'],
                'body'    => $this->data['body-text'],
            ]);
    }
    

    
}
