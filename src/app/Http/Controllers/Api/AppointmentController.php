<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    use ApiResponse;

    /**
     * دریافت زمان‌های خالی پزشک
     */
    public function availableSlots(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $doctor = Doctor::find($doctorId);
        if (!$doctor) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $date = $request->date;
        
        // ساعات کاری پیش‌فرض (از 9 صبح تا 5 عصر)
        $startHour = 9;
        $endHour = 17;
        $slotDuration = 30; // دقیقه

        // تولید تمام اسلات‌های ممکن
        $allSlots = [];
        $currentTime = Carbon::parse($date . ' ' . sprintf('%02d:00:00', $startHour));
        $endTime = Carbon::parse($date . ' ' . sprintf('%02d:00:00', $endHour));

        while ($currentTime < $endTime) {
            $slotEnd = clone $currentTime;
            $slotEnd->addMinutes($slotDuration);
            
            // رد کردن زمان استراحت (12:30 - 14:00)
            $isBreak = false;
            $breakStart = Carbon::parse($date . ' 12:30:00');
            $breakEnd = Carbon::parse($date . ' 14:00:00');
            
            if ($currentTime >= $breakStart && $currentTime < $breakEnd) {
                $isBreak = true;
            }

            if (!$isBreak) {
                $allSlots[] = [
                    'time' => $currentTime->format('H:i'),
                    'start_time' => $currentTime->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'is_available' => true,
                    'is_booked' => false,
                ];
            }

            $currentTime->addMinutes($slotDuration);
        }

        // دریافت نوبت‌های رزرو شده برای این تاریخ
        $bookedAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'confirmed', 'arrived', 'in_progress'])
            ->get();

        // استخراج زمان‌های شروع رزرو شده
        $bookedTimes = $bookedAppointments->map(function ($appointment) {
            return Carbon::parse($appointment->start_time)->format('H:i');
        })->toArray();

        // علامت‌گذاری اسلات‌های رزرو شده
        foreach ($allSlots as &$slot) {
            if (in_array($slot['time'], $bookedTimes)) {
                $slot['is_available'] = false;
                $slot['is_booked'] = true;
            }
        }

        // فیلتر اسلات‌های خالی برای نمایش
        $availableSlots = array_filter($allSlots, function ($slot) {
            return $slot['is_available'] === true;
        });

        return $this->success([
            'available' => count($availableSlots) > 0,
            'date' => $date,
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->full_name ?? $doctor->name ?? 'پزشک',
                'specialty' => $doctor->specialty->name ?? 'عمومی',
                'consultation_fee' => $doctor->consultation_fee ?? 0,
            ],
            'slots' => array_values($allSlots),
            'total_slots' => count($allSlots),
            'available_slots' => count($availableSlots),
        ]);
    }

    /**
     * رزرو نوبت جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $user = auth()->user();
        if (!$user) {
            return $this->error('لطفاً وارد شوید', 401);
        }

        $patient = Patient::where('user_id', $user->id)->first();
        if (!$patient) {
            return $this->error('بیمار یافت نشد. لطفاً ابتدا ثبت نام کنید.', 404);
        }

        // بررسی دقیق تداخل زمانی
        $startTime = Carbon::parse($request->date . ' ' . $request->start_time);
        $endTime = clone $startTime;
        $endTime->addMinutes(30);

        // چک کردن اینکه آیا نوبتی در این زمان وجود دارد
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->whereDate('date', $request->date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // نوبتی که دقیقاً در این زمان شروع می‌شود
                    $q->whereTime('start_time', '=', $startTime->format('H:i:s'));
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // نوبتی که در این بازه زمانی قرار می‌گیرد
                    $q->whereTime('start_time', '>=', $startTime->format('H:i:s'))
                      ->whereTime('start_time', '<', $endTime->format('H:i:s'));
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // نوبتی که این بازه زمانی را پوشش می‌دهد
                    $q->whereTime('start_time', '<=', $startTime->format('H:i:s'))
                      ->whereTime('end_time', '>', $startTime->format('H:i:s'));
                });
            })
            ->whereIn('status', ['pending', 'confirmed', 'arrived', 'in_progress'])
            ->first();

        if ($existingAppointment) {
            return $this->error('این زمان قبلاً توسط شخص دیگری رزرو شده است', 409, [
                'existing_appointment' => [
                    'id' => $existingAppointment->id,
                    'start_time' => $existingAppointment->start_time,
                    'end_time' => $existingAppointment->end_time,
                    'status' => $existingAppointment->status,
                ]
            ]);
        }

        // ایجاد نوبت
        $appointment = new Appointment();
        $appointment->code = $this->generateAppointmentCode();
        $appointment->patient_id = $patient->id;
        $appointment->doctor_id = $request->doctor_id;
        $appointment->date = $request->date;
        $appointment->start_time = $startTime->format('H:i:s');
        $appointment->end_time = $endTime->format('H:i:s');
        $appointment->status = 'pending';
        $appointment->notes = $request->notes;
        $appointment->save();

        // محاسبه هزینه و ایجاد فاکتور
        $doctor = Doctor::find($request->doctor_id);
        $fee = $doctor->consultation_fee ?? 0;
        
        $invoice = new \App\Models\Invoice();
        $invoice->invoice_number = $this->generateInvoiceNumber();
        $invoice->patient_id = $patient->id;
        $invoice->appointment_id = $appointment->id;
        $invoice->amount = $fee;
        $invoice->total_amount = $fee;
        $invoice->status = 'issued';
        $invoice->save();

        return $this->success(
            $appointment->load(['patient', 'doctor']),
            'نوبت با موفقیت رزرو شد',
            201
        );
    }

    /**
     * تولید کد نوبت
     */
    private function generateAppointmentCode()
    {
        $prefix = 'APT';
        $random = strtoupper(substr(uniqid(), -6));
        return $prefix . '-' . date('Ymd') . '-' . $random;
    }

    /**
     * تولید شماره فاکتور
     */
    private function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $count = \App\Models\Invoice::count() + 1;
        return $prefix . '-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * لیست نوبت‌های من (بیمار)
     */
    public function myAppointments(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $query = Appointment::where('patient_id', $patient->id)
            ->with(['doctor', 'doctor.user', 'doctor.specialty']);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $appointments = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($appointments);
    }

    /**
     * نمایش یک نوبت
     */
    public function show($id)
    {
        try {
            $appointment = Appointment::with(['patient', 'doctor', 'doctor.user', 'doctor.specialty'])
                ->findOrFail($id);
            
            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();
            
            if ($appointment->patient_id !== $patient->id && !$user->isAdmin()) {
                return $this->error('شما دسترسی به این نوبت ندارید', 403);
            }
            
            return $this->success($appointment);
        } catch (\Exception $e) {
            return $this->error('نوبت یافت نشد', 404);
        }
    }

    /**
     * لغو نوبت
     */
    public function cancel($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            
            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();
            
            if ($appointment->patient_id !== $patient->id && !$user->isAdmin()) {
                return $this->error('شما دسترسی به این نوبت ندارید', 403);
            }

            if (in_array($appointment->status, ['completed', 'cancelled'])) {
                return $this->error('این نوبت قابل لغو نیست', 400);
            }

            $appointment->status = 'cancelled';
            $appointment->save();

            return $this->success($appointment, 'نوبت با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تکمیل نوبت (توسط پزشک)
     */
    public function complete($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            
            $user = auth()->user();
            $doctor = Doctor::where('user_id', $user->id)->first();
            
            if ($appointment->doctor_id !== $doctor->id && !$user->isAdmin()) {
                return $this->error('شما دسترسی به این نوبت ندارید', 403);
            }

            if ($appointment->status === 'completed') {
                return $this->error('این نوبت قبلاً تکمیل شده است', 400);
            }

            $appointment->status = 'completed';
            $appointment->save();

            return $this->success($appointment, 'نوبت با موفقیت تکمیل شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار نوبت‌های بیمار
     */
    public function myPatientStats()
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $total = Appointment::where('patient_id', $patient->id)->count();
        $pending = Appointment::where('patient_id', $patient->id)->where('status', 'pending')->count();
        $confirmed = Appointment::where('patient_id', $patient->id)->where('status', 'confirmed')->count();
        $completed = Appointment::where('patient_id', $patient->id)->where('status', 'completed')->count();
        $cancelled = Appointment::where('patient_id', $patient->id)->where('status', 'cancelled')->count();
        $upcoming = Appointment::where('patient_id', $patient->id)
            ->whereDate('date', '>=', date('Y-m-d'))
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        return $this->success([
            'total' => $total,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'upcoming' => $upcoming,
        ]);
    }
}
