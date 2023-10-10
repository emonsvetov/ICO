<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

// use App\Jobs\v2migrate\MigrateUsersJob;

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
        // $schedule->command('inspire')->hourly();
        $schedule->command('cron:monthly-invoicing')->monthly();
        $schedule->command('cron:submit-tango-orders')->hourly();
        $schedule->command('cron:send-activation-reminder')->daily();
        // $schedule->command('v2migrate:users --skip-inactive')->daily();
        // $schedule->job(new MigrateUsersJob)->daily();
        $schedule->command('cron:generate-virtual-inventory')->everyFiveMinutes();
        $schedule->command('cron:send-milestone-award')->daily();
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
