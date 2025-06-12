<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\View;

class EstimationApprovedNotification extends Notification
{
    use Queueable;

    protected $messages;

    public function __construct($messages)
    {
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
            ->view('Email.ticket_estimation_approved', ['messages' => $this->messages]);
    }
}
