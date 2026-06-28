<?php

namespace App\Services\Emergency;

use App\Models\Emergency\EmergencyPatient;
use Carbon\Carbon;

class EmergencyService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function register(array $data): EmergencyPatient
    {
        $data['tenant_id'] = $this->tenantId;
        return EmergencyPatient::create($data);
    }

    public function updateStatus(int $id, string $status): EmergencyPatient
    {
        $patient = EmergencyPatient::where('tenant_id', $this->tenantId)->findOrFail($id);
        $patient->update(['status' => $status]);
        return $patient->fresh();
    }

    public function setDisposition(int $id, string $disposition): EmergencyPatient
    {
        $patient = EmergencyPatient::where('tenant_id', $this->tenantId)->findOrFail($id);
        $patient->update([
            'disposition' => $disposition,
            'disposition_time' => now(),
        ]);
        return $patient->fresh();
    }

    public function getWaitingList()
    {
        return EmergencyPatient::where('tenant_id', $this->tenantId)
            ->where('status', 'waiting')
            ->orderBy('arrival_time')
            ->with(['patient.user'])
            ->get();
    }

    public function getTriageStats(): array
    {
        return [
            'red' => EmergencyPatient::where('tenant_id', $this->tenantId)->where('triage_level', 'red')->count(),
            'yellow' => EmergencyPatient::where('tenant_id', $this->tenantId)->where('triage_level', 'yellow')->count(),
            'green' => EmergencyPatient::where('tenant_id', $this->tenantId)->where('triage_level', 'green')->count(),
            'blue' => EmergencyPatient::where('tenant_id', $this->tenantId)->where('triage_level', 'blue')->count(),
            'total' => EmergencyPatient::where('tenant_id', $this->tenantId)->count(),
            'today' => EmergencyPatient::where('tenant_id', $this->tenantId)->whereDate('arrival_time', today())->count(),
        ];
    }
}
