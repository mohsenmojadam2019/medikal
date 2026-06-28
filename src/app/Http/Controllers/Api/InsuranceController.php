<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Insurance\InsuranceService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsuranceController extends Controller
{
    use ApiResponse;

    protected InsuranceService $insuranceService;

    public function __construct(InsuranceService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
    }

    public function index(Request $request)
    {
        $insurances = $this->insuranceService->getInsurances($request->all(), $request->get('per_page', 20));
        return $this->success($insurances);
    }

    public function activeInsurances()
    {
        $insurances = $this->insuranceService->getActiveInsurances();
        return $this->success($insurances);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|unique:insurances,code',
            'description' => 'nullable|string',
            'coverage_percentage' => 'required|numeric|min:0|max:100',
            'max_coverage_per_year' => 'nullable|numeric|min:0',
            'max_coverage_per_visit' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'services' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $insurance = $this->insuranceService->createInsurance($request->all());
            return $this->success($insurance, 'بیمه با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show($id)
    {
        try {
            $insurance = Insurance::findOrFail($id);
            return $this->success($insurance);
        } catch (\Exception $e) {
            return $this->error('بیمه یافت نشد', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $insurance = Insurance::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('بیمه یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|unique:insurances,code,' . $id,
            'description' => 'nullable|string',
            'coverage_percentage' => 'sometimes|numeric|min:0|max:100',
            'max_coverage_per_year' => 'nullable|numeric|min:0',
            'max_coverage_per_visit' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'services' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $insurance = $this->insuranceService->updateInsurance($insurance, $request->all());
            return $this->success($insurance, 'بیمه با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $insurance = Insurance::findOrFail($id);
            $this->insuranceService->deleteInsurance($insurance);
            return $this->success(null, 'بیمه با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $insurance = Insurance::findOrFail($id);
            $insurance = $this->insuranceService->toggleStatus($insurance);
            return $this->success($insurance, 'وضعیت بیمه با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function assignToPatient(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'insurance_id' => 'required|exists:insurances,id',
            'policy_number' => 'nullable|string|unique:patient_insurances,policy_number',
            'card_number' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'is_primary' => 'nullable|boolean',
            'coverage_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $patientInsurance = $this->insuranceService->assignInsuranceToPatient($request->all());
            return $this->success($patientInsurance, 'بیمه با موفقیت به بیمار اختصاص داده شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function patientInsurances($patientId)
    {
        $insurances = $this->insuranceService->getPatientInsurances($patientId);
        return $this->success($insurances);
    }

    public function patientPrimaryInsurance($patientId)
    {
        $insurance = $this->insuranceService->getPatientPrimaryInsurance($patientId);
        if (!$insurance) {
            return $this->error('بیمه اصلی برای این بیمار یافت نشد', 404);
        }
        return $this->success($insurance);
    }

    public function updatePatientInsurance(Request $request, $id)
    {
        try {
            $patientInsurance = PatientInsurance::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('بیمه بیمار یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'policy_number' => 'sometimes|string|unique:patient_insurances,policy_number,' . $id,
            'card_number' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'is_primary' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'coverage_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $patientInsurance = $this->insuranceService->updatePatientInsurance($patientInsurance, $request->all());
            return $this->success($patientInsurance, 'بیمه بیمار با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deactivatePatientInsurance($id)
    {
        try {
            $patientInsurance = PatientInsurance::findOrFail($id);
            $patientInsurance = $this->insuranceService->deactivatePatientInsurance($patientInsurance);
            return $this->success($patientInsurance, 'بیمه بیمار با موفقیت غیرفعال شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function applyToAppointment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
            'patient_insurance_id' => 'required|exists:patient_insurances,id',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $appInsurance = $this->insuranceService->applyInsuranceToAppointment($request->appointment_id, $request->patient_insurance_id);
            return $this->success($appInsurance, 'بیمه با موفقیت به نوبت اعمال شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function appointmentInsurance($appointmentId)
    {
        $insurance = $this->insuranceService->getAppointmentInsurance($appointmentId);
        if (!$insurance) {
            return $this->error('بیمه ای برای این نوبت یافت نشد', 404);
        }
        return $this->success($insurance);
    }

    public function approveClaim($id)
    {
        try {
            $appInsurance = $this->insuranceService->approveInsuranceClaim($id);
            return $this->success($appInsurance, 'درخواست بیمه با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function rejectClaim($id)
    {
        try {
            $appInsurance = $this->insuranceService->rejectInsuranceClaim($id);
            return $this->success($appInsurance, 'درخواست بیمه با موفقیت رد شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function stats(Request $request)
    {
        $stats = $this->insuranceService->getInsuranceStats($request->all());
        return $this->success($stats);
    }

    public function insuranceReport(Request $request, $insuranceId)
    {
        try {
            $report = $this->insuranceService->getInsuranceReport($insuranceId, $request->all());
            return $this->success($report);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
