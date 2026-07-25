<?php
// app/Services/EHR/EHRService.php

namespace App\Services\EHR;

use App\Models\EHRRecord;
use App\Models\EHRVisit;
use App\Models\MedicalDocument;
use App\Models\MedicalAlert;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EHRService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    // ============================================================
    // RECORDS
    // ============================================================

    /**
     * ایجاد پرونده جدید
     */
    public function createRecord(array $data): EHRRecord
    {
        return DB::transaction(function () use ($data) {
            $data['tenant_id'] = $this->tenantId;

            // اگر doctor_id وارد نشده، از کاربر فعلی بگیر
            if (!isset($data['doctor_id'])) {
                $doctor = Doctor::where('user_id', auth()->id())->first();
                $data['doctor_id'] = $doctor?->id;
            }

            $data['recorded_at'] = now();
            $data['status'] = $data['status'] ?? 'active';

            $record = EHRRecord::create($data);

            Log::info('EHR Record created', [
                'record_id' => $record->id,
                'patient_id' => $record->patient_id,
                'tenant_id' => $this->tenantId,
            ]);

            return $record->load(['patient', 'doctor']);
        });
    }

    /**
     * دریافت پرونده
     */
    public function getRecord(int $id): EHRRecord
    {
        return EHRRecord::where('tenant_id', $this->tenantId)
            ->with(['patient', 'doctor', 'visits', 'visits.doctor', 'documents', 'alerts'])
            ->findOrFail($id);
    }

    /**
     * دریافت پرونده‌های یک بیمار
     */
    public function getPatientRecords(int $patientId, array $filters = [], int $perPage = 15)
    {
        $query = EHRRecord::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['doctor']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%")
                ->orWhere('diagnosis', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * بروزرسانی پرونده
     */
    public function updateRecord(EHRRecord $record, array $data): EHRRecord
    {
        $record->update($data);

        Log::info('EHR Record updated', [
            'record_id' => $record->id,
            'patient_id' => $record->patient_id,
        ]);

        return $record->fresh();
    }

    /**
     * حذف پرونده
     */
    public function deleteRecord(EHRRecord $record): void
    {
        $record->delete();

        Log::info('EHR Record deleted', [
            'record_id' => $record->id,
            'patient_id' => $record->patient_id,
        ]);
    }

    // ============================================================
    // VISITS
    // ============================================================

    /**
     * افزودن ویزیت جدید
     */
    public function addVisit(array $data): EHRVisit
    {
        return DB::transaction(function () use ($data) {
            $data['tenant_id'] = $this->tenantId;
            $data['visit_date'] = $data['visit_date'] ?? now();

            // اگر doctor_id وارد نشده، از کاربر فعلی بگیر
            if (!isset($data['doctor_id'])) {
                $doctor = Doctor::where('user_id', auth()->id())->first();
                $data['doctor_id'] = $doctor?->id;
            }

            $visit = EHRVisit::create($data);

            // به‌روزرسانی زمان آخرین ویزیت در پرونده
            if ($visit->ehr_record_id) {
                $record = EHRRecord::find($visit->ehr_record_id);
                if ($record) {
                    $record->update(['last_visit_at' => now()]);
                }
            }

            Log::info('EHR Visit added', [
                'visit_id' => $visit->id,
                'record_id' => $visit->ehr_record_id,
                'doctor_id' => $visit->doctor_id,
            ]);

            return $visit->load(['doctor', 'appointment']);
        });
    }

    /**
     * دریافت ویزیت‌های یک پرونده
     */
    public function getVisits(int $recordId, int $perPage = 20)
    {
        return EHRVisit::where('tenant_id', $this->tenantId)
            ->where('ehr_record_id', $recordId)
            ->with(['doctor', 'appointment'])
            ->orderBy('visit_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * دریافت یک ویزیت خاص
     */
    public function getVisit(int $id): EHRVisit
    {
        return EHRVisit::where('tenant_id', $this->tenantId)
            ->with(['doctor', 'appointment', 'ehrRecord'])
            ->findOrFail($id);
    }

    /**
     * بروزرسانی ویزیت
     */
    public function updateVisit(EHRVisit $visit, array $data): EHRVisit
    {
        $visit->update($data);
        return $visit->fresh();
    }

    /**
     * حذف ویزیت
     */
    public function deleteVisit(EHRVisit $visit): void
    {
        $visit->delete();
    }

    // ============================================================
    // DOCUMENTS
    // ============================================================

    /**
     * آپلود مدرک
     */
    public function uploadDocument(array $data, $file): MedicalDocument
    {
        return DB::transaction(function () use ($data, $file) {
            $data['tenant_id'] = $this->tenantId;

            // ذخیره فایل
            $path = $file->store('medical-documents/' . $data['patient_id'], 'public');

            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_type'] = $file->getMimeType();
            $data['file_size'] = $file->getSize();
            $data['uploaded_at'] = now();

            // اگر doctor_id وارد نشده، از کاربر فعلی بگیر
            if (!isset($data['doctor_id'])) {
                $doctor = Doctor::where('user_id', auth()->id())->first();
                $data['doctor_id'] = $doctor?->id;
            }

            $document = MedicalDocument::create($data);

            Log::info('Medical document uploaded', [
                'document_id' => $document->id,
                'patient_id' => $document->patient_id,
                'file_name' => $document->file_name,
            ]);

            return $document->fresh();
        });
    }

    /**
     * دریافت مدارک بیمار
     */
    public function getDocuments(int $patientId, array $filters = [], int $perPage = 20)
    {
        $query = MedicalDocument::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId);

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%")
                ->orWhere('description', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * دریافت یک مدرک
     */
    public function getDocument(int $id): MedicalDocument
    {
        return MedicalDocument::where('tenant_id', $this->tenantId)->findOrFail($id);
    }

    /**
     * حذف مدرک
     */
    public function deleteDocument(MedicalDocument $document): void
    {
        // حذف فایل از دیسک
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        Log::info('Medical document deleted', [
            'document_id' => $document->id,
            'patient_id' => $document->patient_id,
            'file_path' => $document->file_path,
        ]);
    }

    // ============================================================
    // ALERTS
    // ============================================================

    /**
     * ایجاد هشدار جدید
     */
    public function createAlert(array $data): MedicalAlert
    {
        return DB::transaction(function () use ($data) {
            $data['tenant_id'] = $this->tenantId;
            $data['severity'] = $data['severity'] ?? 'medium';
            $data['is_active'] = true;
            $data['is_read'] = false;

            // اگر doctor_id وارد نشده، از کاربر فعلی بگیر
            if (!isset($data['doctor_id'])) {
                $doctor = Doctor::where('user_id', auth()->id())->first();
                $data['doctor_id'] = $doctor?->id;
            }

            $alert = MedicalAlert::create($data);

            Log::info('Medical alert created', [
                'alert_id' => $alert->id,
                'patient_id' => $alert->patient_id,
                'type' => $alert->type,
                'severity' => $alert->severity,
            ]);

            return $alert;
        });
    }

    /**
     * دریافت هشدارهای بیمار
     */
    public function getPatientAlerts(int $patientId, array $filters = [])
    {
        $query = MedicalAlert::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * رفع هشدار
     */
    public function resolveAlert(int $id): MedicalAlert
    {
        $alert = MedicalAlert::where('tenant_id', $this->tenantId)->findOrFail($id);
        $alert->markAsResolved();

        Log::info('Medical alert resolved', [
            'alert_id' => $alert->id,
            'patient_id' => $alert->patient_id,
            'resolved_by' => auth()->id(),
        ]);

        return $alert->fresh();
    }

    /**
     * علامت‌گذاری هشدار به عنوان خوانده شده
     */
    public function markAlertAsRead(int $id): MedicalAlert
    {
        $alert = MedicalAlert::where('tenant_id', $this->tenantId)->findOrFail($id);
        $alert->markAsRead();
        return $alert->fresh();
    }

    // ============================================================
    // PATIENT HISTORY
    // ============================================================

    /**
     * دریافت تاریخچه کامل بیمار
     */
    public function getFullHistory(int $patientId): array
    {
        $patient = Patient::where('tenant_id', $this->tenantId)
            ->with(['user', 'doctor', 'doctor.user', 'doctor.specialty'])
            ->findOrFail($patientId);

        $records = EHRRecord::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['visits.doctor', 'documents', 'alerts'])
            ->orderBy('created_at', 'desc')
            ->get();

        // جمع‌آوری تمام ویزیت‌ها
        $allVisits = [];
        foreach ($records as $record) {
            foreach ($record->visits as $visit) {
                $allVisits[] = $visit;
            }
        }

        // جمع‌آوری تمام مدارک
        $allDocuments = [];
        foreach ($records as $record) {
            foreach ($record->documents as $doc) {
                $allDocuments[] = $doc;
            }
        }

        // جمع‌آوری تمام هشدارها
        $allAlerts = [];
        foreach ($records as $record) {
            foreach ($record->alerts as $alert) {
                $allAlerts[] = $alert;
            }
        }

        // مرتب‌سازی بر اساس تاریخ
        usort($allVisits, function ($a, $b) {
            return strtotime($b->visit_date) - strtotime($a->visit_date);
        });

        return [
            'patient' => $patient,
            'records' => $records,
            'visits' => $allVisits,
            'documents' => $allDocuments,
            'alerts' => $allAlerts,
            'summary' => [
                'total_records' => $records->count(),
                'total_visits' => count($allVisits),
                'total_documents' => count($allDocuments),
                'total_alerts' => count($allAlerts),
                'active_alerts' => MedicalAlert::where('tenant_id', $this->tenantId)
                    ->where('patient_id', $patientId)
                    ->where('is_active', true)
                    ->count(),
                'last_visit' => !empty($allVisits) ? $allVisits[0] : null,
            ],
        ];
    }

    // ============================================================
    // STATS
    // ============================================================

    /**
     * دریافت آمار بیمار
     */
    public function getStats(int $patientId): array
    {
        $records = EHRRecord::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId);

        $visits = EHRVisit::where('tenant_id', $this->tenantId)
            ->whereHas('ehrRecord', function ($q) use ($patientId) {
                $q->where('patient_id', $patientId);
            });

        $documents = MedicalDocument::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId);

        $alerts = MedicalAlert::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId);

        return [
            'total_records' => $records->count(),
            'active_records' => (clone $records)->where('status', 'active')->count(),
            'total_visits' => $visits->count(),
            'visits_by_type' => (clone $visits)
                ->selectRaw('visit_type, count(*) as count')
                ->groupBy('visit_type')
                ->get()
                ->pluck('count', 'visit_type')
                ->toArray(),
            'total_documents' => $documents->count(),
            'documents_by_category' => (clone $documents)
                ->selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category')
                ->toArray(),
            'total_alerts' => $alerts->count(),
            'active_alerts' => (clone $alerts)->where('is_active', true)->count(),
            'alerts_by_severity' => (clone $alerts)
                ->selectRaw('severity, count(*) as count')
                ->groupBy('severity')
                ->get()
                ->pluck('count', 'severity')
                ->toArray(),
        ];
    }

    // ============================================================
    // SEARCH
    // ============================================================

    /**
     * جستجوی پیشرفته در پرونده‌ها
     */
    public function searchRecords(string $query, array $filters = [], int $perPage = 15)
    {
        $search = EHRRecord::where('tenant_id', $this->tenantId)
            ->with(['patient', 'doctor']);

        // جستجو در عنوان و تشخیص
        $search->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
                ->orWhere('diagnosis', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->orWhere('treatment_plan', 'like', "%{$query}%");
        });

        // جستجو در نام بیمار
        $search->orWhereHas('patient', function ($q) use ($query) {
            $q->where('full_name', 'like', "%{$query}%")
                ->orWhere('national_code', 'like', "%{$query}%");
        });

        if (isset($filters['status'])) {
            $search->where('status', $filters['status']);
        }

        if (isset($filters['doctor_id'])) {
            $search->where('doctor_id', $filters['doctor_id']);
        }

        if (isset($filters['from_date'])) {
            $search->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $search->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $search->orderBy('created_at', 'desc')->paginate($perPage);
    }

    // ============================================================
    // SHARING
    // ============================================================

    /**
     * اشتراک‌گذاری پرونده با پزشک دیگر
     */
    public function shareRecord(int $recordId, int $doctorId): bool
    {
        $record = EHRRecord::where('tenant_id', $this->tenantId)->findOrFail($recordId);

        // بررسی اینکه آیا قبلاً اشتراک‌گذاری شده
        $shared = $record->shared_with ?? [];
        if (!in_array($doctorId, $shared)) {
            $shared[] = $doctorId;
            $record->update(['shared_with' => $shared]);
        }

        Log::info('EHR Record shared', [
            'record_id' => $recordId,
            'shared_with_doctor' => $doctorId,
        ]);

        return true;
    }

    /**
     * لغو اشتراک‌گذاری پرونده
     */
    public function unshareRecord(int $recordId, int $doctorId): bool
    {
        $record = EHRRecord::where('tenant_id', $this->tenantId)->findOrFail($recordId);

        $shared = $record->shared_with ?? [];
        $shared = array_filter($shared, function ($id) use ($doctorId) {
            return $id != $doctorId;
        });

        $record->update(['shared_with' => array_values($shared)]);

        Log::info('EHR Record unshared', [
            'record_id' => $recordId,
            'unshared_from_doctor' => $doctorId,
        ]);

        return true;
    }

    // ============================================================
    // EXPORT
    // ============================================================

    /**
     * خروجی PDF از پرونده
     */
    public function exportToPdf(int $recordId)
    {
        $record = $this->getRecord($recordId);
        // می‌توانید از کتابخانه‌های PDF مانند DomPDF استفاده کنید
        return $record;
    }

    /**
     * خروجی JSON از پرونده
     */
    public function exportToJson(int $recordId): array
    {
        $record = $this->getRecord($recordId);
        return $record->toArray();
    }
}
