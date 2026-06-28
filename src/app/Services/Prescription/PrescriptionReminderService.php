<?php

namespace App\Services\Prescription;

use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PrescriptionReminderService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function generateReminders(Prescription $prescription): array
    {
        return $prescription->generateReminders();
    }

    public function getExpiringSoon(int $days = 3)
    {
        return Prescription::where('tenant_id', $this->tenantId)
            ->expiringSoon($days)
            ->get();
    }

    public function getExpired()
    {
        return Prescription::where('tenant_id', $this->tenantId)
            ->expired()
            ->get();
    }

    public function sendExpiryReminders(): int
    {
        $prescriptions = $this->getExpiringSoon();
        $count = 0;

        foreach ($prescriptions as $prescription) {
            try {
                $patient = $prescription->patient;
                if ($patient && $patient->phone) {
                    $message = "داروی {$prescription->drug_name} شما تا {$prescription->end_date->format('Y/m/d')} باقی مانده است.";
                    Log::info("Reminder sent for prescription {$prescription->code}", [
                        'tenant_id' => $this->tenantId,
                        'prescription_id' => $prescription->id,
                    ]);
                    $count++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to send reminder for prescription {$prescription->code}: " . $e->getMessage(), [
                    'tenant_id' => $this->tenantId,
                ]);
            }
        }

        return $count;
    }
}
