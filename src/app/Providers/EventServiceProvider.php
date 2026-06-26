    protected $listen = [
        \App\Events\AppointmentCreated::class => [
            \App\Listeners\CreateAppointmentReminders::class,
        ],
    ];
