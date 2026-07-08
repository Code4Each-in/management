<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailTemplateNotification extends Notification
{
    use Queueable;

 
    public function __construct($messages)
    {
       // dd($messages);
        $this->messages = $messages;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable) 
    {
        return (new MailMessage)
        ->subject($this->messages['subject'])
        ->view('email_templates.mail_to_client', [
            'client_name' => $this->messages['client_name'],
            'subject'     => $this->messages['subject'],
            'content'     => $this->messages['content'], 
            'banner_img'  => $this->messages['banner_img'],
        ]);
    }


    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}