<?php
// app/Http/Controllers/Admin/AppointmentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    use ApiResponse;

    /**
     * لیست نوبت‌ها (برای ادمین)
     */
    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor', 'doctor.user', 'patient.user']);

        // فیلتر بر اساس وضعیت
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // فیلتر بر اساس پزشک
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // فیلتر بر اساس بیمار
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // فیلتر بر اساس تاریخ
        if ($request->has('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        // جستجو
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('doctor', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $appointments = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($appointments);
    }

    /**
     * ایجاد نوبت جدید (توسط ادمین)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'fee' => 'nullable|numeric|min:0',
            'type' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $appointment = new Appointment();
            $data['code'] = $appointment->generateCode();
            $data['status'] = Appointment::STATUS_PENDING;

            $appointment = Appointment::create($data);

            return $this->success(
                $appointment->load(['patient', 'doctor']),
                'نوبت با موفقیت ایجاد شد',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش نوبت
     */
    public function show($id)
    {
        try {
            $appointment = Appointment::with(['patient', 'doctor', 'doctor.user', 'patient.user'])
                ->findOrFail($id);
            return $this->success($appointment);
        } catch (\Exception $e) {
            return $this->error('نوبت یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی نوبت
     */
    public function update(Request $request, $id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'sometimes|exists:patients,id',
            'doctor_id' => 'sometimes|exists:doctors,id',
            'date' => 'sometimes|date',
            'start_time' => 'sometimes',
            'end_time' => 'nullable',
            'fee' => 'nullable|numeric|min:0',
            'type' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,confirmed,arrived,in_progress,completed,cancelled,no_show',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $appointment->update($request->all());
            return $this->success(
                $appointment->fresh()->load(['patient', 'doctor']),
                'نوبت با موفقیت به‌روزرسانی شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف نوبت
     */
    public function destroy($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            $appointment->delete();
            return $this->success(null, 'نوبت با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت نوبت
     */
    public function changeStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,arrived,in_progress,completed,cancelled,no_show',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $appointment = Appointment::findOrFail($id);
            $appointment->update(['status' => $request->status]);
            return $this->success($appointment->fresh(), 'وضعیت نوبت با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید نوبت
     */
    public function confirm($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            $appointment->update(['status' => Appointment::STATUS_CONFIRMED]);
            return $this->success($appointment->fresh(), 'نوبت با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لغو نوبت
     */
    public function cancel($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            $appointment->update(['status' => Appointment::STATUS_CANCELLED]);
            return $this->success($appointment->fresh(), 'نوبت با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * شروع نوبت
     */
    public function start($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            $appointment->update(['status' => Appointment::STATUS_IN_PROGRESS]);
            return $this->success($appointment->fresh(), 'نوبت شروع شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تکمیل نوبت
     */
    public function complete($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            $appointment->update(['status' => Appointment::STATUS_COMPLETED]);
            return $this->success($appointment->fresh(), 'نوبت با موفقیت تکمیل شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار نوبت‌ها
     */
    public function stats()
    {
        $total = Appointment::count();
        $pending = Appointment::where('status', Appointment::STATUS_PENDING)->count();
        $confirmed = Appointment::where('status', Appointment::STATUS_CONFIRMED)->count();
        $completed = Appointment::where('status', Appointment::STATUS_COMPLETED)->count();
        $cancelled = Appointment::where('status', Appointment::STATUS_CANCELLED)->count();
        $inProgress = Appointment::where('status', Appointment::STATUS_IN_PROGRESS)->count();

        return $this->success([
            'total' => $total,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'in_progress' => $inProgress,
        ]);
    }

    /**
     * دریافت زمان‌های موجود برای پزشک (ادمین)
     */
    public function getAvailableSlots(Request $request, $doctorId)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $doctor = Doctor::findOrFail($doctorId);

        $bookedSlots = Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $request->date)
            ->whereIn('status', [
                Appointment::STATUS_PENDING,
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_ARRIVED,
                Appointment::STATUS_IN_PROGRESS
            ])
            ->pluck('start_time')
            ->map(function ($time) {
                return (int) substr($time, 0, 2);
            })
            ->toArray();

        $workingHours = [9, 10, 11, 12, 13, 14, 15, 16, 17];
        $availableSlots = array_diff($workingHours, $bookedSlots);

        return $this->success(array_values($availableSlots));
    }
}
