<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\WebhookLog;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookController extends Controller
{
    use ApiResponse;

    /**
     * دریافت نوبت از ویپ کلینیک
     */
    public function appointment(Request $request)
    {
        // 1. لاگ درخواست
        $this->logRequest($request, 'appointment_created');

        // 2. چک فعال بودن Webhook
        $clinic = Clinic::first();
        if (!$clinic || !$clinic->webhook_enabled) {
            $this->logResponse(null, 403, 'Webhook غیرفعال است');
            return $this->error('سیستم قادر به دریافت نوبت نیست', 403);
        }

        // 3. اعتبارسنجی داده‌ها
        $validated = $request->validate([
            // اطلاعات بیمار (اجباری)
            'patient_national_code' => 'required|string|size:10',
            'patient_name' => 'required|string|max:255',
            'patient_mobile' => 'required|regex:/^09[0-9]{9}$/',
            'patient_email' => 'nullable|email',
            
            // اطلاعات پزشک
            'doctor_code' => 'nullable|string', // کد پزشک در ویپ
            'doctor_name' => 'nullable|string|max:255', // یا نام پزشک
            
            // اطلاعات نوبت
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'appointment_type' => 'nullable|in:in_person,online,home_visit',
            'notes' => 'nullable|string|max:500',
            
            // اطلاعات اضافی
            'referral_code' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        try {
            // 4. پیدا کردن یا ایجاد بیمار
            $patient = $this->findOrCreatePatient($validated);
            if (!$patient) {
                $this->logResponse(null, 400, 'خطا در ایجاد بیمار');
                return $this->error('خطا در ثبت اطلاعات بیمار', 400);
            }

            // 5. پیدا کردن پزشک
            $doctor = $this->findDoctor($validated);
            if (!$doctor) {
                $this->logResponse(null, 404, 'پزشک یافت نشد');
                return $this->error('پزشک مورد نظر یافت نشد', 404);
            }

            // 6. چک کردن تداخل زمانی
            $date = Carbon::parse($validated['appointment_date']);
            $startTime = Carbon::parse($validated['appointment_time']);

            $existingAppointment = Appointment::where('doctor_id', $doctor->id)
                ->whereDate('date', $date)
                ->whereTime('start_time', $startTime->format('H:i:s'))
                ->whereIn('status', ['pending', 'confirmed', 'arrived', 'in_progress'])
                ->first();

            if ($existingAppointment) {
                $this->logResponse(null, 409, 'تداخل زمانی');
                return $this->error('این زمان قبلاً رزرو شده است', 409);
            }

            // 7. ایجاد نوبت
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'date' => $date->format('Y-m-d'),
                'start_time' => $startTime->format('H:i:s'),
                'duration' => $doctor->visit_duration ?? 30,
                'status' => Appointment::STATUS_PENDING,
                'type' => $validated['appointment_type'] ?? 'in_person',
                'fee' => $doctor->consultation_fee ?? 0,
                'final_price' => $doctor->consultation_fee ?? 0,
                'payment_status' => Appointment::PAYMENT_PENDING,
                'notes' => $validated['notes'] ?? null,
                'metadata' => array_merge([
                    'source' => 'webhook_isp',
                    'doctor_code' => $validated['doctor_code'] ?? null,
                    'referral_code' => $validated['referral_code'] ?? null,
                ], $validated['metadata'] ?? []),
            ]);

            // محاسبه زمان پایان
            $endTime = Carbon::parse($appointment->start_time)
                ->addMinutes($appointment->duration)
                ->format('H:i:s');
            $appointment->update(['end_time' => $endTime]);

            // 8. لاگ موفقیت
            $this->logResponse([
                'appointment_id' => $appointment->id,
                'code' => $appointment->code,
                'patient' => $patient->full_name,
                'doctor' => $doctor->full_name,
            ], 201, 'نوبت با موفقیت ثبت شد');

            // 9. پاسخ به ویپ
            return $this->success([
                'appointment_id' => $appointment->id,
                'code' => $appointment->code,
                'patient_name' => $patient->full_name,
                'doctor_name' => $doctor->full_name,
                'date' => $appointment->date->format('Y/m/d'),
                'time' => $appointment->start_time->format('H:i'),
                'status' => $appointment->status,
                'message' => 'نوبت با موفقیت ثبت شد',
            ], 'نوبت با موفقیت ثبت شد', 201);

        } catch (\Exception $e) {
            $this->logResponse(null, 500, $e->getMessage());
            Log::error('Webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('خطا در ثبت نوبت: ' . $e->getMessage(), 500);
        }
    }

    /**
     * پیدا کردن یا ایجاد بیمار
     */
    protected function findOrCreatePatient(array $data)
    {
        // جستجو با کدملی
        $patient = Patient::where('national_code', $data['patient_national_code'])->first();
        if ($patient) {
            return $patient;
        }

        // جستجو با موبایل
        $user = User::where('mobile', $data['patient_mobile'])->first();
        if ($user) {
            $patient = Patient::where('user_id', $user->id)->first();
            if ($patient) {
                return $patient;
            }
        }

        // ایجاد کاربر جدید
        $user = User::create([
            'name' => $data['patient_name'],
            'mobile' => $data['patient_mobile'],
            'email' => $data['patient_email'] ?? null,
            'is_active' => true,
        ]);
        $user->assignRole('patient');

        // ایجاد بیمار
        return Patient::create([
            'user_id' => $user->id,
            'national_code' => $data['patient_national_code'],
            'phone' => $data['patient_mobile'],
            'is_active' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * پیدا کردن پزشک
     */
    protected function findDoctor(array $data)
    {
        // 1. با کد پزشک در ویپ
        if (isset($data['doctor_code']) && !empty($data['doctor_code'])) {
            $doctor = Doctor::where('metadata->doctor_code', $data['doctor_code'])->first();
            if ($doctor) {
                return $doctor;
            }
        }

        // 2. با نام پزشک (جستجوی دقیق)
        if (isset($data['doctor_name']) && !empty($data['doctor_name'])) {
            $doctor = Doctor::whereHas('user', function ($query) use ($data) {
                $query->where('name', 'LIKE', '%' . $data['doctor_name'] . '%');
            })->first();
            if ($doctor) {
                return $doctor;
            }
        }

        return null;
    }

    /**
     * لاگ درخواست
     */
    protected function logRequest(Request $request, string $eventType): void
    {
        WebhookLog::create([
            'provider' => 'isp',
            'event_type' => $eventType,
            'payload' => $request->all(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * لاگ پاسخ
     */
    protected function logResponse($data, int $statusCode, ?string $error = null): void
    {
        WebhookLog::latest()->first()?->update([
            'response' => $data,
            'status_code' => $statusCode,
            'error_message' => $error,
        ]);
    }

    /**
     * دریافت وضعیت Webhook
     */
    public function status()
    {
        $clinic = Clinic::first();
        return $this->success([
            'enabled' => $clinic?->webhook_enabled ?? false,
            'has_secret' => !empty($clinic?->webhook_secret),
            'provider' => 'isp',
        ]);
    }

    /**
     * فعال/غیرفعال کردن Webhook (ادمین)
     */
    public function toggle(Request $request)
    {
        $clinic = Clinic::first();
        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        $request->validate([
            'enabled' => 'required|boolean',
            'secret' => 'nullable|string|min:16',
        ]);

        $clinic->update([
            'webhook_enabled' => $request->enabled,
            'webhook_secret' => $request->secret ?? $clinic->webhook_secret,
        ]);

        return $this->success([
            'enabled' => $clinic->webhook_enabled,
            'has_secret' => !empty($clinic->webhook_secret),
        ], 'وضعیت Webhook تغییر کرد');
    }

    /**
     * دریافت لاگ‌های Webhook
     */
    public function logs(Request $request)
    {
        $logs = WebhookLog::provider('isp')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success($logs);
    }
}
