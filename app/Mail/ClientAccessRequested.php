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

    public function __construct(Users $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->subject('Client Access Request')
                    ->view('Email.client_access_request');
    }
}

