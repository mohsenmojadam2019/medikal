<?php

namespace App\Services\Prescription;

use App\Models\Prescription;
use App\Models\Appointment;
use App\Enums\PrescriptionStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrescriptionService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Prescription::where('tenant_id', $this->tenantId)
            ->with(['patient.user', 'doctor.user', 'appointment']);

        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('drug_name', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('diagnosis', 'LIKE', "%{$filters['search']}%");
            });
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createFromAppointment(Appointment $appointment, array $data): Prescription
    {
        return DB::transaction(function () use ($appointment, $data) {
            $startDate = $data['start_date'] ?? now()->toDateString();
            $duration = $data['duration'] ?? 7;

            $prescription = Prescription::create([
                'tenant_id' => $this->tenantId,
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'drug_name' => $data['drug_name'],
                'dosage' => $data['dosage'],
                'frequency' => $data['frequency'] ?? 3,
                'duration' => $duration,
                'start_date' => $startDate,
                'end_date' => now()->parse($startDate)->addDays($duration)->toDateString(),
                'instructions' => $data['instructions'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => PrescriptionStatusEnum::ACTIVE,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->generateReminders($prescription);

            return $prescription;
        });
    }

    public function show(int $id): Prescription
    {
        return Prescription::where('tenant_id', $this->tenantId)
            ->with([
                'patient.user',
                'doctor.user',
                'appointment'
            ])
            ->findOrFail($id);
    }

    public function update(Prescription $prescription, array $data): Prescription
    {
        return DB::transaction(function () use ($prescription, $data) {
            $prescription->update($data);

            if (isset($data['start_date']) || isset($data['duration'])) {
                $startDate = $data['start_date'] ?? $prescription->start_date;
                $duration = $data['duration'] ?? $prescription->duration;
                $prescription->update([
                    'end_date' => now()->parse($startDate)->addDays($duration)->toDateString()
                ]);
            }

            return $prescription->fresh();
        });
    }

    public function changeStatus(Prescription $prescription, string $status): Prescription
    {
        $method = match ($status) {
            'activate' => 'activate',
            'complete' => 'complete',
            'cancel' => 'cancel',
            'expire' => 'expire',
            default => throw new \Exception('وضعیت نامعتبر است'),
        };

        $prescription->$method();
        return $prescription->fresh();
    }

    public function delete(Prescription $prescription): void
    {
        $prescription->delete();
    }

    public function patientPrescriptions(int $patientId, array $filters = [], int $perPage = 15)
    {
        $query = Prescription::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['doctor.user']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['active']) && $filters['active']) {
            $query->active();
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function doctorPrescriptions(int $doctorId, array $filters = [], int $perPage = 15)
    {
        $query = Prescription::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->with(['patient.user']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function checkInteractions(Prescription $prescription): array
    {
        return $prescription->getDrugInteractions();
    }

    public function generateReminders(Prescription $prescription): array
    {
        $reminders = $prescription->generateReminders();

        Log::info('Reminders generated for prescription', [
            'tenant_id' => $this->tenantId,
            'prescription_id' => $prescription->id,
            'patient_id' => $prescription->patient_id,
            'count' => count($reminders),
        ]);

        return $reminders;
    }

    public function getExpiringSoon(int $days = 3)
    {
        return Prescription::where('tenant_id', $this->tenantId)
            ->expiringSoon($days)
            ->with(['patient.user', 'doctor.user'])
            ->get();
    }

    public function getExpired()
    {
        return Prescription::where('tenant_id', $this->tenantId)
            ->expired()
            ->with(['patient.user'])
            ->get();
    }

    public function getStats(): array
    {
        return [
            'total' => Prescription::where('tenant_id', $this->tenantId)->count(),
            'active' => Prescription::where('tenant_id', $this->tenantId)->active()->count(),
            'pending' => Prescription::where('tenant_id', $this->tenantId)->pending()->count(),
            'completed' => Prescription::where('tenant_id', $this->tenantId)->where('status', PrescriptionStatusEnum::COMPLETED)->count(),
            'cancelled' => Prescription::where('tenant_id', $this->tenantId)->where('status', PrescriptionStatusEnum::CANCELLED)->count(),
            'expired' => Prescription::where('tenant_id', $this->tenantId)->where('status', PrescriptionStatusEnum::EXPIRED)->count(),
            'expiring_soon' => Prescription::where('tenant_id', $this->tenantId)->expiringSoon()->count(),
        ];
    }

    public function getPrintData(Prescription $prescription): array
    {
        return [
            'prescription' => $prescription->load(['patient.user', 'doctor.user']),
            'patient' => [
                'name' => $prescription->patient->full_name,
                'national_code' => $prescription->patient->national_code,
                'phone' => $prescription->patient->phone,
            ],
            'doctor' => [
                'name' => $prescription->doctor->full_name,
                'specialty' => $prescription->doctor->specialty?->name,
                'license_number' => $prescription->doctor->license_number,
            ],
            'details' => [
                'drug' => $prescription->drug_name,
                'dosage' => $prescription->dosage,
                'frequency' => $prescription->frequency_label,
                'duration' => $prescription->duration . ' روز',
                'start_date' => $prescription->start_date->format('Y/m/d'),
                'end_date' => $prescription->end_date->format('Y/m/d'),
                'instructions' => $prescription->instructions,
                'diagnosis' => $prescription->diagnosis,
            ],
            'daily_times' => $prescription->daily_times,
        ];
    }
}
