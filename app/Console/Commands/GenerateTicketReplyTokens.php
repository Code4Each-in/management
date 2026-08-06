<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\Tickets;

class GenerateTicketReplyTokens extends Command
{
    protected $signature = 'tickets:generate-reply-tokens';

    protected $description = 'Generate reply tokens for old tickets';

    public function handle()
    {
        Tickets::whereNull('reply_token')
            ->chunk(100, function ($tickets) {

                foreach ($tickets as $ticket) {

                    $ticket->reply_token = Str::upper(Str::random(12));
                    $ticket->save();

                    $this->info(
                        "Generated token for ticket #".$ticket->id
                    );
                }

            });

        $this->info("Completed.");

        return Command::SUCCESS;
    }
}
