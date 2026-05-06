<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;

class FeedbackReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $messages;

    public function __construct($messages)
    {
        $this->messages = $messages;
    }

    public function build()
    {
        return $this->subject($this->messages['subject'] ?? 'New Feedback Received')
                    ->view('Email.feedback-received')
                    ->with(['messages' => $this->messages]);
    }
}