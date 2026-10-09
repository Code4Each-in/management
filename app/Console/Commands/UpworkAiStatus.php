<?php

namespace App\Console\Commands;

use App\Services\Upwork\Ai\AiGateway;
use Illuminate\Console\Command;

/** Print AI budget/usage status. Makes no AI request. */
class UpworkAiStatus extends Command
{
    protected $signature = 'upwork:ai-status';
    protected $description = 'Show Upwork AI configuration, usage and budget status (no AI request is made)';

    public function handle()
    {
        $s = AiGateway::fromSettings()->status();
        $this->line(sprintf('AI %s · provider %s (%s) · model %s', $s['enabled'] ? 'ENABLED' : 'disabled', $s['provider'], $s['configured'] ? 'configured' : 'NOT configured', $s['model']));
        $this->table(['', 'Requests', 'Tokens', 'Est. cost (USD)'], [
            ['Today (UTC)', $s['today']['requests'], $s['today']['tokens'], number_format($s['today']['cost'], 6) . ' / ' . $s['daily_budget_usd']],
            ['This month (UTC)', $s['month']['requests'], $s['month']['tokens'], number_format($s['month']['cost'], 6) . ' / ' . $s['monthly_budget_usd']],
        ]);
        if ($s['blocked']) {
            $this->warn($s['blocked']);
        }

        return 0;
    }
}
