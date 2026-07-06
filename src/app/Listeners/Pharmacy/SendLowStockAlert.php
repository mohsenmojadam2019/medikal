<?php

namespace App\Listeners\Pharmacy;

use App\Events\Pharmacy\LowStockAlert;
use App\Models\PharmacyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLowStockAlert implements ShouldQueue
{
    public function handle(LowStockAlert $event)
    {
        $product = $event->product;

        // ارسال نوتیفیکیشن به داروخانه
        PharmacyNotification::create([
            'patient_id' => null,
            'order_id' => null,
            'type' => 'low_stock_alert',
            'title' => '⚠️ هشدار موجودی کم',
            'message' => "موجودی محصول {$product->name} به کمتر از حد مجاز رسیده است. موجودی فعلی: {$product->stock}",
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'current_stock' => $product->stock,
                'min_stock' => $product->min_stock,
            ],
            'sent_at' => now(),
        ]);

        \Log::warning("Low stock alert: {$product->name} - Stock: {$product->stock}");
    }
}
