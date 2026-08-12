<?php

namespace App\Notifications;

use App\Models\EmailMessageMap;
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
        /*
        |--------------------------------------------------------------------------
        | Determine recipient
        |--------------------------------------------------------------------------
        */

        $recipientEmail = null;

        if (is_object($notifiable)) {
            $recipientEmail = $notifiable->email ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | On-demand notification
        |--------------------------------------------------------------------------
        */

        if (!$recipientEmail && is_object($notifiable)) {

            if (
                method_exists(
                    $notifiable,
                    'routeNotificationFor'
                )
            ) {
                $recipientEmail =
                    $notifiable->routeNotificationFor('mail');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Logging
        |--------------------------------------------------------------------------
        */

        \Log::info('TicketNotification toMail called', [
            'subject' =>
                $this->messages['subject'] ?? null,

            'comment_id' =>
                $this->messages['comment_id'] ?? null,

            'recipient' =>
                $recipientEmail,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Build MailMessage
        |--------------------------------------------------------------------------
        */

        $mail = (new MailMessage)
            ->subject(
                $this->messages['subject']
                    ?? 'Notification Email'
            )
            ->replyTo(
                env('IMAP_USERNAME'),
                'Ticket Support'
            )
            ->view(
                'Email.custom_ticket_template',
                [
                    'messages' => $this->messages
                ]
            );

        $mail->withSwiftMessage(function ($message) {

            try {

                $header = $message
                    ->getHeaders()
                    ->get('Message-ID');

                if (!$header) {

                    \Log::warning(
                        'Outgoing email has no Message-ID',
                        [
                            'comment_id' =>
                                $this->messages['comment_id'] ?? null,

                            'recipient' =>
                                $this->messages['recipient_email'] ?? null,
                        ]
                    );

                    return;
                }

                $messageId = $header->getFieldBody();

                $messageId = trim(
                    $messageId,
                    '<> '
                );

                $commentId =
                    $this->messages['comment_id'] ?? null;

                $recipientEmail =
                    $this->messages['recipient_email'] ?? null;

                \Log::info(
                    'Generated outgoing Message-ID',
                    [
                        'message_id' => $messageId,
                        'comment_id' => $commentId,
                        'recipient' => $recipientEmail,
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Save Message-ID in email_message_map
                |--------------------------------------------------------------------------
                */

                if (
                    !empty($commentId) &&
                    !empty($messageId)
                ) {

                    EmailMessageMap::updateOrCreate(
                        [
                            'email_message_id' => $messageId,
                        ],
                        [
                            'ticket_comment_id' => $commentId,
                            'recipient_email' => $recipientEmail,
                        ]
                    );

                    \Log::info(
                        'Email message mapping saved',
                        [
                            'message_id' => $messageId,
                            'comment_id' => $commentId,
                            'recipient' => $recipientEmail,
                        ]
                    );
                }

            } catch (\Throwable $e) {

                \Log::error(
                    'Message ID processing error',
                    [
                        'comment_id' =>
                            $this->messages['comment_id'] ?? null,

                        'recipient' =>
                            $this->messages['recipient_email'] ?? null,

                        'error' => $e->getMessage(),
                    ]
                );
            }
        });


        /*
        |--------------------------------------------------------------------------
        | Attachments
        |--------------------------------------------------------------------------
        */

        foreach ($this->attachments as $relativePath) {

            $fullPath = public_path(
                'assets/img/' . $relativePath
            );

            if (file_exists($fullPath)) {

                $mail->attach($fullPath);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BCC
        |--------------------------------------------------------------------------
        */

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
