<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    use ApiResponse;

    /**
     * لیست پزشکان با فیلتر و صفحه‌بندی
     */
    public function index(Request $request)
    {
        $query = Doctor::with(['user', 'specialty', 'clinic']);

        // ✅ جستجو - اصلاح شده
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('license_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('full_name', 'LIKE', "%{$search}%")
                            ->orWhere('name', 'LIKE', "%{$search}%")
                            ->orWhere('mobile', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('clinic', function ($clinicQuery) use ($search) {
                        $clinicQuery->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // فیلتر بر اساس تخصص
        if ($request->filled('specialty_id')) {
            $query->where('specialty_id', $request->specialty_id);
        }

        // فیلتر بر اساس وضعیت
        if ($request->has('is_available') && $request->is_available !== null) {
            $query->where('is_available', filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN));
        }

        // فیلتر بر اساس تایید
        if ($request->has('is_verified') && $request->is_verified !== null) {
            $query->where('is_verified', filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN));
        }

        // فیلتر بر اساس کلینیک
        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', $request->clinic_id);
        }

        // مرتب‌سازی
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['id', 'created_at', 'consultation_fee', 'experience_years', 'is_available'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $doctors = $query->paginate($request->get('per_page', 15));

        return $this->success($doctors);
    }

    /**
     * ایجاد پزشک جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'specialty_id' => 'nullable|exists:specialties,id',
            'clinic_id' => 'nullable|exists:clinics,id',
            'license_number' => 'required|string|unique:doctors,license_number',
            'consultation_fee' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
            'bio' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $doctor = Doctor::create($request->all());
            return $this->success($doctor->load(['user', 'specialty']), 'پزشک با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش یک پزشک
     */
    public function show($id)
    {
        try {
            $doctor = Doctor::with(['user', 'specialty', 'clinic', 'province', 'city'])
                ->findOrFail($id);
            return $this->success($doctor);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی پزشک
     */
    public function update(Request $request, $id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'specialty_id' => 'nullable|exists:specialties,id',
            'clinic_id' => 'nullable|exists:clinics,id',
            'license_number' => 'sometimes|string|unique:doctors,license_number,' . $id,
            'consultation_fee' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
            'bio' => 'nullable|string',
            'experience_years' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $doctor->update($request->all());
            return $this->success($doctor->fresh()->load(['user', 'specialty']), 'پزشک با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف پزشک
     */
    public function destroy($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->delete();
            return $this->success(null, 'پزشک با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت در دسترس بودن
     */
    public function toggleAvailability($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->toggleAvailability();
            return $this->success($doctor->fresh(), 'وضعیت در دسترس بودن با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید پزشک
     */
    public function verify($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->verify();
            return $this->success($doctor->fresh(), 'پزشک با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لغو تایید پزشک
     */
    public function unverify($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->unverify();
            return $this->success($doctor->fresh(), 'تایید پزشک با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تنظیم هزینه ویزیت
     */
    public function setAppointmentFee(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'fee' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->update(['consultation_fee' => $request->fee]);
            return $this->success($doctor->fresh(), 'هزینه ویزیت با موفقیت تنظیم شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت هزینه ویزیت
     */
    public function getAppointmentFee($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            return $this->success([
                'consultation_fee' => $doctor->consultation_fee,
                'fee_label' => $doctor->fee_label,
            ]);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * تنظیم رایگان بودن ویزیت
     */
    public function setFree($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->update([
                'appointment_fee_type' => 'free',
                'appointment_fee_amount' => 0,
            ]);
            return $this->success($doctor->fresh(), 'ویزیت پزشک رایگان شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تنظیم پولی بودن ویزیت
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
            $doctor->update([
                'appointment_fee_type' => 'paid',
                'appointment_fee_amount' => $request->amount,
            ]);
            return $this->success($doctor->fresh(), 'ویزیت پزشک پولی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آپلود عکس پروفایل
     */
    public function uploadProfileImage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->clearMediaCollection('profile_image');
            $doctor->addMedia($request->file('image'))->toMediaCollection('profile_image');

            return $this->success([
                'profile_image' => $doctor->profile_image_url,
                'profile_image_thumb' => $doctor->profile_image_thumb,
                'profile_image_medium' => $doctor->profile_image_medium,
                'profile_image_large' => $doctor->profile_image_large,
            ], 'عکس پروفایل با موفقیت آپلود شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف عکس پروفایل
     */
    public function deleteProfileImage($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->clearMediaCollection('profile_image');
            return $this->success(null, 'عکس پروفایل با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت عکس پروفایل
     */
    public function getProfileImage($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            return $this->success([
                'profile_image' => $doctor->profile_image_url,
                'profile_image_thumb' => $doctor->profile_image_thumb,
                'profile_image_medium' => $doctor->profile_image_medium,
                'profile_image_large' => $doctor->profile_image_large,
            ]);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * آمار پزشک
     */
    public function stats($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $stats = [
                'total_appointments' => $doctor->appointments()->count(),
                'total_patients' => $doctor->patients()->count(),
                'total_prescriptions' => $doctor->prescriptions()->count(),
                'rating' => $doctor->rating,
                'total_reviews' => $doctor->total_reviews,
                'is_available' => $doctor->is_available,
                'is_verified' => $doctor->is_verified,
            ];
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * بروزرسانی موقعیت مکانی
     */
    public function updateLocation(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
            return $this->success($doctor->fresh(), 'موقعیت مکانی با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
