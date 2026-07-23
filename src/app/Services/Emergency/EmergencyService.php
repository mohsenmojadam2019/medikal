<?php
// app/Services/Emergency/EmergencyService.php

namespace App\Services\Emergency;

use App\Models\Emergency\EmergencyPatient;
use App\Models\Patient;
use App\Models\Clinic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    /**
     * ثبت درخواست اورژانس جدید (کاربر)
     */
    public function createEmergencyRequest(array $data): EmergencyPatient
    {
        return DB::transaction(function () use ($data) {
            // پیدا کردن یا ایجاد بیمار
            $patient = $this->getOrCreatePatient($data);

            // پیدا کردن نزدیک‌ترین کلینیک
            $clinic = $this->findNearestClinic(
                $data['latitude'] ?? null,
                $data['longitude'] ?? null
            );

            $emergency = EmergencyPatient::create([
                'patient_id' => $patient->id,
                'clinic_id' => $clinic?->id,
                'province_id' => $data['province_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'emergency_contact_relation' => $data['emergency_contact_relation'] ?? null,
                'request_latitude' => $data['latitude'] ?? null,
                'request_longitude' => $data['longitude'] ?? null,
                'request_address' => $data['address'] ?? null,
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'history_of_present_illness' => $data['history'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'medications' => $data['medications'] ?? null,
                'past_medical_history' => $data['past_medical_history'] ?? null,
                'status' => 'waiting',
                'arrival_time' => now(),
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // ارسال نوتیفیکیشن به ادمین
            $this->sendEmergencyNotification($emergency);

            Log::info('🚑 Emergency request created', [
                'emergency_id' => $emergency->id,
                'patient_id' => $patient->id,
                'clinic_id' => $clinic?->id,
            ]);

            return $emergency->load(['patient', 'patient.user', 'clinic', 'province', 'city']);
        });
    }

    /**
     * دریافت لیست درخواست‌های اورژانس (ادمین)
     */
    public function getEmergencyRequests(array $filters = [], int $perPage = 20)
    {
        $query = EmergencyPatient::with([
            'patient',
            'patient.user',
            'clinic',
            'province',
            'city',
            'doctor',
            'doctor.user',
            'admission',
        ]);

        // فیلتر بر اساس وضعیت
        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // فیلتر بر اساس سطح تریاز
        if (isset($filters['triage_level']) && $filters['triage_level'] !== 'all') {
            $query->where('triage_level', $filters['triage_level']);
        }

        // فیلتر بر اساس کلینیک
        if (isset($filters['clinic_id'])) {
            $query->byClinic($filters['clinic_id']);
        }

        // فیلتر بر اساس تاریخ
        if (isset($filters['from_date'])) {
            $query->whereDate('arrival_time', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->whereDate('arrival_time', '<=', $filters['to_date']);
        }

        // جستجو
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($q2) use ($search) {
                    $q2->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('national_code', 'like', "%{$search}%");
                })
                    ->orWhereHas('patient.user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    })
                    ->orWhere('ambulance_number', 'like', "%{$search}%")
                    ->orWhere('chief_complaint', 'like', "%{$search}%");
            });
        }

        return $query->orderByRaw("
            CASE
                WHEN triage_level = 'red' THEN 1
                WHEN triage_level = 'yellow' THEN 2
                WHEN triage_level = 'green' THEN 3
                WHEN triage_level = 'blue' THEN 4
                ELSE 5
            END
        ")->orderBy('arrival_time', 'asc')->paginate($perPage);
    }

    /**
     * نمایش یک درخواست اورژانس
     */
    public function getEmergencyRequest(int $id): EmergencyPatient
    {
        return EmergencyPatient::with([
            'patient',
            'patient.user',
            'clinic',
            'province',
            'city',
            'doctor',
            'doctor.user',
            'admission',
        ])->findOrFail($id);
    }

    /**
     * تریاز بیمار (ادمین)
     */
    public function triagePatient(int $id, string $level, array $vitalSigns = null): EmergencyPatient
    {
        $emergency = EmergencyPatient::findOrFail($id);
        $emergency->setTriage($level, $vitalSigns);

        Log::info('🩺 Patient triaged', [
            'emergency_id' => $id,
            'triage_level' => $level,
        ]);

        return $emergency->fresh();
    }

    /**
     * شروع معاینه (ادمین)
     */
    public function startExam(int $id): EmergencyPatient
    {
        $emergency = EmergencyPatient::findOrFail($id);
        $emergency->startExam();
        return $emergency->fresh();
    }

    /**
     * شروع درمان (ادمین)
     */
    public function startTreatment(int $id): EmergencyPatient
    {
        $emergency = EmergencyPatient::findOrFail($id);
        $emergency->startTreatment();
        return $emergency->fresh();
    }

    /**
     * اعزام آمبولانس (ادمین)
     */
    public function dispatchAmbulance(int $id, string $ambulanceNumber, string $team = null): EmergencyPatient
    {
        $emergency = EmergencyPatient::findOrFail($id);
        $emergency->dispatchAmbulance($ambulanceNumber, $team);

        // ارسال نوتیفیکیشن به بیمار
        $this->sendAmbulanceDispatchedNotification($emergency);

        Log::info('🚑 Ambulance dispatched', [
            'emergency_id' => $id,
            'ambulance_number' => $ambulanceNumber,
        ]);

        return $emergency->fresh();
    }

    /**
     * ثبت رسیدن آمبولانس (ادمین)
     */
    public function markAmbulanceArrived(int $id): EmergencyPatient
    {
        $emergency = EmergencyPatient::findOrFail($id);
        $emergency->markAsArrived();
        return $emergency->fresh();
    }

    /**
     * تکمیل فرآیند اورژانس (ادمین)
     */
    public function completeEmergency(int $id): EmergencyPatient
    {
        $emergency = EmergencyPatient::findOrFail($id);
        $emergency->markAsCompleted();
        return $emergency->fresh();
    }

    /**
     * بستری بیمار (ادمین)
     */
    public function admitPatient(int $id, int $admissionId = null): EmergencyPatient
    {
        $emergency = EmergencyPatient::findOrFail($id);
        $emergency->admit();

        if ($admissionId) {
            $emergency->update(['admission_id' => $admissionId]);
        }

        return $emergency->fresh();
    }

    /**
     * ترخیص بیمار (ادمین)
     */
    public function dischargePatient(int $id): EmergencyPatient
    {
        $emergency = EmergencyPatient::findOrFail($id);
        $emergency->discharge();
        return $emergency->fresh();
    }

    /**
     * انتقال بیمار به بیمارستان دیگر (ادمین)
     */
    public function transferPatient(int $id, string $toHospital): EmergencyPatient
    {
        $emergency = EmergencyPatient::findOrFail($id);
        $emergency->transfer($toHospital);
        return $emergency->fresh();
    }

    /**
     * دریافت آمار اورژانس
     */
    public function getStats(array $filters = []): array
    {
        $query = EmergencyPatient::query();

        if (isset($filters['clinic_id'])) {
            $query->byClinic($filters['clinic_id']);
        }

        return [
            'total' => $query->count(),
            'waiting' => (clone $query)->waiting()->count(),
            'active' => (clone $query)->active()->count(),
            'today' => (clone $query)->today()->count(),
            'by_triage' => [
                'red' => (clone $query)->where('triage_level', 'red')->count(),
                'yellow' => (clone $query)->where('triage_level', 'yellow')->count(),
                'green' => (clone $query)->where('triage_level', 'green')->count(),
                'blue' => (clone $query)->where('triage_level', 'blue')->count(),
            ],
            'by_status' => [
                'waiting' => (clone $query)->where('status', 'waiting')->count(),
                'in_triage' => (clone $query)->where('status', 'in_triage')->count(),
                'in_exam' => (clone $query)->where('status', 'in_exam')->count(),
                'in_treatment' => (clone $query)->where('status', 'in_treatment')->count(),
                'admitted' => (clone $query)->where('status', 'admitted')->count(),
                'discharged' => (clone $query)->where('status', 'discharged')->count(),
                'transferred' => (clone $query)->where('status', 'transferred')->count(),
            ],
            'avg_response_time' => $this->calculateAvgResponseTime($filters),
        ];
    }

    /**
     * محاسبه میانگین زمان پاسخگویی
     */
    private function calculateAvgResponseTime(array $filters): ?float
    {
        $query = EmergencyPatient::whereNotNull('dispatched_at')
            ->whereNotNull('arrival_time');

        if (isset($filters['clinic_id'])) {
            $query->byClinic($filters['clinic_id']);
        }

        $avg = $query->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, arrival_time, dispatched_at)) as avg_time')
            ->first();

        return $avg ? round($avg->avg_time, 1) : null;
    }

    /**
     * پیدا کردن یا ایجاد بیمار
     */
    private function getOrCreatePatient(array $data): Patient
    {
        // اگر patient_id وجود دارد
        if (isset($data['patient_id'])) {
            return Patient::findOrFail($data['patient_id']);
        }

        // جستجو با کدملی
        if (isset($data['national_code'])) {
            $patient = Patient::where('national_code', $data['national_code'])->first();
            if ($patient) return $patient;
        }

        // جستجو با موبایل
        if (isset($data['mobile'])) {
            $user = \App\Models\User::where('mobile', $data['mobile'])->first();
            if ($user) {
                $patient = Patient::where('user_id', $user->id)->first();
                if ($patient) return $patient;
            }
        }

        // ایجاد کاربر و بیمار جدید
        $user = \App\Models\User::create([
            'name' => $data['patient_name'] ?? 'بیمار اورژانس',
            'mobile' => $data['mobile'] ?? null,
            'is_active' => true,
        ]);

        return Patient::create([
            'user_id' => $user->id,
            'full_name' => $data['patient_name'] ?? 'بیمار اورژانس',
            'national_code' => $data['national_code'] ?? null,
            'phone' => $data['phone'] ?? $data['mobile'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * پیدا کردن نزدیک‌ترین کلینیک
     */
    private function findNearestClinic($latitude, $longitude): ?Clinic
    {
        if (!$latitude || !$longitude) {
            return Clinic::active()->first();
        }

        return Clinic::active()
            ->selectRaw("*,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance",
                [$latitude, $longitude, $latitude]
            )
            ->orderBy('distance')
            ->first();
    }

    /**
     * ارسال نوتیفیکیشن اورژانس به ادمین
     */
    private function sendEmergencyNotification(EmergencyPatient $emergency): void
    {
        try {
            $admins = \App\Models\User::role('admin')->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'emergency',
                    'title' => '🚨 درخواست اورژانس جدید',
                    'body' => "درخواست اورژانس جدید از بیمار {$emergency->patient->full_name}",
                    'data' => ['emergency_id' => $emergency->id],
                    'priority' => 'urgent',
                    'sent_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Send emergency notification error: ' . $e->getMessage());
        }
    }

    /**
     * ارسال نوتیفیکیشن اعزام آمبولانس به بیمار
     */
    private function sendAmbulanceDispatchedNotification(EmergencyPatient $emergency): void
    {
        try {
            \App\Models\Notification::create([
                'user_id' => $emergency->patient->user_id,
                'type' => 'emergency',
                'title' => '🚑 آمبولانس اعزام شد',
                'body' => "آمبولانس شماره {$emergency->ambulance_number} به سمت شما حرکت کرد.",
                'data' => ['emergency_id' => $emergency->id],
                'priority' => 'high',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Send ambulance notification error: ' . $e->getMessage());
        }
    }
}
