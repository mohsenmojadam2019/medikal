<?php

namespace App\Console\Commands;

use App\Models\LabResult;
use App\Enums\LabResultStatusEnum;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;

class LabCheckCriticalCommand extends Command
{
    protected $signature = 'lab:check-critical';
    protected $description = 'Check for critical lab results and notify doctors';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle(): void
    {
        $this->info('Checking for critical lab results...');

        $criticalResults = LabResult::where('is_critical', true)
            ->where('is_active', true)
            ->whereNull('read_at')
            ->with(['labOrder', 'labOrder.doctor', 'labOrder.patient'])
            ->get();

        foreach ($criticalResults as $result) {
            $this->sendCriticalNotification($result);
            $result->markAsRead();
            $this->info("Critical result notified: ID {$result->id}");
        }

        $this->info('Done checking critical results.');
    }

    private function sendCriticalNotification(LabResult $result): void
    {
        $order = $result->labOrder;

        // Notify doctor
        if ($order->doctor && $order->doctor->user) {
            $this->notificationService->sendToUser(
                $order->doctor->user_id,
                '⚠️ نتیجه بحرانی آزمایش',
                "نتیجه بحرانی برای تست {$result->labTest->name} در سفارش {$order->order_number} بیمار {$order->patient->full_name} ثبت شده است.",
                [
                    'order_id' => $order->id,
                    'result_id' => $result->id,
                    'test_name' => $result->labTest->name,
                    'value' => $result->value,
                ],
                'critical',
                'urgent'
            );
        }

        // Notify patient
        if ($order->patient && $order->patient->user) {
            $this->notificationService->sendToUser(
                $order->patient->user_id,
                '⚠️ نتیجه آزمایش بحرانی',
                "نتیجه بحرانی برای یکی از تست‌های سفارش {$order->order_number} ثبت شده است. لطفاً با پزشک خود تماس بگیرید.",
                ['order_id' => $order->id],
                'critical',
                'urgent'
            );
        }
    }
}
