<?php
// app/Http/Controllers/Admin/DoctorController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Doctor\DoctorService;
use App\Http\Requests\Admin\StoreDoctorRequest;
use App\Http\Requests\Admin\UpdateDoctorRequest;
use App\Traits\ApiResponse;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    use ApiResponse;

    protected DoctorService $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }

    /**
     * لیست پزشکان (ادمین)
     */
    public function index(Request $request)
    {
        $query = Doctor::with([
            'user',
            'specialty',
            'clinic',
            'province',
            'city'
        ]);

        // فیلتر بر اساس کلینیک
        if ($request->has('clinic_id') && $request->clinic_id) {
            $query->where('clinic_id', $request->clinic_id);
        }

        // فیلتر بر اساس استان
        if ($request->has('province_id') && $request->province_id) {
            $query->where('province_id', $request->province_id);
        }

        // فیلتر بر اساس شهر
        if ($request->has('city_id') && $request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        // فیلتر بر اساس تخصص
        if ($request->has('specialty_id') && $request->specialty_id) {
            $query->where('specialty_id', $request->specialty_id);
        }

        // فیلتر بر اساس وضعیت
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('is_available')) {
            $query->where('is_available', $request->is_available);
        }

        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->is_verified);
        }

        // فیلتر بر اساس هزینه
        if ($request->has('fee_type') && $request->fee_type !== 'all') {
            $query->where('appointment_fee_type', $request->fee_type);
        }

        // جستجو
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('clinic_name', 'LIKE', "%{$search}%")
                    ->orWhere('license_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('mobile', 'LIKE', "%{$search}%");
                    });
            });
        }

        $doctors = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // اضافه کردن اطلاعات هزینه
        $doctors->getCollection()->transform(function ($doctor) {
            $doctor->fee_label = $doctor->appointment_fee_label;
            $doctor->fee_value = $doctor->getFeeForAppointment();
            $doctor->is_free = $doctor->isFreeAppointment();
            return $doctor;
        });

        return $this->success($doctors);
    }

    /**
     * ایجاد پزشک جدید (ادمین)
     */
    public function store(StoreDoctorRequest $request)
    {
        try {
            $doctor = $this->doctorService->create($request->validated());
            return $this->success(
                $doctor->load(['user', 'specialty', 'clinic', 'province', 'city']),
                'پزشک با موفقیت ایجاد شد',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش پزشک (ادمین)
     */
    public function show($id)
    {
        try {
            $doctor = Doctor::with([
                'user',
                'specialty',
                'clinic',
                'province',
                'city',
                'primaryAddress',
                'schedules'
            ])->findOrFail($id);

            $doctor->fee_label = $doctor->appointment_fee_label;
            $doctor->fee_value = $doctor->getFeeForAppointment();
            $doctor->is_free = $doctor->isFreeAppointment();

            return $this->success($doctor);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی پزشک (ادمین)
     */
    public function update(UpdateDoctorRequest $request, $id)
    {
        try {
            $doctor = Doctor::with(['user', 'specialty', 'clinic', 'province', 'city'])->findOrFail($id);
            $doctor = $this->doctorService->update($doctor, $request->validated());
            return $this->success($doctor->load(['user', 'specialty', 'clinic', 'province', 'city']), 'پزشک با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف پزشک (ادمین)
     */
    public function destroy($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $this->doctorService->delete($doctor);
            return $this->success(null, 'پزشک با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت پزشک (ادمین)
     */
    public function toggleAvailability($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor = $this->doctorService->toggleAvailability($doctor);
            return $this->success($doctor, 'وضعیت پزشک با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید پزشک (ادمین)
     */
    public function verify($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor = $this->doctorService->verify($doctor);
            return $this->success($doctor, 'پزشک با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تنظیم هزینه نوبت پزشک (ادمین)
     */
    public function setAppointmentFee(Request $request, $id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'fee_type' => 'required|in:free,paid',
            'fee_amount' => 'required_if:fee_type,paid|nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $doctor->appointment_fee_type = $request->fee_type;

            if ($request->fee_type === 'paid') {
                $doctor->appointment_fee_amount = $request->fee_amount;
            } else {
                $doctor->appointment_fee_amount = null;
            }

            $doctor->save();

            return $this->success([
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->full_name,
                'fee_type' => $doctor->appointment_fee_type,
                'fee_amount' => $doctor->appointment_fee_amount,
                'fee_label' => $doctor->appointment_fee_label,
                'fee_value' => $doctor->getFeeForAppointment(),
                'is_free' => $doctor->isFreeAppointment(),
            ], 'هزینه نوبت با موفقیت تنظیم شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت اطلاعات هزینه نوبت پزشک (ادمین)
     */
    public function getAppointmentFee($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);

            return $this->success([
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->full_name,
                'fee_type' => $doctor->appointment_fee_type,
                'fee_amount' => $doctor->appointment_fee_amount,
                'fee_label' => $doctor->appointment_fee_label,
                'is_free' => $doctor->isFreeAppointment(),
                'fee_value' => $doctor->getFeeForAppointment(),
                'is_editable' => $doctor->is_fee_editable_by_admin,
            ]);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * تنظیم پزشک به صورت رایگان (میانبر - ادمین)
     */
    public function setFree($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->appointment_fee_type = 'free';
            $doctor->appointment_fee_amount = null;
            $doctor->save();

            return $this->success([
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->full_name,
                'fee_type' => 'free',
                'fee_label' => 'رایگان',
                'fee_value' => 0,
                'is_free' => true,
            ], 'هزینه نوبت به صورت رایگان تنظیم شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تنظیم پزشک به صورت پولی (میانبر - ادمین)
     */
    public function setPaid(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->appointment_fee_type = 'paid';
            $doctor->appointment_fee_amount = $request->amount;
            $doctor->save();

            return $this->success([
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->full_name,
                'fee_type' => 'paid',
                'fee_amount' => $request->amount,
                'fee_label' => number_format($request->amount) . ' تومان',
                'fee_value' => $request->amount,
                'is_free' => false,
            ], 'هزینه نوبت به صورت پولی تنظیم شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
