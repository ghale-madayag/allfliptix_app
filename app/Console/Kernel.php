<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\FetchCurrentYearInventory::class,
        \App\Console\Commands\FetchLastYearInventory::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('fetch:current-year')->everyFifteenMinutes();
        $schedule->command('fetch:last-year')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
