<?php

namespace App\Services\Vaccination;

use App\Models\Vaccine;
use App\Models\PatientVaccination;
use App\Models\VaccinationReminder;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VaccinationService
{
    public function getVaccines(array $filters = [], int $perPage = 20)
    {
        $query = Vaccine::query();

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%")
                ->orWhere('disease', 'LIKE', "%{$filters['search']}%")
                ->orWhere('manufacturer', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_required'])) {
            $query->where('is_required', $filters['is_required']);
        }

        if (isset($filters['disease'])) {
            $query->byDisease($filters['disease']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getActiveVaccines()
    {
        return Vaccine::active()->orderBy('name')->get();
    }

    public function createVaccine(array $data): Vaccine
    {
        return Vaccine::create($data);
    }

    public function updateVaccine(Vaccine $vaccine, array $data): Vaccine
    {
        $vaccine->update($data);
        return $vaccine->fresh();
    }

    public function deleteVaccine(Vaccine $vaccine): void
    {
        $vaccine->delete();
    }

    public function toggleVaccineStatus(Vaccine $vaccine): Vaccine
    {
        $vaccine->update(['is_active' => !$vaccine->is_active]);
        return $vaccine->fresh();
    }

    public function recordVaccination(array $data): PatientVaccination
    {
        return DB::transaction(function () use ($data) {
            $vaccine = Vaccine::findOrFail($data['vaccine_id']);
            $patient = Patient::findOrFail($data['patient_id']);

            $nextDueDate = null;
            if (isset($data['dose_number']) && $data['dose_number'] < $vaccine->doses_required) {
                $nextDueDate = Carbon::parse($data['administration_date'])
                    ->addDays($vaccine->interval_days ?? 30);
            }

            $vaccination = PatientVaccination::create([
                'patient_id' => $data['patient_id'],
                'vaccine_id' => $data['vaccine_id'],
                'doctor_id' => $data['doctor_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'dose_number' => $data['dose_number'] ?? 1,
                'administration_date' => $data['administration_date'] ?? now(),
                'next_due_date' => $nextDueDate,
                'batch_number' => $data['batch_number'] ?? null,
                'administration_site' => $data['administration_site'] ?? null,
                'status' => 'completed',
                'reaction_notes' => $data['reaction_notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            if ($nextDueDate) {
                $this->createReminder([
                    'patient_id' => $patient->id,
                    'vaccine_id' => $vaccine->id,
                    'patient_vaccination_id' => $vaccination->id,
                    'reminder_date' => $nextDueDate->subDays(7),
                    'type' => 'next_dose',
                    'message' => "یادآوری دوز بعدی واکسن {$vaccine->name} برای بیمار {$patient->full_name}",
                ]);
            }

            return $vaccination->fresh(['patient', 'vaccine', 'doctor']);
        });
    }

    public function getPatientVaccinations(int $patientId, array $filters = [], int $perPage = 20)
    {
        $query = PatientVaccination::with(['vaccine', 'doctor'])
            ->byPatient($patientId);

        if (isset($filters['vaccine_id'])) {
            $query->byVaccine($filters['vaccine_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('administration_date', 'desc')->paginate($perPage);
    }

    public function getPatientVaccinationSummary(int $patientId): array
    {
        $vaccinations = PatientVaccination::with(['vaccine'])
            ->byPatient($patientId)
            ->completed()
            ->get();

        $summary = [];
        foreach ($vaccinations as $vaccination) {
            $key = $vaccination->vaccine_id;
            if (!isset($summary[$key])) {
                $summary[$key] = [
                    'vaccine' => $vaccination->vaccine,
                    'doses_received' => 0,
                    'total_required' => $vaccination->vaccine->doses_required,
                    'last_dose_date' => null,
                    'next_due_date' => null,
                    'is_complete' => false,
                ];
            }
            $summary[$key]['doses_received']++;
            $summary[$key]['last_dose_date'] = $vaccination->administration_date;
            $summary[$key]['next_due_date'] = $vaccination->next_due_date;
            $summary[$key]['is_complete'] = $summary[$key]['doses_received'] >= $summary[$key]['total_required'];
        }

        return array_values($summary);
    }

    public function getUpcomingVaccinations(int $patientId, int $days = 30)
    {
        return PatientVaccination::with(['vaccine'])
            ->byPatient($patientId)
            ->where('status', 'scheduled')
            ->whereDate('next_due_date', '<=', now()->addDays($days))
            ->orderBy('next_due_date')
            ->get();
    }

    public function getOverdueVaccinations(int $patientId)
    {
        return PatientVaccination::with(['vaccine'])
            ->byPatient($patientId)
            ->where('status', 'scheduled')
            ->whereDate('next_due_date', '<', now())
            ->orderBy('next_due_date')
            ->get();
    }

    public function createReminder(array $data): VaccinationReminder
    {
        return VaccinationReminder::create($data);
    }

    public function getPatientReminders(int $patientId, array $filters = [], int $perPage = 20)
    {
        $query = VaccinationReminder::with(['vaccine'])
            ->byPatient($patientId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('reminder_date', 'asc')->paginate($perPage);
    }

    public function processReminders(): int
    {
        $reminders = VaccinationReminder::due()->get();
        $count = 0;

        foreach ($reminders as $reminder) {
            try {
                Log::info('Vaccination reminder sent', [
                    'patient_id' => $reminder->patient_id,
                    'vaccine_id' => $reminder->vaccine_id,
                    'reminder_date' => $reminder->reminder_date,
                ]);
                $reminder->markAsSent();
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to send vaccination reminder: ' . $e->getMessage());
            }
        }

        return $count;
    }

    public function getStats(array $filters = []): array
    {
        $query = PatientVaccination::query();

        if (isset($filters['from_date'])) {
            $query->whereDate('administration_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('administration_date', '<=', $filters['to_date']);
        }

        if (isset($filters['vaccine_id'])) {
            $query->byVaccine($filters['vaccine_id']);
        }

        return [
            'total_vaccinations' => $query->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'scheduled' => (clone $query)->where('status', 'scheduled')->count(),
            'missed' => (clone $query)->where('status', 'missed')->count(),
            'overdue' => (clone $query)->where('status', 'scheduled')
                ->whereDate('next_due_date', '<', now())
                ->count(),
            'by_vaccine' => $this->getVaccinationStatsByVaccine($filters),
            'by_month' => $this->getVaccinationStatsByMonth($filters),
        ];
    }

    private function getVaccinationStatsByVaccine(array $filters): array
    {
        $query = PatientVaccination::with(['vaccine']);

        if (isset($filters['from_date'])) {
            $query->whereDate('administration_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('administration_date', '<=', $filters['to_date']);
        }

        return $query->get()
            ->groupBy('vaccine_id')
            ->map(function ($items) {
                $vaccine = $items->first()->vaccine;
                return [
                    'vaccine_name' => $vaccine->name,
                    'count' => $items->count(),
                ];
            })
            ->values()
            ->toArray();
    }

    private function getVaccinationStatsByMonth(array $filters): array
    {
        $query = PatientVaccination::where('status', 'completed');

        if (isset($filters['from_date'])) {
            $query->whereDate('administration_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('administration_date', '<=', $filters['to_date']);
        }

        return $query->get()
            ->groupBy(function ($item) {
                return $item->administration_date->format('Y-m');
            })
            ->map(function ($items, $month) {
                return [
                    'month' => $month,
                    'count' => $items->count(),
                ];
            })
            ->values()
            ->toArray();
    }
}
