<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        Commands\QuarterlyLeaves::class,
        // Commands\TestScheduler::class,
        Commands\CheckReminders::class
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
                \Log::error('Error executing cron job: ' . $e->getMessage());
            }
        })->cron('0 0 1 */3 *');
  
        $schedule->call(function () {
            try {
                Artisan::call('votes:winner');
                info('Cron job for selecting the winner executed successfully!');
            } catch (\Exception $e) {
                \Log::error('Error executing cron job: ' . $e->getMessage());
            }
        })->monthlyOn(1, '00:01');

        $schedule->command('reminders:check')->everyMinute();
        $schedule->command('send:project-reports')->monthlyOn(1, '10:00');
     
        $schedule->command('emails:send-scheduled')->everyMinute();
        $schedule->command('reminders:send')->everyMinute()->withoutOverlapping();
        $schedule->command('SendMailToClient')->everyMinute();

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
