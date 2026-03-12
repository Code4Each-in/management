<?php

namespace App\Console\Commands;

use App\Models\ProjectLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeleteOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'logs:delete-old';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete project logs older than 30 days';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $deleted = ProjectLog::where('logged_at', '<', Carbon::now()->subDays(30))->delete();

        $this->info("Deleted {$deleted} old logs.");
    }
}
