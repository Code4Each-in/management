<?php

namespace App\Mail;

use App\Models\Users;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientAccessApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(Users $user)
    {
        $this->user = $user;
    }

   public function build()
{
    return $this->subject('Your Client Access Has Been Approved')
                ->view('Email.client-access-approved') 
                ->with([
                    'user' => $this->user,
                ]);
}

}

