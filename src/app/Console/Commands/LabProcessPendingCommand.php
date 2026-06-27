<?php

namespace App\Console\Commands;

use App\Models\LabOrder;
use App\Enums\LabOrderStatusEnum;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class LabProcessPendingCommand extends Command
{
    protected $signature = 'lab:process-pending';
    protected $description = 'Process pending lab orders and send reminders';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle(): void
    {
        $this->info('Processing pending lab orders...');

        // Orders pending for more than 24 hours without payment
        $pendingOrders = LabOrder::where('status', LabOrderStatusEnum::PENDING)
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->get();

        foreach ($pendingOrders as $order) {
            $this->sendReminder($order);
            $this->info("Reminder sent for order: {$order->order_number}");
        }

        // Orders waiting for sample collection for more than 48 hours
        $waitingForSample = LabOrder::where('status', LabOrderStatusEnum::PAID)
            ->where('updated_at', '<', Carbon::now()->subHours(48))
            ->get();

        foreach ($waitingForSample as $order) {
            $this->sendSampleReminder($order);
            $this->info("Sample reminder sent for order: {$order->order_number}");
        }

        $this->info('Done processing pending lab orders.');
    }

    private function sendReminder(LabOrder $order): void
    {
        if ($order->patient && $order->patient->user) {
            $this->notificationService->sendToUser(
                $order->patient->user_id,
                'یادآوری سفارش آزمایش',
                "سفارش آزمایش {$order->order_number} هنوز پرداخت نشده است. لطفاً برای تکمیل فرآیند اقدام کنید.",
                ['order_id' => $order->id],
                'reminder'
            );
        }
    }

    private function sendSampleReminder(LabOrder $order): void
    {
        if ($order->patient && $order->patient->user) {
            $this->notificationService->sendToUser(
                $order->patient->user_id,
                'یادآوری نمونه‌گیری',
                "سفارش آزمایش {$order->order_number} پرداخت شده است. لطفاً برای نمونه‌گیری به آزمایشگاه مراجعه کنید.",
                ['order_id' => $order->id],
                'reminder'
            );
        }
    }
}
