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
    public function __construct($messages, $attachments = [], $bcc = null)
    {
        $this->messages = $messages;
        $this->attachments = $attachments;
        $this->bcc = $bcc;
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
        $mail = (new MailMessage)
        ->subject($this->messages['subject'] ?? 'Notification Email')
        ->view('Email.custom_ticket_template', [
                    'messages' => $this->messages
                ]);

        // Add attachments (absolute paths)
         foreach ($this->attachments as $relativePath) {
            $fullPath = public_path('assets/img/' . $relativePath);
            if (file_exists($fullPath)) {
                $mail->attach($fullPath);
            }
        }

        if (!empty($this->bcc)) {
            $mail->bcc($this->bcc);
        }

        return $mail;

    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [];
    }
}
