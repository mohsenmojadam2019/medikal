<?php

namespace App\Listeners;

use App\Events\AppointmentCreated;
use App\Services\Reminder\ReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateAppointmentReminders implements ShouldQueue
{
    protected ReminderService $reminderService;

    public function __construct(ReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    public function handle(AppointmentCreated $event): void
    {
        $this->reminderService->createReminders($event->appointment);
    }
}
