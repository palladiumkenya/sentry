<?php

namespace App\Console;

use App\Jobs\GetSpotFacilities;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        //
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new GetSpotFacilities())->everyTenMinutes()->withoutOverlapping();
        $schedule->command('sentry:get-indicators')->mondays()->at('7:00');
        $schedule->command('sentry:post-live-sync-indicators')->everyThirtyMinutes()->withoutOverlapping();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
