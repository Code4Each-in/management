<?php

namespace App\Console\Commands;

use App\Models\Users;
use Illuminate\Console\Command;

class IssueUpworkAgentToken extends Command
{
    protected $signature = 'upwork:agent-token {email : Existing PMS user the agent acts as} {--name=claude-upwork-agent : Token name}';
    protected $description = 'Issue a Sanctum API token (upwork:ingest ability only) for the Claude Upwork agent';

    public function handle()
    {
        $user = Users::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No user found with that email. Create the user in the PMS first.');
            return 1;
        }

        $token = $user->createToken($this->option('name'), [config('upwork.agent_token_ability')]);

        $this->info('Token issued for ' . $user->email . '. It is shown only once — store it in the agent\'s secret store, never in the repository:');
        $this->line($token->plainTextToken);

        return 0;
    }
}
