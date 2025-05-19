<?php

namespace App\Notifications;
use Illuminate\Support\HtmlString;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketNotification extends Notification
{
    use Queueable;

    protected $messages;

    /**
     * Create a new notification instance.
     */
    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        // dd($this->messages);
        return (new MailMessage)
            ->subject($this->messages['subject'] ?? 'Notification Email')
            ->view('Email.custom_ticket_template', ['messages' => $this->messages]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [];
    }
}
