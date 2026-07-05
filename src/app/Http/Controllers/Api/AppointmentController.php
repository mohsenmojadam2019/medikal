<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Invoice;
use App\Enums\InvoiceStatusEnum;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    use ApiResponse;

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
        
        $startHour = 9;
        $endHour = 17;
        $slotDuration = 30;

        $allSlots = [];
        $currentTime = Carbon::parse($date . ' ' . sprintf('%02d:00:00', $startHour));
        $endTime = Carbon::parse($date . ' ' . sprintf('%02d:00:00', $endHour));

        while ($currentTime < $endTime) {
            $slotEnd = clone $currentTime;
            $slotEnd->addMinutes($slotDuration);
            
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

        $bookedAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->whereIn('status', ['pending', 'confirmed', 'arrived', 'in_progress'])
            ->get();

        $bookedTimes = $bookedAppointments->map(function ($appointment) {
            return Carbon::parse($appointment->start_time)->format('H:i');
        })->toArray();

        foreach ($allSlots as &$slot) {
            if (in_array($slot['time'], $bookedTimes)) {
                $slot['is_available'] = false;
                $slot['is_booked'] = true;
            }
        }

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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ], [
            'doctor_id.required' => 'شناسه پزشک الزامی است',
            'doctor_id.exists' => 'پزشک مورد نظر یافت نشد',
            'date.required' => 'تاریخ الزامی است',
            'date.date' => 'فرمت تاریخ نامعتبر است',
            'date.after_or_equal' => 'تاریخ باید امروز یا بعد از آن باشد',
            'start_time.required' => 'ساعت شروع الزامی است',
            'start_time.date_format' => 'فرمت ساعت باید HH:MM باشد',
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

        $startTime = Carbon::parse($request->date . ' ' . $request->start_time);
        $endTime = clone $startTime;
        $endTime->addMinutes(30);

        // ✅ بررسی اینکه آیا کاربر قبلاً برای این زمان نوبت گرفته است
        $userExistingAppointment = Appointment::where('patient_id', $patient->id)
            ->where('doctor_id', $request->doctor_id)
            ->whereDate('date', $request->date)
            ->whereTime('start_time', $startTime->format('H:i:s'))
            ->whereIn('status', ['pending', 'confirmed', 'arrived', 'in_progress'])
            ->first();

        if ($userExistingAppointment) {
            return $this->error('شما قبلاً برای این زمان نوبت گرفته‌اید', 409, [
                'existing_appointment' => [
                    'id' => $userExistingAppointment->id,
                    'start_time' => $userExistingAppointment->start_time,
                    'status' => $userExistingAppointment->status,
                ]
            ]);
        }

        // ✅ بررسی تداخل با سایر کاربران
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->whereDate('date', $request->date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime) {
                    $q->whereTime('start_time', '=', $startTime->format('H:i:s'));
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->whereTime('start_time', '>=', $startTime->format('H:i:s'))
                      ->whereTime('start_time', '<', $endTime->format('H:i:s'));
                })->orWhere(function ($q) use ($startTime) {
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

        $doctor = Doctor::find($request->doctor_id);
        $fee = $doctor->consultation_fee ?? 0;
        
        $invoice = new Invoice();
        $invoice->tenant_id = session('tenant_id', 1);
        $invoice->patient_id = $patient->id;
        $invoice->appointment_id = $appointment->id;
        $invoice->invoice_number = $invoice->generateNumber();
        $invoice->amount = $fee;
        $invoice->tax = 0;
        $invoice->discount = 0;
        $invoice->total_amount = $fee;
        $invoice->status = InvoiceStatusEnum::ISSUED;
        $invoice->description = 'هزینه ویزیت دکتر ' . ($doctor->full_name ?? $doctor->name ?? '');
        $invoice->due_date = Carbon::parse($request->date)->addDays(7);
        $invoice->items = [
            [
                'description' => 'ویزیت پزشک',
                'quantity' => 1,
                'unit_price' => $fee,
                'total' => $fee,
            ]
        ];
        $invoice->save();

        return $this->success(
            $appointment->load(['patient', 'doctor']),
            'نوبت با موفقیت رزرو شد',
            201
        );
    }

    public function myAppointments(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->error('لطفاً وارد شوید', 401);
        }

        $patient = Patient::where('user_id', $user->id)->first();
        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $query = Appointment::where('patient_id', $patient->id)
            ->with(['doctor', 'doctor.user', 'doctor.specialty'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $appointments = $query->paginate($request->get('per_page', 15));

        return $this->success($appointments);
    }

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

            $invoice = Invoice::where('appointment_id', $appointment->id)->first();
            if ($invoice && $invoice->status === InvoiceStatusEnum::ISSUED) {
                $invoice->markAsCancelled();
            }

            return $this->success($appointment, 'نوبت با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function confirm($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            
            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();
            
            if ($appointment->patient_id !== $patient->id && !$user->isAdmin()) {
                return $this->error('شما دسترسی به این نوبت ندارید', 403);
            }

            if ($appointment->status !== 'pending') {
                return $this->error('این نوبت قابل تایید نیست', 400);
            }

            $appointment->status = 'confirmed';
            $appointment->save();

            return $this->success($appointment, 'نوبت با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    private function generateAppointmentCode()
    {
        $prefix = 'APT';
        $random = strtoupper(substr(uniqid(), -6));
        return $prefix . '-' . date('Ymd') . '-' . $random;
    }
}
