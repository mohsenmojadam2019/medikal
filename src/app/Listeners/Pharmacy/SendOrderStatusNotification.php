<?php

namespace App\Listeners\Pharmacy;

use App\Events\Pharmacy\OrderStatusChanged;
use App\Models\PharmacyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusNotification implements ShouldQueue
{
    public function handle(OrderStatusChanged $event)
    {
        $order = $event->order;
        $user = $order->user;

        $statusMessages = [
            'paid' => 'پرداخت سفارش شما تایید شد',
            'preparing' => 'سفارش شما در حال آماده‌سازی است',
            'ready' => 'سفارش شما آماده تحویل است',
            'delivered' => 'سفارش شما تحویل داده شد',
            'cancelled' => 'سفارش شما لغو شد',
        ];

        $message = $statusMessages[$event->newStatus] ?? "وضعیت سفارش شما به {$event->newStatus} تغییر کرد";

        // ذخیره در دیتابیس
        PharmacyNotification::create([
            'patient_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'order_status_changed',
            'title' => 'تغییر وضعیت سفارش',
            'message' => "سفارش شماره {$order->order_number}: {$message}",
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
            ],
            'sent_at' => now(),
        ]);

        // ارسال SMS
        if ($user->mobile) {
            try {
                // app(SmsService::class)->send($user->mobile, "{$message}. شماره سفارش: {$order->order_number}");
                \Log::info("SMS would be sent to {$user->mobile}: Order {$order->order_number} status changed to {$event->newStatus}");
            } catch (\Exception $e) {
                \Log::error('SMS send failed: ' . $e->getMessage());
            }
        }
    }
}
