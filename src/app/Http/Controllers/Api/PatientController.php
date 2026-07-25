<?php
// app/Http/Controllers/Api/PatientController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Patient\PatientService;
use App\Traits\ApiResponse;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use ApiResponse;

    protected PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    /**
     * لیست بیماران (عمومی - API)
     */
    public function index(Request $request)
    {
        $filters = $request->all();
        $filters['is_active'] = true;

        // فیلتر بر اساس استان
        if ($request->has('province_id') && $request->province_id) {
            $filters['province_id'] = $request->province_id;
        }

        // فیلتر بر اساس شهر
        if ($request->has('city_id') && $request->city_id) {
            $filters['city_id'] = $request->city_id;
        }

        $patients = $this->patientService->list($filters, $request->get('per_page', 15));
        return $this->success($patients);
    }

    /**
     * نمایش یک بیمار (عمومی - API)
     */
    public function show($id)
    {
        try {
            $patient = $this->patientService->show($id);
            return $this->success($patient);
        } catch (\Exception $e) {
            return $this->error('بیمار یافت نشد', 404);
        }
    }

    /**
     * دریافت بیماران نزدیک (عمومی - API)
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        $radius = $request->radius ?? 10;
        $perPage = $request->get('per_page', 15);

        $patients = Patient::where('tenant_id', session('tenant_id'))
            ->with(['user', 'doctor', 'province', 'city'])
            ->where('is_active', true)
            ->nearby($request->lat, $request->lng, $radius)
            ->paginate($perPage);

        return $this->success($patients);
    }

    /**
     * دریافت بیماران من (پزشک جاری)
     */
    public function myPatients(Request $request)
    {
        $user = auth()->user();
        $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('شما پزشک نیستید', 403);
        }

        $filters = $request->all();
        $filters['doctor_id'] = $doctor->id;

        $patients = $this->patientService->list($filters, $request->get('per_page', 15));
        return $this->success($patients);
    }

    /**
     * دریافت اطلاعات بیمار جاری (خود بیمار)
     */
    public function myProfile(Request $request)
    {
        $user = auth()->user();
        $patient = $this->patientService->getCurrentPatient($user->id);

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        return $this->success($patient->load(['user', 'doctor', 'province', 'city']));
    }

    /**
     * به‌روزرسانی پروفایل بیمار (خود بیمار)
     */
    public function updateMyProfile(Request $request)
    {
        $user = auth()->user();

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'national_code' => 'nullable|string|size:10',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'insurance_type' => 'nullable|string|max:50',
            'insurance_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $patient = $this->patientService->updateCurrentPatient($user->id, $request->all());
            return $this->success(
                $patient->load(['user', 'doctor', 'province', 'city']),
                'پروفایل با موفقیت به‌روزرسانی شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
