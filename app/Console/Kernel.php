<?php

namespace Kami\Cocktail\Console;

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
    #[\Override]
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
        // $schedule->command('horizon:snapshot')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    #[\Override]
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }
}
