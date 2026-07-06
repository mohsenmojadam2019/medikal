<?php

namespace App\Listeners\Pharmacy;

use App\Events\Pharmacy\PrescriptionRequested;
use App\Models\PharmacyNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPrescriptionRequestNotification implements ShouldQueue
{
    public function handle(PrescriptionRequested $event)
    {
        $request = $event->prescriptionRequest;

        // نوتیفیکیشن به ادمین
        PharmacyNotification::create([
            'patient_id' => null,
            'order_id' => null,
            'type' => 'prescription_requested',
            'title' => '📋 درخواست نسخه جدید',
            'message' => "درخواست نسخه جدید از کاربر {$request->user->name} دریافت شد",
            'data' => [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'pharmacy_id' => $request->pharmacy_id,
            ],
            'sent_at' => now(),
        ]);

        \Log::info("New prescription request: ID {$request->id} from user {$request->user_id}");
    }
}
