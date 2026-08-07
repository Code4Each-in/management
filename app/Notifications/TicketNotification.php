<?php

namespace App\Notifications;

use App\Models\TicketComments;
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
    // public function toMail($notifiable)
    // {
    //     // $mail = (new MailMessage)
    //     // ->subject($this->messages['subject'] ?? 'Notification Email')
    //     // ->view('Email.custom_ticket_template', [
    //     //             'messages' => $this->messages
    //     //         ]);
    // $mail = (new MailMessage)
    //     ->subject($this->messages['subject'] ?? 'Notification Email')
    //     ->replyTo(
    //         env('IMAP_USERNAME'),
    //         'Ticket Support'
    //     )
    //     ->view('Email.custom_ticket_template', [
    //         'messages' => $this->messages
    //     ]);
    //     // Add attachments (absolute paths)
    //      foreach ($this->attachments as $relativePath) {
    //         $fullPath = public_path('assets/img/' . $relativePath);
    //         if (file_exists($fullPath)) {
    //             $mail->attach($fullPath);
    //         }
    //     }

    //     if (!empty($this->bcc)) {
    //         $mail->bcc($this->bcc);
    //     }

    //     return $mail;

    // }
public function toMail($notifiable)
{
    \Log::info('TicketNotification toMail called', [
        'subject' => $this->messages['subject'] ?? null,
        'comment_id' => $this->messages['comment_id'] ?? null,
    ]);


    $mail = (new MailMessage)
        ->subject(
            $this->messages['subject'] ?? 'Notification Email'
        )
        ->replyTo(
            env('IMAP_USERNAME'),
            'Ticket Support'
        )
        ->view(
            'Email.custom_ticket_template',
            [
                'messages'=>$this->messages
            ]
        );



    $mail->withSwiftMessage(function($message){


        try {


            $header = $message
                ->getHeaders()
                ->get('Message-ID');


            if(!$header){

                return;

            }


            $messageId = $header->getFieldBody();


            $messageId = trim(
                $messageId,
                '<>'
            );


            \Log::info('Generated Message ID',[
                'message_id'=>$messageId,
                'comment_id'=>$this->messages['comment_id'] ?? null
            ]);



            /*
             |--------------------------------------------------------------------------
             | Save only first sent email id
             |--------------------------------------------------------------------------
             */

            if (
                !empty($this->messages['comment_id']) &&
                !empty($messageId)
            ) {

                $comment = TicketComments::find(
                    $this->messages['comment_id']
                );

                if($comment && empty($comment->email_message_id)) {

                    $comment->email_message_id = $messageId;
                    $comment->save();

                    \Log::info('Message ID saved',[
                        'comment'=>$comment->id,
                        'message_id'=>$messageId
                    ]);

                }

            }



        }
        catch(\Throwable $e){

            \Log::error(
                'Message ID Error '.$e->getMessage()
            );

        }



    });



    foreach($this->attachments as $relativePath){


        $fullPath = public_path(
            'assets/img/'.$relativePath
        );


        if(file_exists($fullPath)){

            $mail->attach($fullPath);

        }

    }



    if(!empty($this->bcc)){

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
