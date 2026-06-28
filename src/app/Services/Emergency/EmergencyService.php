<?php

namespace App\Services\Emergency;

use App\Models\Emergency\EmergencyPatient;
use Carbon\Carbon;

class EmergencyService
{
    public function register(array $data): EmergencyPatient
    {
        return EmergencyPatient::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'] ?? null,
            'admission_id' => $data['admission_id'] ?? null,
            'triage_level' => $data['triage_level'] ?? 'green',
            'arrival_time' => $data['arrival_time'] ?? now(),
            'chief_complaint' => $data['chief_complaint'] ?? null,
            'history_of_present_illness' => $data['history_of_present_illness'] ?? null,
            'vital_signs' => $data['vital_signs'] ?? null,
            'allergies' => $data['allergies'] ?? null,
            'medications' => $data['medications'] ?? null,
            'past_medical_history' => $data['past_medical_history'] ?? null,
            'status' => 'waiting',
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function updateStatus(int $id, string $status): EmergencyPatient
    {
        $patient = EmergencyPatient::findOrFail($id);
        $patient->update(['status' => $status]);
        return $patient->fresh();
    }

    public function setDisposition(int $id, string $disposition): EmergencyPatient
    {
        $patient = EmergencyPatient::findOrFail($id);
        $patient->update([
            'disposition' => $disposition,
            'disposition_time' => now(),
        ]);
        return $patient->fresh();
    }

    public function getWaitingList()
    {
        return EmergencyPatient::where('status', 'waiting')
            ->orderBy('arrival_time')
            ->with(['patient.user'])
            ->get();
    }

    public function getTriageStats(): array
    {
        return [
            'red' => EmergencyPatient::where('triage_level', 'red')->count(),
            'yellow' => EmergencyPatient::where('triage_level', 'yellow')->count(),
            'green' => EmergencyPatient::where('triage_level', 'green')->count(),
            'blue' => EmergencyPatient::where('triage_level', 'blue')->count(),
            'total' => EmergencyPatient::count(),
            'today' => EmergencyPatient::whereDate('arrival_time', today())->count(),
        ];
    }
}
