<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('apmailer:run')
                  ->everyFiveMinutes();

        $schedule->command('apindexer:run')
            ->everyMinute();

        $schedule->command('quotemailer:run')
                  ->everyMinute();

        $schedule->command('invoice fetch_invoices')
//                  ->everyFiveMinutes();
                  ->everyMinute();

        $schedule->command('invoice check_deleted')
//                  ->everyFiveMinutes();
                  ->everyMinute();
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
