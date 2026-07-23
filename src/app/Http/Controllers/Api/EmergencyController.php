<?php
// app/Http/Controllers/Api/EmergencyController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Emergency\EmergencyService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmergencyController extends Controller
{
    use ApiResponse;

    protected EmergencyService $emergencyService;

    public function __construct(EmergencyService $emergencyService)
    {
        $this->emergencyService = $emergencyService;
    }

    /**
     * درخواست اورژانس جدید (با کلینیک)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // اطلاعات بیمار
            'patient_name' => 'required_without:patient_id|string|max:255',
            'patient_id' => 'nullable|exists:patients,id',
            'national_code' => 'nullable|string|size:10',
            'mobile' => 'nullable|regex:/^09[0-9]{9}$/',
            'phone' => 'nullable|string|max:15',

            // اطلاعات موقعیت
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',

            // ✅ اطلاعات کلینیک (برای اعزام آمبولانس)
            'clinic_id' => 'nullable|exists:clinics,id',

            // اطلاعات پزشکی
            'chief_complaint' => 'required|string|max:500',
            'history' => 'nullable|string',
            'allergies' => 'nullable|string',
            'medications' => 'nullable|string',
            'past_medical_history' => 'nullable|string',

            // تماس اضطراری
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relation' => 'nullable|string|max:50',

            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $emergency = $this->emergencyService->createEmergencyRequest($request->all());

            // ✅ اگر کلینیک ارسال شده، تنظیم کن
            if ($request->has('clinic_id') && $request->clinic_id) {
                $emergency->update(['clinic_id' => $request->clinic_id]);
            }

            return $this->success($emergency->load(['clinic', 'province', 'city']), 'درخواست اورژانس با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت وضعیت درخواست اورژانس
     */
    public function status($id)
    {
        try {
            $emergency = $this->emergencyService->getEmergencyRequest($id);

            // بررسی دسترسی
            $user = auth()->user();
            if (!$user->isAdmin() && $emergency->patient->user_id != $user->id) {
                return $this->error('شما دسترسی به این درخواست ندارید', 403);
            }

            return $this->success([
                'id' => $emergency->id,
                'status' => $emergency->status,
                'status_label' => $emergency->status_label,
                'status_color' => $emergency->status_color,
                'triage_level' => $emergency->triage_level,
                'triage_level_label' => $emergency->triage_level_label,
                'ambulance_number' => $emergency->ambulance_number,
                'clinic_id' => $emergency->clinic_id,           // ✅ اضافه شد
                'clinic_name' => $emergency->clinic?->name,     // ✅ اضافه شد
                'dispatched_at' => $emergency->dispatched_at,
                'arrived_at' => $emergency->arrived_at,
                'completed_at' => $emergency->completed_at,
                'created_at' => $emergency->created_at,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * دریافت تاریخچه درخواست‌های اورژانس کاربر (با کلینیک)
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $patient = \App\Models\Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $emergencies = \App\Models\Emergency\EmergencyPatient::where('patient_id', $patient->id)
            ->with(['clinic', 'province', 'city'])  // ✅ اضافه شد
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($emergencies);
    }
}
