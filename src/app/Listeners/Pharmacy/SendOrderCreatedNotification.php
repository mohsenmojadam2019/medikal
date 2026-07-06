<?php

namespace App\Listeners\Pharmacy;

use App\Events\Pharmacy\OrderCreated;
use App\Models\PharmacyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderCreatedNotification implements ShouldQueue
{
    public function handle(OrderCreated $event)
    {
        $order = $event->order;
        $user = $order->user;
        $pharmacy = $order->pharmacy;

        // ذخیره در دیتابیس
        PharmacyNotification::create([
            'patient_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'order_created',
            'title' => 'سفارش جدید ثبت شد',
            'message' => "سفارش شماره {$order->order_number} با موفقیت ثبت شد. منتظر تایید داروخانه باشید.",
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $order->total_amount,
            ],
            'sent_at' => now(),
        ]);

        // ارسال SMS (اگر سرویس SMS دارید)
        if ($user->mobile) {
            try {
                // app(SmsService::class)->send($user->mobile, "سفارش داروخانه شما با شماره {$order->order_number} ثبت شد. مبلغ: {$order->total_amount} تومان");
                \Log::info("SMS would be sent to {$user->mobile}: Order {$order->order_number} created");
            } catch (\Exception $e) {
                \Log::error('SMS send failed: ' . $e->getMessage());
            }
        }
    }
}
