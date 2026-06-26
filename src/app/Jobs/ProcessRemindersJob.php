<?php

namespace App\Jobs;

use App\Services\Reminder\ReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ReminderService $reminderService): void
    {
        try {
            $count = $reminderService->processPendingReminders();
            Log::info("Processed {$count} reminders");
        } catch (\Exception $e) {
            Log::error('Reminder job failed: ' . $e->getMessage());
        }
    }
}
