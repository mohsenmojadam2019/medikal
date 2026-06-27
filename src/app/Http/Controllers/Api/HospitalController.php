<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Hospital\HospitalService;
use App\Traits\ApiResponse;
use App\Models\Admission;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\Discharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HospitalController extends Controller
{
    use ApiResponse;

    protected HospitalService $hospitalService;

    public function __construct(HospitalService $hospitalService)
    {
        $this->hospitalService = $hospitalService;
        $this->middleware(['auth:sanctum']);
    }

    // ============================================================
    // WARDS
    // ============================================================

    public function wards(Request $request)
    {
        $wards = $this->hospitalService->getWards(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($wards);
    }

    public function activeWards()
    {
        $wards = Ward::active()
            ->withCount(['beds', 'admissions'])
            ->get();
        return $this->success($wards);
    }

    public function showWard($id)
    {
        try {
            $ward = Ward::with(['beds', 'admissions'])->findOrFail($id);
            return $this->success($ward);
        } catch (\Exception $e) {
            return $this->error('بخش یافت نشد', 404);
        }
    }

    public function storeWard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'floor' => 'nullable|integer',
            'capacity' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $ward = $this->hospitalService->createWard($request->all());
            return $this->success($ward, 'بخش با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateWard(Request $request, $id)
    {
        try {
            $ward = Ward::findOrFail($id);
            $ward = $this->hospitalService->updateWard($ward, $request->all());
            return $this->success($ward, 'بخش با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteWard($id)
    {
        try {
            $ward = Ward::findOrFail($id);
            $this->hospitalService->deleteWard($ward);
            return $this->success(null, 'بخش با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // BEDS
    // ============================================================

    public function beds(Request $request)
    {
        $beds = $this->hospitalService->getBeds(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($beds);
    }

    public function showBed($id)
    {
        try {
            $bed = Bed::with(['ward', 'currentAdmission'])->findOrFail($id);
            return $this->success($bed);
        } catch (\Exception $e) {
            return $this->error('تخت یافت نشد', 404);
        }
    }

    public function storeBed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ward_id' => 'required|exists:wards,id',
            'bed_number' => 'required|string|max:50',
            'is_private' => 'nullable|boolean',
            'price_per_day' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $bed = $this->hospitalService->createBed($request->all());
            return $this->success($bed, 'تخت با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateBed(Request $request, $id)
    {
        try {
            $bed = Bed::findOrFail($id);
            $bed = $this->hospitalService->updateBed($bed, $request->all());
            return $this->success($bed, 'تخت با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteBed($id)
    {
        try {
            $bed = Bed::findOrFail($id);
            $this->hospitalService->deleteBed($bed);
            return $this->success(null, 'تخت با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function changeBedStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:available,occupied,reserved,maintenance,cleaning',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $bed = Bed::findOrFail($id);
            $bed = $this->hospitalService->changeBedStatus($bed, $request->status);
            return $this->success($bed, 'وضعیت تخت با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // ADMISSIONS
    // ============================================================

    public function admissions(Request $request)
    {
        $admissions = $this->hospitalService->getAdmissions(
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($admissions);
    }

    public function showAdmission($id)
    {
        try {
            $admission = $this->hospitalService->getAdmission($id);

            // بررسی دسترسی
            $user = auth()->user();
            if (!$user->isAdmin() &&
                $admission->patient->user_id != $user->id &&
                $admission->doctor->user_id != $user->id) {
                return $this->error('شما دسترسی به این پذیرش ندارید', 403);
            }

            return $this->success($admission);
        } catch (\Exception $e) {
            return $this->error('پذیرش یافت نشد', 404);
        }
    }

    public function storeAdmission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'ward_id' => 'required|exists:wards,id',
            'bed_id' => 'nullable|exists:beds,id',
            'diagnosis' => 'nullable|string',
            'chief_complaint' => 'nullable|string',
            'history_of_present_illness' => 'nullable|string',
            'past_medical_history' => 'nullable|string',
            'allergies' => 'nullable|string',
            'medications' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $admission = $this->hospitalService->createAdmission($request->all());
            return $this->success($admission, 'پذیرش با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateAdmission(Request $request, $id)
    {
        try {
            $admission = Admission::findOrFail($id);
            $admission = $this->hospitalService->updateAdmission($admission, $request->all());
            return $this->success($admission, 'پذیرش با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function admitPatient($id)
    {
        try {
            $admission = Admission::findOrFail($id);
            $admission = $this->hospitalService->admitPatient($admission);
            return $this->success($admission, 'بیمار با موفقیت پذیرش شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // DISCHARGE
    // ============================================================

    public function dischargePatient(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'final_diagnosis' => 'nullable|string',
            'summary' => 'nullable|string',
            'medications_at_discharge' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'follow_up_date' => 'nullable|date|after:now',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $admission = Admission::findOrFail($id);
            $data = $request->all();
            $data['doctor_id'] = $data['doctor_id'] ?? $admission->doctor_id;

            $discharge = $this->hospitalService->dischargePatient($admission, $data);
            return $this->success($discharge, 'بیمار با موفقیت ترخیص شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // ADMISSION DAYS (VITAL SIGNS)
    // ============================================================

    public function addAdmissionDay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admission_id' => 'required|exists:admissions,id',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'heart_rate' => 'nullable|integer|min:0|max:300',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
            'blood_pressure_systolic' => 'nullable|integer|min:0|max:300',
            'blood_pressure_diastolic' => 'nullable|integer|min:0|max:200',
            'oxygen_saturation' => 'nullable|integer|min:0|max:100',
            'pain_score' => 'nullable|integer|min:0|max:10',
            'weight' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:300',
            'consciousness_level' => 'nullable|string|in:alert,drowsy,stupor,coma',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $day = $this->hospitalService->addAdmissionDay($request->all());
            return $this->success($day, 'ثبت روزانه با موفقیت اضافه شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getAdmissionDays($admissionId)
    {
        try {
            $admission = Admission::findOrFail($admissionId);
            $days = $this->hospitalService->getAdmissionDays($admissionId);
            return $this->success([
                'admission' => $admission,
                'days' => $days,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    // ============================================================
    // ADMISSION SERVICES
    // ============================================================

    public function addService(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admission_id' => 'required|exists:admissions,id',
            'service_name' => 'required|string|max:255',
            'type' => 'nullable|string|in:medical,paraclinical,surgery,consultation,nursing,other',
            'description' => 'nullable|string',
            'quantity' => 'nullable|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'performed_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $service = $this->hospitalService->addService($request->all());
            return $this->success($service, 'خدمت با موفقیت اضافه شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // ADMISSION DRUGS
    // ============================================================

    public function addDrug(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admission_id' => 'required|exists:admissions,id',
            'drug_name' => 'required|string|max:255',
            'dosage' => 'required|string|max:100',
            'frequency' => 'nullable|integer|min:1|max:10',
            'route' => 'nullable|string|in:oral,iv,im,sc,topical,inhalation',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'quantity' => 'nullable|integer|min:1',
            'unit_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $drug = $this->hospitalService->addDrug($request->all());
            return $this->success($drug, 'دارو با موفقیت اضافه شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // PATIENT ADMISSIONS (My Admissions)
    // ============================================================

    public function myAdmissions(Request $request)
    {
        $user = auth()->user();
        $patient = \App\Models\Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $admissions = $this->hospitalService->getAdmissions(
            ['patient_id' => $patient->id] + $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($admissions);
    }

    public function doctorAdmissions(Request $request)
    {
        $user = auth()->user();
        $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $admissions = $this->hospitalService->getAdmissions(
            ['doctor_id' => $doctor->id] + $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($admissions);
    }

    // ============================================================
    // STATISTICS
    // ============================================================

    public function stats(Request $request)
    {
        $stats = $this->hospitalService->getStats($request->all());
        return $this->success($stats);
    }

    public function wardStats($wardId)
    {
        try {
            $ward = Ward::findOrFail($wardId);
            $stats = [
                'ward' => $ward,
                'total_beds' => $ward->beds()->count(),
                'available_beds' => $ward->beds()->available()->count(),
                'occupied_beds' => $ward->beds()->occupied()->count(),
                'occupancy_rate' => $ward->occupancy_rate,
                'active_admissions' => $ward->admissions()->active()->count(),
                'total_admissions' => $ward->admissions()->count(),
            ];
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
