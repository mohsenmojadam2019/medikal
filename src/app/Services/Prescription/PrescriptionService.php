<?php

namespace App\Services\Prescription;

use App\Models\Prescription;
use App\Models\Appointment;
use App\Enums\PrescriptionStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionService
{
    /**
     * لیست نسخه‌ها
     */
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Prescription::with(['patient.user', 'doctor.user', 'appointment']);

        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('drug_name', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('diagnosis', 'LIKE', "%{$filters['search']}%");
            });
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * ایجاد نسخه از نوبت
     */
    public function createFromAppointment(Appointment $appointment, array $data): Prescription
    {
        return DB::transaction(function () use ($appointment, $data) {
            // محاسبه تاریخ پایان
            $startDate = $data['start_date'] ?? now()->toDateString();
            $duration = $data['duration'] ?? 7;

            $prescription = Prescription::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'drug_name' => $data['drug_name'],
                'dosage' => $data['dosage'],
                'frequency' => $data['frequency'] ?? 3,
                'duration' => $duration,
                'start_date' => $startDate,
                'end_date' => now()->parse($startDate)->addDays($duration)->toDateString(),
                'instructions' => $data['instructions'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => PrescriptionStatusEnum::ACTIVE,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // تولید یادآوری‌ها
            $this->generateReminders($prescription);

            return $prescription;
        });
    }

    /**
     * نمایش نسخه
     */
    public function show(int $id): Prescription
    {
        return Prescription::with([
            'patient.user',
            'doctor.user',
            'appointment'
        ])->findOrFail($id);
    }

    /**
     * به‌روزرسانی نسخه
     */
    public function update(Prescription $prescription, array $data): Prescription
    {
        return DB::transaction(function () use ($prescription, $data) {
            $prescription->update($data);

            // اگر تاریخ شروع یا مدت تغییر کرد، تاریخ پایان رو محاسبه کن
            if (isset($data['start_date']) || isset($data['duration'])) {
                $startDate = $data['start_date'] ?? $prescription->start_date;
                $duration = $data['duration'] ?? $prescription->duration;
                $prescription->update([
                    'end_date' => now()->parse($startDate)->addDays($duration)->toDateString()
                ]);
            }

            return $prescription->fresh();
        });
    }

    /**
     * تغییر وضعیت نسخه
     */
    public function changeStatus(Prescription $prescription, string $status): Prescription
    {
        $method = match ($status) {
            'activate' => 'activate',
            'complete' => 'complete',
            'cancel' => 'cancel',
            'expire' => 'expire',
            default => throw new \Exception('وضعیت نامعتبر است'),
        };

        $prescription->$method();
        return $prescription->fresh();
    }

    /**
     * حذف نسخه
     */
    public function delete(Prescription $prescription): void
    {
        $prescription->delete();
    }

    /**
     * نسخه‌های بیمار
     */
    public function patientPrescriptions(int $patientId, array $filters = [], int $perPage = 15)
    {
        $query = Prescription::where('patient_id', $patientId)
            ->with(['doctor.user']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['active']) && $filters['active']) {
            $query->active();
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * نسخه‌های پزشک
     */
    public function doctorPrescriptions(int $doctorId, array $filters = [], int $perPage = 15)
    {
        $query = Prescription::where('doctor_id', $doctorId)
            ->with(['patient.user']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * بررسی تداخل دارویی
     */
    public function checkInteractions(Prescription $prescription): array
    {
        return $prescription->getDrugInteractions();
    }

    /**
     * تولید یادآوری‌های دارو
     */
    public function generateReminders(Prescription $prescription): array
    {
        $reminders = $prescription->generateReminders();

        // ذخیره در دیتابیس (اگر جدول reminders وجود داشته باشه)
        // یا ارسال به صف برای پردازش

        Log::info('Reminders generated for prescription', [
            'prescription_id' => $prescription->id,
            'patient_id' => $prescription->patient_id,
            'count' => count($reminders),
        ]);

        return $reminders;
    }

    /**
     * نسخه‌های در حال انقضا
     */
    public function getExpiringSoon(int $days = 3)
    {
        return Prescription::expiringSoon($days)->with(['patient.user', 'doctor.user'])->get();
    }

    /**
     * نسخه‌های منقضی شده
     */
    public function getExpired()
    {
        return Prescription::expired()->with(['patient.user'])->get();
    }

    /**
     * آمار نسخه‌ها
     */
    public function getStats(): array
    {
        return [
            'total' => Prescription::count(),
            'active' => Prescription::active()->count(),
            'pending' => Prescription::pending()->count(),
            'completed' => Prescription::where('status', PrescriptionStatusEnum::COMPLETED)->count(),
            'cancelled' => Prescription::where('status', PrescriptionStatusEnum::CANCELLED)->count(),
            'expired' => Prescription::where('status', PrescriptionStatusEnum::EXPIRED)->count(),
            'expiring_soon' => Prescription::expiringSoon()->count(),
        ];
    }

    /**
     * چاپ نسخه
     */
    public function getPrintData(Prescription $prescription): array
    {
        return [
            'prescription' => $prescription->load(['patient.user', 'doctor.user']),
            'patient' => [
                'name' => $prescription->patient->full_name,
                'national_code' => $prescription->patient->national_code,
                'phone' => $prescription->patient->phone,
            ],
            'doctor' => [
                'name' => $prescription->doctor->full_name,
                'specialty' => $prescription->doctor->specialty?->name,
                'license_number' => $prescription->doctor->license_number,
            ],
            'details' => [
                'drug' => $prescription->drug_name,
                'dosage' => $prescription->dosage,
                'frequency' => $prescription->frequency_label,
                'duration' => $prescription->duration . ' روز',
                'start_date' => $prescription->start_date->format('Y/m/d'),
                'end_date' => $prescription->end_date->format('Y/m/d'),
                'instructions' => $prescription->instructions,
                'diagnosis' => $prescription->diagnosis,
            ],
            'daily_times' => $prescription->daily_times,
        ];
    }
}
