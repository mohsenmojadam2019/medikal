<?php
// app/Http/Controllers/Admin/EmergencyController.php

namespace App\Http\Controllers\Admin;

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
     * لیست درخواست‌های اورژانس (با فیلتر کلینیک)
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->all();

            // ✅ فیلتر بر اساس کلینیک
            if ($request->has('clinic_id') && $request->clinic_id) {
                $filters['clinic_id'] = $request->clinic_id;
            }

            $emergencies = $this->emergencyService->getEmergencyRequests(
                $filters,
                $request->get('per_page', 20)
            );

            return $this->success($emergencies);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * نمایش یک درخواست اورژانس
     */
    public function show($id)
    {
        try {
            $emergency = $this->emergencyService->getEmergencyRequest($id);
            return $this->success($emergency);
        } catch (\Exception $e) {
            return $this->error('درخواست اورژانس یافت نشد', 404);
        }
    }

    /**
     * تریاز بیمار
     */
    public function triage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'triage_level' => 'required|in:red,yellow,green,blue',
            'vital_signs' => 'nullable|array',
            'vital_signs.temperature' => 'nullable|numeric|min:30|max:45',
            'vital_signs.heart_rate' => 'nullable|integer|min:0|max:300',
            'vital_signs.respiratory_rate' => 'nullable|integer|min:0|max:100',
            'vital_signs.blood_pressure_systolic' => 'nullable|integer|min:0|max:300',
            'vital_signs.blood_pressure_diastolic' => 'nullable|integer|min:0|max:200',
            'vital_signs.oxygen_saturation' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $emergency = $this->emergencyService->triagePatient(
                $id,
                $request->triage_level,
                $request->vital_signs
            );
            return $this->success($emergency, 'تریاز با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * شروع معاینه
     */
    public function startExam($id)
    {
        try {
            $emergency = $this->emergencyService->startExam($id);
            return $this->success($emergency, 'معاینه شروع شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * شروع درمان
     */
    public function startTreatment($id)
    {
        try {
            $emergency = $this->emergencyService->startTreatment($id);
            return $this->success($emergency, 'درمان شروع شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * اعزام آمبولانس (با کلینیک)
     */
    public function dispatchAmbulance(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'ambulance_number' => 'required|string|max:50',
            'ambulance_team' => 'nullable|string|max:100',
            'clinic_id' => 'nullable|exists:clinics,id',  // ✅ اضافه شد
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $emergency = $this->emergencyService->dispatchAmbulance(
                $id,
                $request->ambulance_number,
                $request->ambulance_team
            );

            // ✅ اگر کلینیک ارسال شده، به‌روزرسانی کن
            if ($request->has('clinic_id') && $request->clinic_id) {
                $emergency->update(['clinic_id' => $request->clinic_id]);
            }

            return $this->success($emergency, 'آمبولانس با موفقیت اعزام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ثبت رسیدن آمبولانس
     */
    public function arrived($id)
    {
        try {
            $emergency = $this->emergencyService->markAmbulanceArrived($id);
            return $this->success($emergency, 'آمبولانس به محل رسید');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تکمیل فرآیند اورژانس
     */
    public function complete($id)
    {
        try {
            $emergency = $this->emergencyService->completeEmergency($id);
            return $this->success($emergency, 'فرآیند اورژانس تکمیل شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * بستری بیمار
     */
    public function admit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'admission_id' => 'nullable|exists:admissions,id',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $emergency = $this->emergencyService->admitPatient($id, $request->admission_id);
            return $this->success($emergency, 'بیمار با موفقیت بستری شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ترخیص بیمار
     */
    public function discharge($id)
    {
        try {
            $emergency = $this->emergencyService->dischargePatient($id);
            return $this->success($emergency, 'بیمار با موفقیت ترخیص شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * انتقال بیمار
     */
    public function transfer(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'to_hospital' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $emergency = $this->emergencyService->transferPatient($id, $request->to_hospital);
            return $this->success($emergency, 'بیمار با موفقیت منتقل شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار اورژانس (با فیلتر کلینیک)
     */
    public function stats(Request $request)
    {
        try {
            $filters = $request->all();

            // ✅ فیلتر بر اساس کلینیک
            if ($request->has('clinic_id') && $request->clinic_id) {
                $filters['clinic_id'] = $request->clinic_id;
            }

            $stats = $this->emergencyService->getStats($filters);
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
