<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    protected $commands = [
        Commands\ScheduleMeetingReminder::class,
        Commands\ActionItemReminder::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:meeting-reminder')
        ->everyMinute()
        ->timezone(env('TIME_ZONE', 'Asia/Calcutta'));

        $schedule->command('send:action-items-reminder')
        ->everyMinute()
        ->timezone(env('TIME_ZONE', 'Asia/Calcutta'));
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