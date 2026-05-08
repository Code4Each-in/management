<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;

class TicketFeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public $messages;

    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    public function build()
    {
        return $this->subject($this->messages['subject'] ?? 'Notification Email')
                    ->view('Email.ticket-feedback')
                    ->with([
                        'messages' => $this->messages
                    ]);
    }
}