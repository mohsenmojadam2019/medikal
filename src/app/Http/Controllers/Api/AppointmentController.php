<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\Appointment\AppointmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    use ApiResponse;

    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * دریافت زمان‌های آزاد پزشک (فقط زمان‌های خالی)
     */
    public function availableSlots(Request $request, $doctorId)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        $doctor = Doctor::find($doctorId);
        if (!$doctor) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $result = $this->appointmentService->getAvailableSlots($doctor, $request->date);

        if (!$result['available']) {
            return $this->error($result['message'], 400);
        }

        // فقط زمان‌های خالی رو برگردون (is_available = true)
        $availableSlots = array_filter($result['slots'], function($slot) {
            return $slot['is_available'] === true;
        });

        $result['slots'] = array_values($availableSlots);
        $result['available_slots'] = count($availableSlots);

        return $this->success($result);
    }

    /**
     * رزرو نوبت جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i:s',
            'type' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $appointment = $this->appointmentService->bookAppointment($request->all());
            return $this->success($appointment, 'نوبت با موفقیت رزرو شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش نوبت
     */
    public function show($id)
    {
        $appointment = Appointment::with([
            'patient.user',
            'doctor.user',
            'doctor.specialty'
        ])->find($id);

        if (!$appointment) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $user = auth()->user();
        if (!$user->isAdmin() &&
            $appointment->patient->user_id != $user->id &&
            $appointment->doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این نوبت ندارید', 403);
        }

        return $this->success($appointment);
    }

    /**
     * به‌روزرسانی نوبت
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $user = auth()->user();
        if (!$user->isAdmin() && $appointment->doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این نوبت ندارید', 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date|after_or_equal:today',
            'start_time' => 'sometimes|date_format:H:i:s',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $appointment->update($request->only(['date', 'start_time', 'notes']));
            return $this->success($appointment->fresh(), 'نوبت با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید نوبت (توسط پزشک)
     */
    public function confirm($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $user = auth()->user();
        if (!$user->isAdmin() && $appointment->doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این نوبت ندارید', 403);
        }

        try {
            $appointment = $this->appointmentService->confirmAppointment($appointment);
            return $this->success($appointment, 'نوبت با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لغو نوبت
     */
    public function cancel(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $user = auth()->user();
        if (!$user->isAdmin() &&
            $appointment->patient->user_id != $user->id &&
            $appointment->doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این نوبت ندارید', 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $appointment = $this->appointmentService->cancelAppointment(
                $appointment,
                $request->reason
            );
            return $this->success($appointment, 'نوبت با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر زمان نوبت
     */
    public function reschedule(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $user = auth()->user();
        if (!$user->isAdmin() &&
            $appointment->patient->user_id != $user->id &&
            $appointment->doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این نوبت ندارید', 403);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i:s',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $appointment = $this->appointmentService->rescheduleAppointment(
                $appointment,
                $request->only(['date', 'start_time'])
            );
            return $this->success($appointment, 'زمان نوبت با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * شروع ویزیت (حضور بیمار)
     */
    public function start($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $user = auth()->user();
        if (!$user->isAdmin() && $appointment->doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این نوبت ندارید', 403);
        }

        try {
            $appointment = $this->appointmentService->startAppointment($appointment);
            return $this->success($appointment, 'حضور بیمار ثبت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * پایان ویزیت
     */
    public function complete($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $user = auth()->user();
        if (!$user->isAdmin() && $appointment->doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این نوبت ندارید', 403);
        }

        try {
            $appointment = $this->appointmentService->completeAppointment($appointment);
            return $this->success($appointment, 'ویزیت با موفقیت پایان یافت');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * بیمار حاضر نشده
     */
    public function noShow($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $user = auth()->user();
        if (!$user->isAdmin() && $appointment->doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این نوبت ندارید', 403);
        }

        try {
            $appointment = $this->appointmentService->markNoShow($appointment);
            return $this->success($appointment, 'بیمار به عنوان حاضر نشده ثبت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تاریخچه نوبت‌های بیمار (کاربر جاری)
     */
    public function myAppointments(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $appointments = $this->appointmentService->patientAppointments(
            $patient,
            $request->all(),
            $request->get('per_page', 15)
        );

        return $this->success($appointments);
    }

    /**
     * نوبت‌های پزشک جاری
     */
    public function myDoctorAppointments(Request $request)
    {
        $user = auth()->user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $appointments = $this->appointmentService->doctorAppointments(
            $doctor,
            $request->all(),
            $request->get('per_page', 15)
        );

        return $this->success($appointments);
    }

    /**
     * آمار نوبت‌های پزشک
     */
    public function myDoctorStats(Request $request)
    {
        $user = auth()->user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $stats = [
            'today' => Appointment::today()->byDoctor($doctor->id)->count(),
            'upcoming' => Appointment::upcoming()->byDoctor($doctor->id)->count(),
            'pending' => Appointment::byStatus(Appointment::STATUS_PENDING)->byDoctor($doctor->id)->count(),
            'confirmed' => Appointment::byStatus(Appointment::STATUS_CONFIRMED)->byDoctor($doctor->id)->count(),
            'completed' => Appointment::byStatus(Appointment::STATUS_COMPLETED)->byDoctor($doctor->id)->count(),
            'total' => Appointment::byDoctor($doctor->id)->count(),
        ];

        return $this->success($stats);
    }

    /**
     * آمار نوبت‌های بیمار
     */
    public function myPatientStats(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $stats = [
            'upcoming' => Appointment::upcoming()->byPatient($patient->id)->count(),
            'pending' => Appointment::byStatus(Appointment::STATUS_PENDING)->byPatient($patient->id)->count(),
            'confirmed' => Appointment::byStatus(Appointment::STATUS_CONFIRMED)->byPatient($patient->id)->count(),
            'completed' => Appointment::byStatus(Appointment::STATUS_COMPLETED)->byPatient($patient->id)->count(),
            'total' => Appointment::byPatient($patient->id)->count(),
        ];

        return $this->success($stats);
    }
}
