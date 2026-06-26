<?php

namespace App\Services\Prescription;

use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PrescriptionReminderService
{
    /**
     * تولید یادآوری‌های دارو برای یک نسخه
     */
    public function generateReminders(Prescription $prescription): array
    {
        return $prescription->generateReminders();
    }

    /**
     * دریافت نسخه‌های در حال انقضا
     */
    public function getExpiringSoon(int $days = 3)
    {
        return Prescription::expiringSoon($days)->get();
    }

    /**
     * دریافت نسخه‌های منقضی شده
     */
    public function getExpired()
    {
        return Prescription::expired()->get();
    }

    /**
     * ارسال یادآوری برای نسخه‌های در حال انقضا
     */
    public function sendExpiryReminders(): int
    {
        $prescriptions = $this->getExpiringSoon();
        $count = 0;

        foreach ($prescriptions as $prescription) {
            try {
                $patient = $prescription->patient;
                if ($patient && $patient->phone) {
                    $message = "داروی {$prescription->drug_name} شما تا {$prescription->end_date->format('Y/m/d')} باقی مانده است.";
                    // app(SmsManager::class)->send($patient->phone, $message);
                    Log::info("Reminder sent for prescription {$prescription->code}");
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to send reminder for prescription {$prescription->code}: " . $e->getMessage());
            }
        }

        return $count;
    }
}
