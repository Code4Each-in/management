<?php

namespace App\Console\Commands;

use App\Services\Upwork\Api\UpworkClientFactory;
use App\Services\Upwork\Discovery\ServerDiscoveryRunner;
use Illuminate\Console\Command;

/**
 * One server-side discovery cycle: read-only Upwork searches and job details,
 * then local scoring. Scheduled runs only happen when discovery is switched on
 * in the PMS and the interval has passed.
 */
class UpworkDiscover extends Command
{
    protected $signature = 'upwork:discover {--manual : run now, even if scheduled discovery is switched off} {--client= : graphql or fixture (fixture: not in production)}';
    protected $description = 'Run one read-only Upwork discovery cycle on the server';

    public function handle(ServerDiscoveryRunner $runner)
    {
        $client = $this->option('client') ? UpworkClientFactory::make((string) $this->option('client')) : null;
        $out = $runner->run($this->option('manual') ? 'manual' : 'scheduled', $client);

        $this->line("Discovery {$out['status']}" . ($out['run'] ? " (run {$out['run']})" : '') . ($out['message'] ? ": {$out['message']}" : ''));
        if ($out['stats']) {
            $this->line(collect($out['stats'])->map(fn($v, $k) => "{$k}=" . ($v ?? '-'))->implode(' '));
        }

        return $out['status'] === 'failed' ? 1 : 0;
    }
}
