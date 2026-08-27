<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webklex\PHPIMAP\ClientManager;
use App\Models\TicketComments;
use App\Models\Users;
use App\Models\TicketFiles;
use App\Models\EmailMessageMap;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Notifications\TicketNotification;
use App\Models\TicketAssigns;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use App\Models\Client;

class ProcessTicketReplies extends Command
{
    protected $signature = 'tickets:process-replies';

    protected $description = 'Process ticket email replies';

    public function handle()
    {
        echo "command started\n";

        $cm = new ClientManager();

        $client = $cm->make([
            'host' => env('IMAP_HOST'),
            'port' => env('IMAP_PORT'),
            'encryption' => env('IMAP_ENCRYPTION'),
            'validate_cert' => env('IMAP_VALIDATE_CERT'),
            'username' => env('IMAP_USERNAME'),
            'password' => env('IMAP_PASSWORD'),
            'protocol' => env('IMAP_PROTOCOL'),
        ]);

        $client->connect();

        $folder = $client->getFolder('INBOX');

        $messages = $folder
            ->messages()
            ->all()
            ->since(now()->startOfDay())
            ->setFetchOrder('desc')
            ->get();

        foreach ($messages as $message) {

            $from = $message->getFrom()[0]->mail ?? null;


            if (!$from) {
                continue;
            }
            $incomingMessageId =
                $this->extractHeaderValue(
                    $message->getMessageId()
                );

            $incomingMessageId = $incomingMessageId
                ? trim($incomingMessageId, '<> ')
                : null;


            /*
            |--------------------------------------------------------------------------
            | Duplicate incoming email check
            |--------------------------------------------------------------------------
            */

            if ($incomingMessageId) {

                $alreadyImported =
                    EmailMessageMap::where(
                        'email_message_id',
                        $incomingMessageId
                    )->exists();

                if ($alreadyImported) {

                    echo "Already imported\n";

                    continue;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | In-Reply-To
            |--------------------------------------------------------------------------
            */

            $inReplyTo =
                $this->extractHeaderValue(
                    $message->getInReplyTo()
                );

            $inReplyTo = $inReplyTo
                ? trim($inReplyTo, '<> ')
                : null;

            /*
            |--------------------------------------------------------------------------
            | References
            |--------------------------------------------------------------------------
            */

            $referencesRaw =
                $this->extractHeaderValue(
                    $message->getReferences()
                );

            $references = null;

            if ($referencesRaw) {

                $refIds = preg_split(
                    '/\s+/',
                    trim($referencesRaw)
                );

                $lastRef = end($refIds);

                if ($lastRef) {

                    $references = trim(
                        $lastRef,
                        '<> '
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Determine parent email
            |--------------------------------------------------------------------------
            */

            $parentId = $inReplyTo ?: $references;

            if (!$parentId) {

                echo "Not ticket reply\n";

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Find parent using email_message_map
            |--------------------------------------------------------------------------
            */

            $emailMap = EmailMessageMap::with('comment')
                ->where(
                    'email_message_id',
                    $parentId
                )
                ->first();

            if (!$emailMap) {

                echo "Email mapping not found for: "
                    . $parentId
                    . "\n";

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Parent ticket comment
            |--------------------------------------------------------------------------
            */

            $parent = $emailMap->comment;

            if (!$parent) {

                echo "Parent comment not found for email map: "
                    . $emailMap->id
                    . "\n";

                continue;
            }

            echo "PARENT COMMENT ID : "
                . $parent->id
                . "\n";

            echo "TICKET ID : "
                . $parent->ticket_id
                . "\n";

            /*
            |--------------------------------------------------------------------------
            | Find sender
            |--------------------------------------------------------------------------
            */

            $fromEmail = strtolower(trim($from));

            /*
            |--------------------------------------------------------------------------
            | First check primary user email
            |--------------------------------------------------------------------------
            */

            $user = Users::whereRaw(
                'LOWER(TRIM(email)) = ?',
                [$fromEmail]
            )->first();

            /*
            |--------------------------------------------------------------------------
            | If not found, check client secondary/additional email
            |--------------------------------------------------------------------------
            */

            if (!$user) {

                $client = Client::where(function ($query) use ($fromEmail) {

                    $query->whereRaw(
                        'LOWER(TRIM(secondary_email)) = ?',
                        [$fromEmail]
                    )
                    ->orWhereRaw(
                        'LOWER(TRIM(additional_email)) = ?',
                        [$fromEmail]
                    );

                })->first();

                if ($client) {

                    /*
                    |--------------------------------------------------------------------------
                    | Get the actual portal user belonging to this client
                    |--------------------------------------------------------------------------
                    */

                    $user = Users::where(
                        'client_id',
                        $client->id
                    )->first();

                    if ($user) {

                        echo "Client matched using alternate email: "
                            . $from
                            . "\n";

                        \Log::info(
                            'Client matched using secondary/additional email',
                            [
                                'email' => $from,
                                'client_id' => $client->id,
                                'user_id' => $user->id,
                            ]
                        );
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Still not found
            |--------------------------------------------------------------------------
            */

            if (!$user) {

                echo "User not found for email: "
                    . $from
                    . "\n";

                \Log::warning(
                    'Incoming email sender not found',
                    [
                        'email' => $from,
                    ]
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Read email body
            |--------------------------------------------------------------------------
            */
            $htmlBody = $message->getHTMLBody();
            $plainBody = $message->getTextBody();

            if (!empty($htmlBody)) {

                /*
                |--------------------------------------------------------------------------
                | Keep HTML structure.
                |--------------------------------------------------------------------------
                */

                $replyHtml = $this->cleanIncomingEmailHtml($htmlBody);

                /*
                |--------------------------------------------------------------------------
                | Convert CLEAN HTML to text only for:
                |
                | - empty reply detection
                | - acknowledgement detection
                | - word count
                |
                | The HTML itself is NOT rebuilt from this text.
                |--------------------------------------------------------------------------
                */

                $replyText = $this->htmlEmailToPlainText($replyHtml);

            } elseif (!empty($plainBody)) {

                $replyText = $this->stripQuotedReply($plainBody);

                $replyHtml = $this->convertPlainTextToHtml($replyText);

            } else {

                $replyText = '';
                $replyHtml = '';
            }



            /*
            |--------------------------------------------------------------------------
            | Normalize email body
            |--------------------------------------------------------------------------
            */

            $replyText = html_entity_decode(
                $replyText,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

            $replyText = str_replace(
                ["\r\n", "\r"],
                "\n",
                $replyText
            );

            $replyText = str_replace(
                ["\xc2\xa0", "\u{00A0}"],
                ' ',
                $replyText
            );

            $replyText = trim($replyText);

            /*
            |--------------------------------------------------------------------------
            | Remove quoted/replied email content
            |--------------------------------------------------------------------------
            */

            $replyText = $this->stripQuotedReply($replyText);

            $replyText = trim($replyText);


            /*
            |--------------------------------------------------------------------------
            | Save attachments BEFORE deciding whether body is empty
            |--------------------------------------------------------------------------
            |
            | Important:
            | Even if the email contains only an attachment, we must still save it.
            |
            */

            $documentPaths = $this->saveIncomingAttachments($message);


            /*
            |--------------------------------------------------------------------------
            | Empty body + no attachment
            |--------------------------------------------------------------------------
            */

            if (
                trim($replyText) === '' &&
                empty($documentPaths)
            ) {

                echo "Empty reply and no attachments, skipping\n";

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Attachment-only email
            |--------------------------------------------------------------------------
            |
            | Give the comment a meaningful value instead of refusing to create it.
            |
            */

            if (trim($replyText) === '' && !empty($documentPaths)) {

                $replyText = '[Attachment received]';

                echo "Email contains attachment(s) but no text\n";
            }


                        /*
                        |--------------------------------------------------------------------------
                        | Debug
                        |--------------------------------------------------------------------------
                        */

                        echo "FINAL REPLY TEXT:\n";
                        echo $replyText . "\n";

                        echo "ATTACHMENTS FOUND: " . count($documentPaths) . "\n";

                        /*
                        |--------------------------------------------------------------------------
                        | Save incoming email Message-ID
                        |--------------------------------------------------------------------------
                        */

                        echo "STEP 1: Before TicketComments::create()\n";
                        $newComment = TicketComments::create([
                            'ticket_id' => $parent->ticket_id,
                            'comments' => $replyHtml,
                            'comment_by' => $user->id,
                            'reply_to' => $parent->id,
                            'document' => implode(',', $documentPaths),
                            'is_system' => 0,
                            'comment_source' => 'email',
                        ]);

                        echo "STEP 2: TicketComments::create() completed. ID: {$newComment->id}\n";

                        echo "STEP 3: Before sendEmailReplyNotifications()\n";

                        $this->sendEmailReplyNotifications(
                            $newComment,
                            $parent,
                            $user,
                            $replyText,
                            $documentPaths,
                            $replyHtml
                        );

                        echo "STEP 4: sendEmailReplyNotifications() completed\n";
                        if ($incomingMessageId) {

                            EmailMessageMap::create([
                                'ticket_comment_id' =>
                                    $newComment->id,

                                'recipient_email' =>
                                    $from,

                                'email_message_id' =>
                                    $incomingMessageId,
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Save attachments in TicketFiles
                        |--------------------------------------------------------------------------
                        */

                        foreach ($documentPaths as $docPath) {

                            TicketFiles::create([
                                'document' =>
                                    $docPath,

                                'ticket_id' =>
                                    $parent->ticket_id,
                            ]);
                        }

                        echo "CLIENT/STAFF EMAIL REPLY SAVED\n";

                        echo "NEW COMMENT ID : "
                            . $newComment->id
                            . "\n";

                        if (!empty($documentPaths)) {

                            echo "ATTACHMENTS SAVED: "
                                . count($documentPaths)
                                . "\n";
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Client reply status
                        |--------------------------------------------------------------------------
                        */

            if ((int) $user->role_id === 6) {

                // Normalize client reply text
                $plainReplyText = trim(strip_tags($replyText));

                $normalizedReplyText = strtolower(
                    trim(
                        preg_replace(
                            '/[^a-z0-9\s]/i',
                            '',
                            $plainReplyText
                        )
                    )
                );

                // Count words
                $replyWordCount = $plainReplyText === ''
                    ? 0
                    : count(preg_split('/\s+/', $plainReplyText));

                // Short acknowledgement phrases
                $noResponsePhrases = [
                    'thanks',
                    'thank you',
                    'thanks a lot',
                    'thank you so much',
                    'ok',
                    'okay',
                    'noted',
                    'fine',
                    'alright',
                    'sure',
                    'cool',
                    'perfect',
                    'great',
                    'nice',
                    'no problem',
                ];

                $isShortClientReply = false;

                /*
                |--------------------------------------------------------------------------
                | Check whether this is a short acknowledgement
                |--------------------------------------------------------------------------
                */

                foreach ($noResponsePhrases as $phrase) {

                    if (
                        preg_match(
                            '/\b' . preg_quote($phrase, '/') . '\b/',
                            $normalizedReplyText
                        )
                    ) {
                        if (
                            $replyWordCount > 0 &&
                            $replyWordCount <= 5
                        ) {
                            $isShortClientReply = true;
                            break;
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Save status
                |--------------------------------------------------------------------------
                |
                | Short acknowledgement:
                |     acknowledged
                |
                | Normal client reply:
                |     pending
                |
                */

                $status = $isShortClientReply
                    ? 'acknowledged'
                    : 'pending';

                $statusData = [
                    'comment_id' => $newComment->id,
                    'ticket_id' => $parent->ticket_id,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                /*
                |--------------------------------------------------------------------------
                | If short acknowledgement, also record who acknowledged it
                |--------------------------------------------------------------------------
                */

                if ($isShortClientReply) {
                    $statusData['acknowledged_by'] = $user->id;
                    $statusData['acknowledged_at'] = now();
                }

                DB::table('comment_status')->insert($statusData);

                if ($isShortClientReply) {

                    echo "Client short reply -> acknowledged\n";

                    \Log::info(
                        'Client short acknowledgement saved',
                        [
                            'ticket_id' => $parent->ticket_id,
                            'comment_id' => $newComment->id,
                            'client_id' => $user->id,
                            'reply' => $replyText,
                            'status' => 'acknowledged',
                        ]
                    );

                } else {

                    echo "Client normal reply -> pending\n";

                    \Log::info(
                        'Client normal email reply saved as pending',
                        [
                            'ticket_id' => $parent->ticket_id,
                            'comment_id' => $newComment->id,
                            'client_id' => $user->id,
                            'reply' => $replyText,
                            'status' => 'pending',
                        ]
                    );
                }
            }


            if ((int) $user->role_id === 3) {

                /*
                |--------------------------------------------------------------------------
                | DEVELOPER EMAIL REPLY
                |--------------------------------------------------------------------------
                |
                */

                $now = now();

                $status = DB::table('comment_status')
                    ->where('comment_id', $parent->id)
                    ->where('ticket_id', $parent->ticket_id)
                    ->where('status', 'pending')
                    ->first();

                if ($status) {

                    DB::table('comment_status')
                        ->where('id', $status->id)
                        ->where('status', 'pending')
                        ->update([
                            'status'          => 'acknowledged',
                            'replied_by'      => $user->id,
                            'replied_at'      => $now,
                            'acknowledged_by' => $user->id,
                            'acknowledged_at' => $now,
                            'updated_at'      => $now,
                        ]);

                    echo "Developer email reply -> client comment acknowledged\n";

                    \Log::info(
                        'Client comment acknowledged by developer email reply',
                        [
                            'ticket_id'        => $parent->ticket_id,
                            'client_comment_id' => $parent->id,
                            'developer_id'     => $user->id,
                            'developer_email'  => $user->email,
                        ]
                    );

                } else {

                    echo "No pending client comment found for developer email reply\n";

                    \Log::info(
                        'No pending comment found for developer email reply',
                        [
                            'ticket_id'         => $parent->ticket_id,
                            'parent_comment_id' => $parent->id,
                            'developer_id'      => $user->id,
                        ]
                    );
                }

            } elseif ((int) $user->role_id !== 6) {



                $now = now();

                $status = DB::table('comment_status')
                    ->where('comment_id', $parent->id)
                    ->where('ticket_id', $parent->ticket_id)
                    ->where('status', 'pending')
                    ->first();

                if ($status) {

                    $workingSeconds = null;

                    if ($status->created_at) {

                        $workingSeconds =
                            $this->calculateWorkingSeconds(
                                $status->created_at,
                                $now
                            );
                    }

                    DB::table('comment_status')
                        ->where('id', $status->id)
                        ->where('status', 'pending')
                        ->update([
                            'status' =>
                                'replied',

                            'replied_by' =>
                                $user->id,

                            'replied_at' =>
                                $now,

                            'first_response_time_seconds' =>
                                $workingSeconds,

                            'updated_at' =>
                                $now,
                        ]);

                    echo "Admin/staff email reply -> client comment marked replied\n";

                    \Log::info(
                        'Client comment replied by admin/staff email',
                        [
                            'ticket_id'         => $parent->ticket_id,
                            'client_comment_id' => $parent->id,
                            'staff_id'          => $user->id,
                            'staff_email'       => $user->email,
                        ]
                    );
                }
            }


            echo "Reply processing completed\n";
        }

        return Command::SUCCESS;
    }

private function cleanIncomingEmailHtml(string $html): string
{
    /*
    |--------------------------------------------------------------------------
    | 1. Normalize line endings
    |--------------------------------------------------------------------------
    */

    $html = str_replace(
        ["\r\n", "\r"],
        "\n",
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 2. Remove <head>
    |--------------------------------------------------------------------------
    |
    | We don't need the email document head.
    |
    */

    $html = preg_replace(
        '/<head\b[^>]*>.*?<\/head>/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 3. Remove scripts
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/<script\b[^>]*>.*?<\/script>/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 4. Remove dangerous elements
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/<(iframe|object|embed|form|input|button|textarea|select|video|audio|canvas)\b[^>]*>.*?<\/\1>/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 5. Remove self-closing dangerous elements
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/<(iframe|object|embed|form|input|button|textarea|select|video|audio|canvas)\b[^>]*\/?>/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 6. Remove Gmail quoted reply
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/<div\b[^>]*class\s*=\s*(["\'])[^"\']*\bgmail_quote\b[^"\']*\1[^>]*>.*$/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 7. Remove Gmail signature
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/<div\b[^>]*class\s*=\s*(["\'])[^"\']*\bgmail_signature\b[^"\']*\1[^>]*>.*?<\/div>/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 8. Remove Outlook reply/forward content
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/<div\b[^>]*id\s*=\s*(["\'])divRplyFwdMsg\1[^>]*>.*$/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 9. Remove blockquotes
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/<blockquote\b[^>]*>.*?<\/blockquote>/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 10. Remove "On ... wrote:" blocks
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/<(div|p|span)\b[^>]*>\s*(?:<[^>]+>\s*)*On\b.{0,1000}\bwrote\s*:?\s*(?:<\/[^>]+>\s*)*<\/\1>/isu',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 11. Remove inline JavaScript event handlers
    |--------------------------------------------------------------------------
    |
    | onclick=""
    | onload=""
    | onmouseover=""
    | onerror=""
    | etc.
    |
    */

    $html = preg_replace(
        '/\s+on[a-z]+\s*=\s*(["\']).*?\1/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 12. Remove javascript: URLs
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/\s+(href|src)\s*=\s*(["\'])\s*javascript\s*:.*?\2/is',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 13. Remove dangerous CSS expressions
    |--------------------------------------------------------------------------
    */

    $html = preg_replace_callback(
        '/\s+style\s*=\s*(["\'])(.*?)\1/is',
        function ($match) {

            $quote = $match[1];
            $style = $match[2];

            /*
             * Remove javascript: from CSS.
             */
            $style = preg_replace(
                '/javascript\s*:/i',
                '',
                $style
            );

            /*
             * Remove expression().
             */
            $style = preg_replace(
                '/expression\s*\([^)]*\)/i',
                '',
                $style
            );

            return ' style=' . $quote . $style . $quote;
        },
        $html
    );

    $html = preg_replace_callback(
        '/<style\b([^>]*)>(.*?)<\/style>/is',
        function ($match) {

            $css = $match[2];

            /*
             * Remove javascript URLs from CSS.
             */
            $css = preg_replace(
                '/javascript\s*:/i',
                '',
                $css
            );

            /*
             * Remove expression().
             */
            $css = preg_replace(
                '/expression\s*\([^)]*\)/i',
                '',
                $css
            );

            /*
             * Remove @import.
             */
            $css = preg_replace(
                '/@import\b[^;]+;?/i',
                '',
                $css
            );

            return '<style>' . $css . '</style>';
        },
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 15. Remove Microsoft Office XML tags
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/<\/?(?:o|w|m):[^>]*>/i',
        '',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 16. Clean HTML attributes
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | We KEEP:
    |
    | class
    | style
    | href
    | src
    | alt
    | width
    | height
    | target
    | rel
    | title
    |
    | because email designs depend on them.
    |
    */

    $html = preg_replace_callback(
        '/<([a-z][a-z0-9]*)\b([^>]*)>/i',
        function ($match) {

            $tag = strtolower($match[1]);
            $attributes = $match[2];

            /*
            |--------------------------------------------------------------------------
            | Extract attributes
            |--------------------------------------------------------------------------
            */

            preg_match_all(
                '/([a-zA-Z_:][a-zA-Z0-9:_.-]*)\s*=\s*(["\'])(.*?)\2/is',
                $attributes,
                $matches,
                PREG_SET_ORDER
            );

            $allowed = [];

            foreach ($matches as $attribute) {

                $name = strtolower($attribute[1]);
                $value = $attribute[3];

                /*
                | Never allow event handlers.
                */

                if (str_starts_with($name, 'on')) {
                    continue;
                }

                /*
                | Never allow dangerous javascript URLs.
                */

                if (
                    in_array($name, ['href', 'src'], true) &&
                    preg_match('/^\s*javascript\s*:/i', $value)
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Global safe attributes
                |--------------------------------------------------------------------------
                */

                $globalAttributes = [
                    'class',
                    'style',
                    'title',
                    'lang',
                    'dir',
                ];

                /*
                |--------------------------------------------------------------------------
                | Link attributes
                |--------------------------------------------------------------------------
                */

                $linkAttributes = [
                    'href',
                    'target',
                    'rel',
                ];

                /*
                |--------------------------------------------------------------------------
                | Image attributes
                |--------------------------------------------------------------------------
                */

                $imageAttributes = [
                    'src',
                    'alt',
                    'width',
                    'height',
                    'title',
                ];

                /*
                |--------------------------------------------------------------------------
                | Table/email attributes
                |--------------------------------------------------------------------------
                */

                $tableAttributes = [
                    'width',
                    'height',
                    'align',
                    'valign',
                    'border',
                    'cellpadding',
                    'cellspacing',
                    'bgcolor',
                ];

                $isAllowed = false;

                if (in_array($name, $globalAttributes, true)) {
                    $isAllowed = true;
                }

                if (
                    $tag === 'a' &&
                    in_array($name, $linkAttributes, true)
                ) {
                    $isAllowed = true;
                }

                if (
                    $tag === 'img' &&
                    in_array($name, $imageAttributes, true)
                ) {
                    $isAllowed = true;
                }

                if (
                    in_array(
                        $tag,
                        ['table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td'],
                        true
                    ) &&
                    in_array($name, $tableAttributes, true)
                ) {
                    $isAllowed = true;
                }

                if (!$isAllowed) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Escape attribute value
                |--------------------------------------------------------------------------
                */

                $safeValue = htmlspecialchars(
                    $value,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                );

                $allowed[] =
                    $name . '="' . $safeValue . '"';
            }

            /*
            |--------------------------------------------------------------------------
            | Rebuild tag
            |--------------------------------------------------------------------------
            */

            if (!empty($allowed)) {
                return '<' . $tag . ' ' .
                    implode(' ', $allowed) .
                    '>';
            }

            return '<' . $tag . '>';
        },
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 17. Remove empty structural elements
    |--------------------------------------------------------------------------
    */

    $previous = null;

    while ($previous !== $html) {

        $previous = $html;

        $html = preg_replace(
            '/<(span|div)\b[^>]*>\s*<\/\1>/i',
            '',
            $html
        );
    }

    /*
    |--------------------------------------------------------------------------
    | 18. Remove excessive whitespace between tags
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        '/>\s+</',
        '><',
        $html
    );

    /*
    |--------------------------------------------------------------------------
    | 19. Remove excessive blank lines
    |--------------------------------------------------------------------------
    */

    $html = preg_replace(
        "/\n{3,}/",
        "\n\n",
        $html
    );

    return trim($html);
}



    private function stripQuotedReply(string $text): string
    {
        /*
        |--------------------------------------------------------------------------
        | Normalize line endings
        |--------------------------------------------------------------------------
        */

        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        /*
        |--------------------------------------------------------------------------
        | Decode HTML entities
        |--------------------------------------------------------------------------
        */

        $text = html_entity_decode(
            $text,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $text = preg_replace(
            '/[\x{00A0}\x{2007}\x{202F}]/u',
            ' ',
            $text
        );


        $text = preg_replace(
            '/[ \t]+/',
            ' ',
            $text
        );

        $lines = explode("\n", $text);

        foreach ($lines as $index => $line) {

            $line = trim($line);

            if ($line === '') {
                continue;
            }


            if (
                preg_match(
                    '/^On\b.{0,500}\bwrote\s*:\s*$/iu',
                    $line
                )
            ) {

                $text = implode(
                    "\n",
                    array_slice($lines, 0, $index)
                );

                break;
            }
        }

        $text = trim($text);

        /*
        |--------------------------------------------------------------------------
        | 2. Outlook / Microsoft "Original Message"
        |--------------------------------------------------------------------------
        */

        $text = preg_split(
            '/(?:^|\n)\s*-{2,}\s*Original Message\s*-{2,}\s*(?:\n|$)/i',
            $text,
            2
        )[0];

        /*
        |--------------------------------------------------------------------------
        | 3. Common underscore separator
        |--------------------------------------------------------------------------
        */

        $text = preg_split(
            '/(?:^|\n)\s*_{5,}\s*(?:\n|$)/',
            $text,
            2
        )[0];

        /*
        |--------------------------------------------------------------------------
        | 4. Quoted lines beginning with >
        |--------------------------------------------------------------------------
        */

        $lines = explode("\n", $text);

        $cleanLines = [];

        foreach ($lines as $line) {

            $trimmedLine = trim($line);

            /*
            | Stop as soon as quoted content starts.
            */

            if (
                $trimmedLine !== '' &&
                preg_match('/^>+/', $trimmedLine)
            ) {
                break;
            }

            $cleanLines[] = $line;
        }

        $text = implode(
            "\n",
            $cleanLines
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Outlook From / Sent / To block
        |--------------------------------------------------------------------------
        */

        $lines = explode("\n", $text);
        $count = count($lines);

        for ($i = 0; $i < $count - 2; $i++) {

            if (
                preg_match('/^\s*From:\s*.+$/i', $lines[$i]) &&
                preg_match('/^\s*Sent:\s*.+$/i', $lines[$i + 1]) &&
                preg_match('/^\s*To:\s*.+$/i', $lines[$i + 2])
            ) {

                $text = implode(
                    "\n",
                    array_slice($lines, 0, $i)
                );

                break;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Remove excessive blank lines
        |--------------------------------------------------------------------------
        */

        $text = preg_replace(
            "/\n{3,}/",
            "\n\n",
            $text
        );

        return trim($text);
    }

    private function convertPlainTextToHtml(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = str_replace(["\xc2\xa0", "\u{00A0}"], ' ', $text);

        $text = preg_replace('/[ \t]+$/m', '', $text);

        $text = trim($text);

        if ($text === '') {
            return '';
        }
        $text = preg_replace_callback(
                '/(https?:\/\/[^\s]*?)\n(?=\S)/i',
                function ($m) {
                    return $m[1];
                },
                $text
            );
        /*
        * Dewrap lines that were auto-wrapped by the email client
        * (single newlines that are NOT part of a blank-line run).
        */
        // $text = preg_replace('/(?<!\n)\n(?!\n)/', ' ', $text);

        /*
        |--------------------------------------------------------------------------
        | Split into content chunks + blank-line-run delimiters, keeping the
        | delimiters so we know exactly how many blank lines separated them.
        |--------------------------------------------------------------------------
        */
        $parts = preg_split(
            '/(\n{2,})/',
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $html = '';

        foreach ($parts as $part) {

            // This part is a run of 2+ newlines (a paragraph break)
            if (preg_match('/^\n{2,}$/', $part)) {

                $newlineCount = substr_count($part, "\n");

                // e.g. "\n\n" = 1 blank line, "\n\n\n" = 2 blank lines
                $blankLines = $newlineCount - 1;

                for ($i = 0; $i < $blankLines; $i++) {
                    $html .= '<p><br></p>';
                }

                continue;
            }

            $paragraph = trim($part);

            if ($paragraph === '') {
                continue;
            }

            // $paragraph = htmlspecialchars(
            //     $paragraph,
            //     ENT_NOQUOTES | ENT_SUBSTITUTE,
            //     'UTF-8'
            // );
            $paragraph = $this->linkifyParagraph($paragraph);
            $paragraph = str_replace("\n", '<br>', $paragraph);

            $html .= '<p>' . $paragraph . '</p>';
        }

        return $html;
    }
    /**
     * Escape a paragraph's text and turn bare URLs into clickable
     * <a> links, without emitting unescaped user content.
     */
    private function linkifyParagraph(string $paragraph): string
    {
        $urls = [];

        $withPlaceholders = preg_replace_callback(
            '/(?<![@\w])((?:(?:https?:\/\/|www\.)[a-z0-9.-]+\.[a-z]{2,}(?::\d+)?(?:\/[^\s<>"\']*)?)|(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}(?::\d+)?(?:\/[^\s<>"\']*)?)/i',
            function ($m) use (&$urls) {

                $raw = $m[1];

                /*
                |--------------------------------------------------------------------------
                | Remove punctuation accidentally captured after the URL
                |--------------------------------------------------------------------------
                */

                $trailing = '';

                while (
                    $raw !== '' &&
                    preg_match('/[.,;:!?)\]}>]$/', $raw)
                ) {
                    $trailing = substr($raw, -1) . $trailing;
                    $raw = substr($raw, 0, -1);
                }

                if ($raw === '') {
                    return $m[1];
                }

                /*
                |--------------------------------------------------------------------------
                | Add https:// when the user entered a bare domain
                |--------------------------------------------------------------------------
                */

                $href = preg_match(
                    '/^https?:\/\//i',
                    $raw
                )
                    ? $raw
                    : 'https://' . $raw;

                $index = count($urls);

                $urls[$index] = [
                    'href' => $href,
                    'label' => $raw,
                ];

                return "\x00LINK{$index}\x00" . $trailing;
            },
            $paragraph
        );

        /*
        |--------------------------------------------------------------------------
        | Escape normal text
        |--------------------------------------------------------------------------
        */

        $escaped = htmlspecialchars(
            $withPlaceholders,
            ENT_NOQUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        /*
        |--------------------------------------------------------------------------
        | Replace URL placeholders with anchors
        |--------------------------------------------------------------------------
        */

        foreach ($urls as $index => $url) {

            $hrefEscaped = htmlspecialchars(
                $url['href'],
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $labelEscaped = htmlspecialchars(
                $url['label'],
                ENT_NOQUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

            $anchor =
                '<a href="' . $hrefEscaped . '" '
                . 'target="_blank" '
                . 'rel="noopener noreferrer">'
                . $labelEscaped
                . '</a>';

            $escaped = str_replace(
                "\x00LINK{$index}\x00",
                $anchor,
                $escaped
            );
        }

        return $escaped;
    }
    /**
     * Save incoming attachments.
     */
    private function saveIncomingAttachments($message): array
    {
        $documentPaths = [];

        try {

            if (!$message->hasAttachments()) {
                return $documentPaths;
            }

            $destDir = public_path(
                'assets/img/ticketAssets'
            );

            if (!is_dir($destDir)) {

                mkdir(
                    $destDir,
                    0755,
                    true
                );
            }

            foreach (
                $message->getAttachments()
                as $attachment
            ) {

                try {

                    $originalName =
                        $attachment->getName();

                    if (!$originalName) {

                        $originalName =
                            'attachment_'
                            . Str::random(6);
                    }

                    $extension =
                        pathinfo(
                            $originalName,
                            PATHINFO_EXTENSION
                        );

                    $baseName =
                        pathinfo(
                            $originalName,
                            PATHINFO_FILENAME
                        );

                    $safeName =
                        date('YmdHis')
                        . '_'
                        . Str::slug($baseName)
                        . (
                            $extension
                                ? '.' . $extension
                                : ''
                        );

                    $destPath =
                        $destDir
                        . DIRECTORY_SEPARATOR
                        . $safeName;

                    $content =
                        $attachment->getContent();

                    if (
                        $content === null
                        || $content === ''
                    ) {

                        echo "Attachment '"
                            . $originalName
                            . "' had no content, skipping\n";

                        continue;
                    }

                    file_put_contents(
                        $destPath,
                        $content
                    );

                    $documentPaths[] =
                        'ticketAssets/'
                        . $safeName;

                    echo "Saved attachment: "
                        . $originalName
                        . " -> "
                        . $safeName
                        . "\n";

                } catch (\Throwable $e) {

                    \Log::error(
                        'Failed to save inbound attachment: '
                        . $e->getMessage()
                    );

                    echo "Failed to save an attachment, see log\n";
                }
            }

        } catch (\Throwable $e) {

            \Log::error(
                'Failed to read attachments from inbound message: '
                . $e->getMessage()
            );
        }

        return $documentPaths;
    }

    /**
     * Safely extract header value.
     */
    private function extractHeaderValue(
        $value
    ): ?string {

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {

            $value = trim($value);

            return $value !== ''
                ? $value
                : null;
        }

        if (is_array($value)) {

            $first = reset($value);

            return $first !== false
                ? $this->extractHeaderValue($first)
                : null;
        }

        if (is_object($value)) {

            if (method_exists($value, 'first')) {

                $first = $value->first();

                if (
                    $first !== null
                    && $first !== ''
                ) {

                    return $this->extractHeaderValue(
                        $first
                    );
                }
            }

            if (
                method_exists(
                    $value,
                    '__toString'
                )
            ) {

                $str = trim(
                    (string) $value
                );

                return $str !== ''
                    ? $str
                    : null;
            }
        }

        return null;
    }

    private function htmlEmailToPlainText(string $html): string
    {
        /*
        |--------------------------------------------------------------------------
        | Normalize line endings
        |--------------------------------------------------------------------------
        */

        $html = str_replace(
            ["\r\n", "\r"],
            "\n",
            $html
        );
        $html = preg_replace('/<blockquote\b[^>]*>.*?<\/blockquote>/is', '', $html);
        /*
        |--------------------------------------------------------------------------
        | Convert <br> into line breaks
        |--------------------------------------------------------------------------
        */

        $html = preg_replace(
            '/<\s*br\s*\/?\s*>/i',
            "\n",
            $html
        );
        // $html = preg_replace('/<\/a>\s*<a\b/i', "</a>\n<a", $html);
        $html = preg_replace('/<a\b/i', "\n<a", $html);
        $html = preg_replace('/<\/a>/i', "</a>\n", $html);


        /*
        |--------------------------------------------------------------------------
        | Convert block elements into paragraph boundaries
        |--------------------------------------------------------------------------
        */

        $html = preg_replace(
            '/<\s*\/\s*(p|div|li|tr|h[1-6])\s*>/i',
            "\n\n",
            $html
        );

        $html = preg_replace(
            '/<\s*(p|div|li|tr|h[1-6])[^>]*>/i',
            '',
            $html
        );

        /*
        |--------------------------------------------------------------------------
        | Remove remaining HTML tags
        |--------------------------------------------------------------------------
        */
        $html = preg_replace('/<img\b[^>]*>/i', '', $html);
        $text = strip_tags($html);
        $text = html_entity_decode(
            $text,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
        $text = str_replace('">', '', $text);

        $text = preg_replace(
            '/(?<!\n)(https?:\/\/.*?\.php)(?=https?:\/\/)/i',
            "$1\n",
            $text
        );
        /*
        |--------------------------------------------------------------------------
        | Decode HTML entities
        |--------------------------------------------------------------------------
        |
        | &quot;
        | &#039;
        | &amp;
        | &nbsp;
        |
        | all become their normal characters here.
        |--------------------------------------------------------------------------
        */

        // $text = html_entity_decode(
        //     $text,
        //     ENT_QUOTES | ENT_HTML5,
        //     'UTF-8'
        // );

        /*
        |--------------------------------------------------------------------------
        | Convert non-breaking spaces
        |--------------------------------------------------------------------------
        */
        $text = preg_replace('/\x{FFFC}/u', '', $text);
        $text = str_replace(
            ["\xc2\xa0", "\u{00A0}"],
            ' ',
            $text
        );

        /*
        |--------------------------------------------------------------------------
        | Normalize spaces/tabs
        |--------------------------------------------------------------------------
        */

        $text = preg_replace(
            '/[ \t]+/',
            ' ',
            $text
        );

        /*
        |--------------------------------------------------------------------------
        | Normalize line endings
        |--------------------------------------------------------------------------
        */

        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        /*
        |--------------------------------------------------------------------------
        | Remove excessive blank lines
        |--------------------------------------------------------------------------
        */

        $text = preg_replace(
            "/\n{3,}/",
            "\n\n",
            $text
        );
        $text = preg_replace('/^(.*)\n(?:\n)?\1$/m', '$1', $text);
        return trim($text);
    }
    /**
     * Send notification emails after an email reply
     *
     * Client reply:
     *      Client -> Assigned Developers + Admin
     *
     * Staff/Admin reply:
     *      Developer/Admin -> Client
     */
    private function sendEmailReplyNotifications(
        TicketComments $newComment,
        TicketComments $parent,
        Users $sender,
        string $replyText,
        array $documentPaths = [] ,
        string $replyHtml = ''
    ): void {
        try {

            $ticketId = $parent->ticket_id;

            /*
            |--------------------------------------------------------------------------
            | Get ticket
            |--------------------------------------------------------------------------
            */

            $ticket = \App\Models\Tickets::find($ticketId);

            if (!$ticket) {
                \Log::warning('Email reply notification: ticket not found', [
                    'ticket_id' => $ticketId,
                    'comment_id' => $newComment->id,
                ]);

                return;
            }

            $ticketName = $ticket->title ?? "Ticket #{$ticketId}";

            /*
            |--------------------------------------------------------------------------
            | Common email message data
            |--------------------------------------------------------------------------
            */

            $messages = [];

            $messages['greeting-text'] = 'Hello!';

            $messages['comment_id'] = $newComment->id;

            $messages['ticket_id'] = $ticketId;

            $messages['title'] =
                "New email reply on Ticket # <strong>{$ticketId}</strong>";

            $messages['title-ticketName'] =
                "<p><strong>Ticket Name:</strong> {$ticketName}</p>";

            // $messages['body-text'] = $this->convertPlainTextToHtml($replyText);
            $messages['body-text'] = $replyHtml ?: $this->convertPlainTextToHtml($replyText);

            $messages['url-title'] = 'View Ticket';

            $messages['url'] = "/view/ticket/{$ticketId}";

            /*
            |--------------------------------------------------------------------------
            | Attachment information
            |--------------------------------------------------------------------------
            */

            if (!empty($documentPaths)) {

                $documentText =
                    '<p><strong>Attached Document(s):</strong></p><ul>';

                foreach ($documentPaths as $docPath) {

                    $fileName = basename($docPath);

                    $documentText .=
                        '<li>' . e($fileName) . '</li>';
                }

                $documentText .= '</ul>';

                $messages['document-text'] = $documentText;
            }

            /*
            |--------------------------------------------------------------------------
            | CLIENT REPLIED FROM EMAIL
            |--------------------------------------------------------------------------
            |
            | Client email -> Ticket
            |
            | Notify:
            |   1. Assigned developers
            |   2. Admin
            |
            */

            if ((int) $sender->role_id === 6) {

                $messages['subject'] =
                    "Client Reply on \"{$ticketName}\"";

                \Log::info(
                    'Processing client email reply notification',
                    [
                        'ticket_id' => $ticketId,
                        'comment_id' => $newComment->id,
                        'client_id' => $sender->id,
                        'client_email' => $sender->email,
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Get assigned developers
                |--------------------------------------------------------------------------
                */

                $assignedUsers = TicketAssigns::join(
                    'users',
                    'ticket_assigns.user_id',
                    '=',
                    'users.id'
                )
                    ->where(
                        'ticket_assigns.ticket_id',
                        $ticketId
                    )
                    ->whereNotNull('users.email')
                    ->get([
                        'users.id',
                        'users.first_name',
                        'users.email',
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Admin
                |--------------------------------------------------------------------------
                */

                $admin = Users::find(1);

                /*
                |--------------------------------------------------------------------------
                | Build unique recipient list
                |--------------------------------------------------------------------------
                */

                $recipients = collect();

                foreach ($assignedUsers as $assignedUser) {

                    if (!empty($assignedUser->email)) {

                        $recipients->push([
                            'id' => $assignedUser->id,
                            'email' => trim($assignedUser->email),
                            'name' => $assignedUser->first_name,
                            'type' => 'developer',
                        ]);
                    }
                }

                if (
                    $admin &&
                    !empty($admin->email)
                ) {

                    $recipients->push([
                        'id' => $admin->id,
                        'email' => trim($admin->email),
                        'name' => $admin->first_name,
                        'type' => 'admin',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Remove duplicate email addresses
                |--------------------------------------------------------------------------
                */

                $recipients = $recipients
                    ->unique(function ($recipient) {
                        return strtolower($recipient['email']);
                    })
                    ->values();

                /*
                |--------------------------------------------------------------------------
                | Send email to developers + admin
                |--------------------------------------------------------------------------
                */

                foreach ($recipients as $recipient) {

                    /*
                    |--------------------------------------------------------------------------
                    | Do not send to empty address
                    |--------------------------------------------------------------------------
                    */

                    if (empty($recipient['email'])) {
                        continue;
                    }

                    $recipientMessages = $messages;

                    /*
                    |--------------------------------------------------------------------------
                    | IMPORTANT:
                    |
                    | This is the NEW comment ID.
                    |
                    | TicketNotification will create an EmailMessageMap
                    | for this outgoing email.
                    |--------------------------------------------------------------------------
                    */

                    $recipientMessages['comment_id'] =
                        $newComment->id;

                    $recipientMessages['recipient_email'] =
                        $recipient['email'];

                    $recipientMessages['sender_type'] =
                        'client';

                    \Log::info(
                        'Sending client reply notification',
                        [
                            'ticket_id' => $ticketId,
                            'comment_id' => $newComment->id,
                            'recipient' => $recipient['email'],
                            'recipient_type' => $recipient['type'],
                        ]
                    );

                    try {

                        NotificationFacade::route(
                            'mail',
                            $recipient['email']
                        )->notify(
                            new TicketNotification(
                                $recipientMessages,
                                $documentPaths
                            )
                        );

                        \Log::info(
                            'Client reply notification sent',
                            [
                                'ticket_id' => $ticketId,
                                'comment_id' => $newComment->id,
                                'recipient' => $recipient['email'],
                            ]
                        );

                    } catch (\Throwable $e) {

                        \Log::error(
                            'Failed to send client reply notification',
                            [
                                'ticket_id' => $ticketId,
                                'comment_id' => $newComment->id,
                                'recipient' => $recipient['email'],
                                'error' => $e->getMessage(),
                            ]
                        );
                    }
                }

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | STAFF / ADMIN REPLIED FROM EMAIL
            |--------------------------------------------------------------------------
            |
            | Developer/Admin email -> Ticket
            |
            | Notify client.
            |
            */

            $messages['subject'] =
                "Reply on \"{$ticketName}\"";

            \Log::info(
                'Processing staff email reply notification',
                [
                    'ticket_id' => $ticketId,
                    'comment_id' => $newComment->id,
                    'staff_id' => $sender->id,
                    'staff_email' => $sender->email,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Find client(s) attached to the project
            |--------------------------------------------------------------------------
            */

            $project = null;

            if (!empty($ticket->project_id)) {

                $project = \App\Models\Projects::find(
                    $ticket->project_id
                );
            }

            $clientIds = collect();

            if ($project) {

                /*
                | New many-to-many client relation
                */

                if (
                    $project->clients &&
                    $project->clients->isNotEmpty()
                ) {

                    $clientIds = $project->clients->pluck('id');
                }

                /*
                | Old single client relation
                */

                elseif (!empty($project->client_id)) {

                    $clientIds = collect([
                        $project->client_id
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Send to clients
            |--------------------------------------------------------------------------
            */

            foreach ($clientIds as $clientId) {

                $clientUser = Users::where(
                    'client_id',
                    $clientId
                )->first();

                $client = \App\Models\Client::find($clientId);

                if (!$client) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Main client email
                |--------------------------------------------------------------------------
                */

                if (
                    $clientUser &&
                    !empty($clientUser->email) &&
                    strtolower(trim($clientUser->email)) !== strtolower(trim($sender->email))
                ) {

                    $recipientMessages = $messages;

                    $recipientMessages['comment_id'] =
                        $newComment->id;

                    $recipientMessages['recipient_email'] =
                        $clientUser->email;

                    $recipientMessages['sender_type'] =
                        'staff';

                    \Log::info(
                        'Sending staff email reply to client',
                        [
                            'ticket_id' => $ticketId,
                            'comment_id' => $newComment->id,
                            'recipient' => $clientUser->email,
                        ]
                    );

                    try {

                        $clientUser->notify(
                            new TicketNotification(
                                $recipientMessages,
                                $documentPaths
                            )
                        );

                    } catch (\Throwable $e) {

                        \Log::error(
                            'Failed to send staff reply to client',
                            [
                                'ticket_id' => $ticketId,
                                'comment_id' => $newComment->id,
                                'recipient' => $clientUser->email,
                                'error' => $e->getMessage(),
                            ]
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Secondary client email
                |--------------------------------------------------------------------------
                */

                if (
                    !empty($client->secondary_email) &&
                    strtolower(trim($client->secondary_email)) !== strtolower(trim($sender->email))
                ) {

                    $recipientMessages = $messages;

                    $recipientMessages['comment_id'] =
                        $newComment->id;

                    $recipientMessages['recipient_email'] =
                        $client->secondary_email;

                    $recipientMessages['sender_type'] =
                        'staff';

                    try {

                        NotificationFacade::route(
                            'mail',
                            $client->secondary_email
                        )->notify(
                            new TicketNotification(
                                $recipientMessages,
                                $documentPaths
                            )
                        );

                    } catch (\Throwable $e) {

                        \Log::error(
                            'Failed to send staff reply to secondary client email',
                            [
                                'ticket_id' => $ticketId,
                                'comment_id' => $newComment->id,
                                'recipient' => $client->secondary_email,
                                'error' => $e->getMessage(),
                            ]
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Additional client email
                |--------------------------------------------------------------------------
                */

                if (
                    !empty($client->additional_email) &&
                    strtolower(trim($client->additional_email)) !== strtolower(trim($sender->email))
                ) {

                    $recipientMessages = $messages;

                    $recipientMessages['comment_id'] =
                        $newComment->id;

                    $recipientMessages['recipient_email'] =
                        $client->additional_email;

                    $recipientMessages['sender_type'] =
                        'staff';

                    try {

                        NotificationFacade::route(
                            'mail',
                            $client->additional_email
                        )->notify(
                            new TicketNotification(
                                $recipientMessages,
                                $documentPaths
                            )
                        );

                    } catch (\Throwable $e) {

                        \Log::error(
                            'Failed to send staff reply to additional client email',
                            [
                                'ticket_id' => $ticketId,
                                'comment_id' => $newComment->id,
                                'recipient' => $client->additional_email,
                                'error' => $e->getMessage(),
                            ]
                        );
                    }
                }
            }

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Never prevent ticket comment creation because email failed
            |--------------------------------------------------------------------------
            */

            \Log::error(
                'Email reply notification processing failed',
                [
                    'ticket_id' => $parent->ticket_id ?? null,
                    'comment_id' => $newComment->id ?? null,
                    'sender_id' => $sender->id ?? null,
                    'sender_email' => $sender->email ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }
    }

}
