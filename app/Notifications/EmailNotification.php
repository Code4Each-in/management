<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailNotification extends Notification
{
    use Queueable;
    private $messages;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($messages)
    {
        $this->messages = $messages;
    }
    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->greeting(isset($this->messages['greeting-text']) ? $this->messages['greeting-text'] : 'Hello!')
                    ->subject(isset($this->messages['subject']) ? $this->messages['subject'] : 'Notification Email')
                    ->line(isset($this->messages['title']) ? $this->messages['title'] : 'Title')
                    ->line(isset($this->messages['body-text']) ? $this->messages['body-text'] : 'Body Text')
                    ->action(isset($this->messages['url-title']) ? $this->messages['url-title'] : 'Action Not Required', isset($this->messages['url']) ? url($this->messages['url']) : '#')
                    ->line('Thank you for using our Platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
