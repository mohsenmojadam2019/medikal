<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Reminder Jobs
        $schedule->job(new \App\Jobs\ProcessRemindersJob())->everyFiveMinutes();
        $schedule->command('reminders:process')->everyMinute();

        // Lab: Process pending orders (send reminders for sample collection)
        $schedule->command('lab:process-pending')->everyTenMinutes();

        // Lab: Check for critical results notification
        $schedule->command('lab:check-critical')->everyFifteenMinutes();

        // Lab: Clean up old results files (30 days)
        $schedule->command('lab:cleanup-files')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
