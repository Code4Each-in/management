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

            $user = Users::where(
                'email',
                $from
            )->first();

            if (!$user) {

                echo "User not found: "
                    . $from
                    . "\n";

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Read email body
            |--------------------------------------------------------------------------
            */
/*
|--------------------------------------------------------------------------
| Read email body
|--------------------------------------------------------------------------
*/

$htmlBody = $message->getHTMLBody();
$plainBody = $message->getTextBody();

if (!empty($htmlBody)) {

    /*
     * Convert HTML email to clean plain text.
     */
    $replyText = $this->htmlEmailToPlainText($htmlBody);

} elseif (!empty($plainBody)) {

    /*
     * Plain-text email.
     */
    $replyText = $plainBody;

} else {

    $replyText = '';
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
$commentHtml = $this->convertPlainTextToHtml($replyText);
                        $newComment = TicketComments::create([
                            'ticket_id' => $parent->ticket_id,
                            'comments' => $commentHtml,
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
                            $documentPaths
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


                        /*
                        |--------------------------------------------------------------------------
                        | Staff/Admin reply
                        |--------------------------------------------------------------------------
                        */


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
private function stripQuotedReply(string $text): string
{
    // Normalize line endings.
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    // Decode HTML entities such as &nbsp;, &lt;, &gt;, etc.
    $text = html_entity_decode(
        $text,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    // Convert non-breaking spaces to normal spaces.
    $text = str_replace(
        ["\xc2\xa0", "\u{00A0}"],
        ' ',
        $text
    );

    // Normalize excessive spaces/tabs.
    $text = preg_replace('/[ \t]+/', ' ', $text);

    /*
    |--------------------------------------------------------------------------
    | 1. Gmail / Google Workspace style quote header
    |--------------------------------------------------------------------------
    |
    | A genuine "On <date>, <name> wrote:" header always sits on ONE line.
    | We match line-by-line (no DOTALL) so a real reply paragraph that
    | happens to start with "On ..." is never eaten just because the word
    | "wrote:" appears somewhere further down the email.
    |
    */
$lines = explode("\n", $text);
$lineCount = count($lines);

for ($i = 0; $i < $lineCount; $i++) {

    $window = '';

    for ($w = 0; $w < 3 && ($i + $w) < $lineCount; $w++) {

        $window = trim($window . ' ' . $lines[$i + $w]);

        if (preg_match('/^On\s.{0,250}\swrote\s*:\s*$/i', $window)) {

            $text = implode(
                "\n",
                array_slice($lines, 0, $i)
            );

            break 2;
        }
    }
}

$text = trim($text);

    /*
    |--------------------------------------------------------------------------
    | 2. Outlook / Microsoft style quoted reply
    |--------------------------------------------------------------------------
    */

    $text = preg_split(
        '/(?:^|\n)\s*-{2,}\s*Original Message\s*-{2,}\s*(?:\n|$)/i',
        $text,
        2
    )[0];

    /*
    |--------------------------------------------------------------------------
    | 3. Other common email separators
    |--------------------------------------------------------------------------
    */

    $text = preg_split(
        '/(?:^|\n)\s*_{5,}\s*(?:\n|$)/',
        $text,
        2
    )[0];

    /*
    |--------------------------------------------------------------------------
    | 4. Lines beginning with ">"
    |--------------------------------------------------------------------------
    */

    $lines = explode("\n", $text);

    $cleanLines = [];

    foreach ($lines as $line) {

        $trimmedLine = trim($line);

        /*
        * Stop when quoted content starts.
        */
        if ($trimmedLine !== '' && str_starts_with($trimmedLine, '>')) {
            break;
        }

        /*
        * Some clients use multiple ">" characters.
        */
        if (
            $trimmedLine !== '' &&
            preg_match('/^>{1,}/', $trimmedLine)
        ) {
            break;
        }

        $cleanLines[] = $line;
    }

    $text = implode("\n", $cleanLines);

    /*
    |--------------------------------------------------------------------------
    | 5. Remove Outlook "From: / Sent: / To:" header block
    |--------------------------------------------------------------------------
    |
    | Bounded to 3 consecutive lines only (no DOTALL) so it can't span
    | across real paragraphs the way the old Gmail regex did.
    |
    */

    $lines = explode("\n", $text);

    for ($i = 0, $count = count($lines); $i < $count - 2; $i++) {

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
    | 6. Remove trailing blank lines
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

    /*
     * Dewrap lines that were auto-wrapped by the email client
     * (single newlines that are NOT part of a blank-line run).
     */
    $text = preg_replace('/(?<!\n)\n(?!\n)/', ' ', $text);

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

        $paragraph = htmlspecialchars(
            $paragraph,
            ENT_NOQUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $paragraph = str_replace("\n", '<br>', $paragraph);

        $html .= '<p>' . $paragraph . '</p>';
    }

    return $html;
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

        $text = strip_tags($html);

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

        $text = html_entity_decode(
            $text,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        /*
        |--------------------------------------------------------------------------
        | Convert non-breaking spaces
        |--------------------------------------------------------------------------
        */

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
        array $documentPaths = []
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

            $messages['body-text'] = $this->convertPlainTextToHtml($replyText);

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
