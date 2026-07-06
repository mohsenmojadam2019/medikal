    protected $listen = [
        \App\Events\AppointmentCreated::class => [
            \App\Listeners\CreateAppointmentReminders::class,
        ],
    ];

    protected $listen = [
        // Pharmacy Events
        \App\Events\Pharmacy\OrderCreated::class => [
            \App\Listeners\Pharmacy\SendOrderCreatedNotification::class,
        ],
        \App\Events\Pharmacy\OrderStatusChanged::class => [
            \App\Listeners\Pharmacy\SendOrderStatusNotification::class,
        ],
        \App\Events\Pharmacy\LowStockAlert::class => [
            \App\Listeners\Pharmacy\SendLowStockAlert::class,
        ],
        \App\Events\Pharmacy\PrescriptionRequested::class => [
            \App\Listeners\Pharmacy\SendPrescriptionRequestNotification::class,
        ],
    ];
