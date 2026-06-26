<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRemindersJob;
use Illuminate\Console\Command;

class ProcessReminders extends Command
{
    protected $signature = 'reminders:process';
    protected $description = 'Process pending reminders';

    public function handle(): void
    {
        $this->info('Processing reminders...');
        ProcessRemindersJob::dispatch();
        $this->info('Reminders queued for processing.');
    }
}
