<?php

namespace App\Mail;

use App\Models\Users;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientAccessRequested extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $messages;

    public function __construct(Users $user, $messages)
    {
        $this->user = $user;
        $this->messages = $messages;
    }

    public function build()
    {
        return $this->subject($this->messages['subject'] ?? 'Client Access Request')
                    ->view('Email.client_access_request');
    }
}

