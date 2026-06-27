<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EHR\EHRService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EHRController extends Controller
{
    use ApiResponse;

    protected EHRService $ehrService;

    public function __construct(EHRService $ehrService)
    {
        $this->ehrService = $ehrService;
    }

    // ============================================================
    // RECORDS
    // ============================================================

    public function createRecord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:active,completed,archived',
            'is_emergency' => 'nullable|boolean',
            'is_confidential' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $record = $this->ehrService->createRecord($request->all());
            return $this->success($record, 'پرونده با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getRecord($id)
    {
        try {
            $record = $this->ehrService->getRecord($id);
            return $this->success($record);
        } catch (\Exception $e) {
            return $this->error('پرونده یافت نشد', 404);
        }
    }

    public function patientRecords(Request $request, $patientId)
    {
        $records = $this->ehrService->getPatientRecords(
            $patientId,
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($records);
    }

    public function updateRecord(Request $request, $id)
    {
        try {
            $record = $this->ehrService->getRecord($id);
        } catch (\Exception $e) {
            return $this->error('پرونده یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:active,completed,archived',
            'is_emergency' => 'nullable|boolean',
            'is_confidential' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $record = $this->ehrService->updateRecord($record, $request->all());
            return $this->success($record, 'پرونده با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteRecord($id)
    {
        try {
            $record = $this->ehrService->getRecord($id);
            $this->ehrService->deleteRecord($record);
            return $this->success(null, 'پرونده با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // VISITS
    // ============================================================

    public function addVisit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ehr_record_id' => 'required|exists:ehr_records,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'doctor_id' => 'required|exists:doctors,id',
            'visit_type' => 'required|in:initial,follow_up,emergency,consultation',
            'chief_complaint' => 'nullable|string',
            'history_of_present_illness' => 'nullable|string',
            'past_medical_history' => 'nullable|string',
            'family_history' => 'nullable|string',
            'social_history' => 'nullable|string',
            'physical_exam' => 'nullable|string',
            'assessment' => 'nullable|string',
            'plan' => 'nullable|string',
            'notes' => 'nullable|string',
            'vital_signs' => 'nullable|array',
            'visit_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $visit = $this->ehrService->addVisit($request->all());
            return $this->success($visit, 'ویزیت با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getVisits(Request $request, $recordId)
    {
        $visits = $this->ehrService->getVisits($recordId, $request->get('per_page', 20));
        return $this->success($visits);
    }

    // ============================================================
    // DOCUMENTS
    // ============================================================

    public function uploadDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'ehr_record_id' => 'nullable|exists:ehr_records,id',
            'title' => 'required|string|max:255',
            'category' => 'required|in:lab_result,imaging,prescription,referral,other',
            'description' => 'nullable|string',
            'is_private' => 'nullable|boolean',
            'file' => 'required|file|max:20480',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $document = $this->ehrService->uploadDocument(
                $request->except('file'),
                $request->file('file')
            );
            return $this->success($document, 'مدرک با موفقیت آپلود شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getDocuments(Request $request, $patientId)
    {
        $documents = $this->ehrService->getDocuments(
            $patientId,
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($documents);
    }

    public function deleteDocument($id)
    {
        try {
            $document = \App\Models\MedicalDocument::findOrFail($id);
            $this->ehrService->deleteDocument($document);
            return $this->success(null, 'مدرک با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // ALERTS
    // ============================================================

    public function createAlert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'type' => 'required|in:allergy,drug_interaction,chronic_disease,critical_result',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'severity' => 'nullable|in:low,medium,high,critical',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $alert = $this->ehrService->createAlert($request->all());
            return $this->success($alert, 'هشدار با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getPatientAlerts(Request $request, $patientId)
    {
        $alerts = $this->ehrService->getPatientAlerts($patientId, $request->all());
        return $this->success($alerts);
    }

    public function resolveAlert($id)
    {
        try {
            $alert = $this->ehrService->resolveAlert($id);
            return $this->success($alert, 'هشدار با موفقیت برطرف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // PATIENT HISTORY
    // ============================================================

    public function fullHistory($patientId)
    {
        try {
            $history = $this->ehrService->getFullHistory($patientId);
            return $this->success($history);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    // ============================================================
    // STATS
    // ============================================================

    public function stats($patientId)
    {
        try {
            $stats = $this->ehrService->getStats($patientId);
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
