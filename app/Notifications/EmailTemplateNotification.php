<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Swift_Image;

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
        ])
        ->withSwiftMessage(function ($message) {
            $this->logo = $message->embed(
                Swift_Image::fromPath(public_path('assets/img/code4each_logo.png'))
            );
        });
}


    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}