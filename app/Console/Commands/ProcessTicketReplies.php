<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webklex\PHPIMAP\ClientManager;
use App\Models\TicketComments;
use App\Models\Users;
use App\Models\TicketFiles;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class ProcessTicketReplies extends Command
{

    protected $signature = 'tickets:process-replies';

    protected $description = 'Process ticket email replies';



    public function handle()
    {

        echo "command started\n";


        $cm = new ClientManager();


        $client = $cm->make([

            'host'=>env('IMAP_HOST'),
            'port'=>env('IMAP_PORT'),
            'encryption'=>env('IMAP_ENCRYPTION'),
            'validate_cert'=>env('IMAP_VALIDATE_CERT'),
            'username'=>env('IMAP_USERNAME'),
            'password'=>env('IMAP_PASSWORD'),
            'protocol'=>env('IMAP_PROTOCOL'),

        ]);

        $client->connect();


        $folder = $client->getFolder('INBOX');

        $messages = $folder
            ->messages()
            ->all()
            ->since(now()->startOfDay())
            ->setFetchOrder('desc')
            ->get();



        foreach($messages as $message)
        {


            echo "\n----------------\n";


            $from = $message->getFrom()[0]->mail ?? null;


            echo "FROM : ".$from."\n";


            if(!$from)
            {
                continue;
            }


            $inReplyTo = $this->extractHeaderValue($message->getInReplyTo());

            $inReplyTo = $inReplyTo ? trim($inReplyTo, '<> ') : null;


            $referencesRaw = $this->extractHeaderValue($message->getReferences());

            $references = null;

            if($referencesRaw)
            {
                // References is a space separated chain, oldest -> newest.
                // The direct parent is the LAST id in that chain.
                $refIds = preg_split('/\s+/', trim($referencesRaw));

                $lastRef = end($refIds);

                if($lastRef)
                {
                    $references = trim($lastRef, '<> ');
                }
            }


            echo "IN REPLY TO : ".($inReplyTo ?? 'null')."\n";

            echo "REFERENCES : ".($references ?? 'null')."\n";


            $parentId = $inReplyTo ?: $references;



            if(!$parentId)
            {

                echo "Not ticket reply\n";

                continue;

            }

            $parent = TicketComments::where(
                'email_message_id',
                $parentId
            )->first();



            if(!$parent)
            {

                echo "Parent comment not found for: ".$parentId."\n";

                continue;

            }

            $user = Users::where(
                'email',
                $from
            )->first();



            if(!$user)
            {

                echo "User not found\n";

                continue;

            }


            $body = $message->getTextBody();



            if(!$body)
            {
                $body = $message->getHTMLBody();
            }



            $replyText = trim($body);

            $replyText = $this->stripQuotedReply($replyText);



            if(empty($replyText))
            {
                continue;
            }


            $documentPaths = $this->saveIncomingAttachments($message);




            /*
            |--------------------------------------------------------------------------
            | Avoid duplicate replies
            |--------------------------------------------------------------------------
            */


            $incomingMessageId = $this->extractHeaderValue($message->getMessageId());

            $incomingMessageId = $incomingMessageId ? trim($incomingMessageId, '<> ') : null;


            if($incomingMessageId)
            {

                $exists = TicketComments::where(
                    'email_message_id',
                    $incomingMessageId
                )->first();



                if($exists)
                {

                    echo "Already imported\n";

                    continue;

                }

            }

            /*
            |--------------------------------------------------------------------------
            | Save as ticket comment
            |--------------------------------------------------------------------------
            */


            $newComment = TicketComments::create([

                'ticket_id'=>$parent->ticket_id,

                'comments'=>$replyText,

                'comment_by'=>$user->id,

                'reply_to'=>$parent->id,

                'email_message_id'=>$incomingMessageId,

                'document'=>implode(',', $documentPaths),

                'is_system'=>0,

            ]);



            foreach($documentPaths as $docPath)
            {

                TicketFiles::create([

                    'document'=>$docPath,

                    'ticket_id'=>$parent->ticket_id,

                ]);

            }



            echo "CLIENT REPLY SAVED\n";

            if(!empty($documentPaths))
            {
                echo "ATTACHMENTS SAVED: ".count($documentPaths)."\n";
            }


            if($user->role_id == 6)
            {

                DB::table('comment_status')->insert([

                    'comment_id'=>$newComment->id,

                    'ticket_id'=>$parent->ticket_id,

                    'status'=>'pending',

                    'created_at'=>now(),

                    'updated_at'=>now(),

                ]);


                echo "comment_status row inserted (pending)\n";

            }


        }



        return Command::SUCCESS;

    }

    private function stripQuotedReply(string $text): string
    {

        // Normalize line endings first so the patterns below behave consistently.
        $text = preg_replace('/\r\n|\r/', "\n", $text);


        $text = preg_replace(
            '/On\s.{0,300}?wrote\s*:.*/isU',
            '',
            $text,
            1
        );

        $text = preg_split(
            '/-{2,}\s*Original Message\s*-{2,}|_{10,}/i',
            $text
        )[0];

        $lines = explode("\n", $text);

        $cleanLines = [];


        foreach($lines as $line)
        {

            if(preg_match('/^\s*>/', $line))
            {
                break;
            }

            $cleanLines[] = $line;

        }


        $text = implode("\n", $cleanLines);



        return trim($text);

    }

    private function saveIncomingAttachments($message): array
    {

        $documentPaths = [];


        try
        {

            if(!$message->hasAttachments())
            {
                return $documentPaths;
            }


            $destDir = public_path('assets/img/ticketAssets');


            if(!is_dir($destDir))
            {
                mkdir($destDir, 0755, true);
            }


            foreach($message->getAttachments() as $attachment)
            {

                try
                {

                    $originalName = $attachment->getName();

                    if(!$originalName)
                    {
                        $originalName = 'attachment_'.Str::random(6);
                    }


                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);

                    $baseName = pathinfo($originalName, PATHINFO_FILENAME);


                    $safeName = date('YmdHis').'_'.Str::slug($baseName).($extension ? '.'.$extension : '');


                    $destPath = $destDir.DIRECTORY_SEPARATOR.$safeName;


                    // getContent() returns the raw decoded attachment bytes
                    $content = $attachment->getContent();


                    if($content === null || $content === '')
                    {
                        echo "Attachment '".$originalName."' had no content, skipping\n";
                        continue;
                    }


                    file_put_contents($destPath, $content);


                    $documentPaths[] = 'ticketAssets/'.$safeName;


                    echo "Saved attachment: ".$originalName." -> ".$safeName."\n";

                }
                catch(\Throwable $e)
                {

                    \Log::error('Failed to save inbound attachment: '.$e->getMessage());

                    echo "Failed to save an attachment, see log\n";

                }

            }

        }
        catch(\Throwable $e)
        {

            \Log::error('Failed to read attachments from inbound message: '.$e->getMessage());

        }


        return $documentPaths;

    }



    /**
     * Safely pull a plain string value out of whatever
     * Webklex\PHPIMAP returns (Attribute object, array, or string).
     */
    private function extractHeaderValue($value): ?string
    {

        if($value === null)
        {
            return null;
        }


        if(is_string($value))
        {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }


        if(is_array($value))
        {
            $first = reset($value);

            return $first !== false ? $this->extractHeaderValue($first) : null;
        }


        if(is_object($value))
        {

            // Webklex\PHPIMAP\Attribute exposes first()
            if(method_exists($value, 'first'))
            {
                $first = $value->first();

                if($first !== null && $first !== '')
                {
                    return $this->extractHeaderValue($first);
                }
            }


            // Fallback: cast if the object supports __toString
            if(method_exists($value, '__toString'))
            {
                $str = trim((string) $value);

                return $str !== '' ? $str : null;
            }

        }


        return null;

    }

}
