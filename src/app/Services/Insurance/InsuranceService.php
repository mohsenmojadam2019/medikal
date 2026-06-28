<?php

namespace App\Services\Insurance;

use App\Models\Insurance;
use App\Models\PatientInsurance;
use App\Models\AppointmentInsurance;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsuranceService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getInsurances(array $filters = [], int $perPage = 20)
    {
        $query = Insurance::where('tenant_id', $this->tenantId);

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%")
                ->orWhere('code', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_valid'])) {
            $query->valid();
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getActiveInsurances()
    {
        return Insurance::where('tenant_id', $this->tenantId)->active()->valid()->get();
    }

    public function createInsurance(array $data): Insurance
    {
        $data['tenant_id'] = $this->tenantId;
        return Insurance::create($data);
    }

    public function updateInsurance(Insurance $insurance, array $data): Insurance
    {
        $insurance->update($data);
        return $insurance->fresh();
    }

    public function deleteInsurance(Insurance $insurance): void
    {
        $insurance->delete();
    }

    public function toggleStatus(Insurance $insurance): Insurance
    {
        $insurance->update(['is_active' => !$insurance->is_active]);
        return $insurance->fresh();
    }

    public function assignInsuranceToPatient(array $data): PatientInsurance
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['is_primary']) && $data['is_primary']) {
                PatientInsurance::where('tenant_id', $this->tenantId)
                    ->where('patient_id', $data['patient_id'])
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $data['tenant_id'] = $this->tenantId;
            return PatientInsurance::create($data);
        });
    }

    public function getPatientInsurances(int $patientId)
    {
        return PatientInsurance::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['insurance'])
            ->active()
            ->get();
    }

    public function getPatientPrimaryInsurance(int $patientId): ?PatientInsurance
    {
        return PatientInsurance::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['insurance'])
            ->primary()
            ->active()
            ->first();
    }

    public function updatePatientInsurance(PatientInsurance $patientInsurance, array $data): PatientInsurance
    {
        return DB::transaction(function () use ($patientInsurance, $data) {
            if (isset($data['is_primary']) && $data['is_primary']) {
                PatientInsurance::where('tenant_id', $this->tenantId)
                    ->where('patient_id', $patientInsurance->patient_id)
                    ->where('id', '!=', $patientInsurance->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $patientInsurance->update($data);
            return $patientInsurance->fresh();
        });
    }

    public function deactivatePatientInsurance(PatientInsurance $patientInsurance): PatientInsurance
    {
        $patientInsurance->update(['is_active' => false]);
        return $patientInsurance->fresh();
    }

    public function applyInsuranceToAppointment(int $appointmentId, int $patientInsuranceId): AppointmentInsurance
    {
        return DB::transaction(function () use ($appointmentId, $patientInsuranceId) {
            $appointment = Appointment::where('tenant_id', $this->tenantId)
                ->with(['patient'])
                ->findOrFail($appointmentId);

            $patientInsurance = PatientInsurance::where('tenant_id', $this->tenantId)
                ->with(['insurance'])
                ->findOrFail($patientInsuranceId);

            $totalAmount = $appointment->final_price ?? 0;
            $coverage = $patientInsurance->calculateCoverage($totalAmount);

            $appointmentInsurance = AppointmentInsurance::create([
                'tenant_id' => $this->tenantId,
                'appointment_id' => $appointmentId,
                'patient_insurance_id' => $patientInsuranceId,
                'total_amount' => $coverage['total_amount'],
                'insurance_share' => $coverage['insurance_share'],
                'patient_share' => $coverage['patient_share'],
                'deductible' => 0,
                'status' => 'pending',
            ]);

            $appointment->update([
                'final_price' => $coverage['patient_share'],
                'discount' => $coverage['insurance_share'],
            ]);

            return $appointmentInsurance->fresh(['appointment', 'patientInsurance']);
        });
    }

    public function getAppointmentInsurance(int $appointmentId): ?AppointmentInsurance
    {
        return AppointmentInsurance::where('tenant_id', $this->tenantId)
            ->where('appointment_id', $appointmentId)
            ->with(['patientInsurance', 'patientInsurance.insurance'])
            ->first();
    }

    public function approveInsuranceClaim(int $appointmentInsuranceId): AppointmentInsurance
    {
        $appInsurance = AppointmentInsurance::where('tenant_id', $this->tenantId)
            ->findOrFail($appointmentInsuranceId);

        $appInsurance->approve();
        return $appInsurance->fresh();
    }

    public function rejectInsuranceClaim(int $appointmentInsuranceId): AppointmentInsurance
    {
        $appInsurance = AppointmentInsurance::where('tenant_id', $this->tenantId)
            ->findOrFail($appointmentInsuranceId);

        $appInsurance->reject();
        return $appInsurance->fresh();
    }

    public function getInsuranceStats(array $filters = []): array
    {
        $query = AppointmentInsurance::where('tenant_id', $this->tenantId);

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (isset($filters['insurance_id'])) {
            $query->whereHas('patientInsurance', function ($q) use ($filters) {
                $q->where('insurance_id', $filters['insurance_id']);
            });
        }

        return [
            'total_claims' => $query->count(),
            'total_approved' => (clone $query)->where('status', 'approved')->count(),
            'total_rejected' => (clone $query)->where('status', 'rejected')->count(),
            'total_pending' => (clone $query)->where('status', 'pending')->count(),
            'total_insurance_share' => (clone $query)->sum('insurance_share'),
            'total_patient_share' => (clone $query)->sum('patient_share'),
            'total_amount' => (clone $query)->sum('total_amount'),
        ];
    }

    public function getInsuranceReport(int $insuranceId, array $filters = []): array
    {
        $query = AppointmentInsurance::where('tenant_id', $this->tenantId)
            ->whereHas('patientInsurance', function ($q) use ($insuranceId) {
                $q->where('insurance_id', $insuranceId);
            });

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        $claims = $query->with(['appointment', 'patientInsurance.patient'])->get();

        return [
            'total_claims' => $claims->count(),
            'total_amount' => $claims->sum('total_amount'),
            'total_insurance_share' => $claims->sum('insurance_share'),
            'total_patient_share' => $claims->sum('patient_share'),
            'claims' => $claims,
        ];
    }
}
