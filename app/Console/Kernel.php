<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        Commands\QuarterlyLeaves::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->call(function () {
            try {
                Artisan::call('leaves:quarterly');
                info('Cron job for quarterly leave executed successfully!');
            } catch (\Exception $e) {
                // Log or handle the exception
                \Log::error('Error executing quarterly leaves cron job: ' . $e->getMessage());
            }
        })->cron('0 0 1 */3 *');
        // $schedule->command('credit-leaves:quarterly')->cron('0 0 1 */3 *');

        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
