<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        //
    ];

    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('sentry:schedule-etl')->everyMinute()->withoutOverlapping();
        // $schedule->command('sentry:get-indicators')->mondays()->at('7:00')->withoutOverlapping();
        // $schedule->command('sentry:post-live-sync-indicators')->everyThirtyMinutes()->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
