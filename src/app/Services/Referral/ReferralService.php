<?php

namespace App\Services\Referral;

use App\Models\Referral;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function create(array $data): Referral
    {
        return DB::transaction(function () use ($data) {
            $data['tenant_id'] = $this->tenantId;
            $referral = Referral::create($data);
            return $referral->load(['patient.user', 'fromDoctor.user', 'toDoctor.user']);
        });
    }

    public function getPatientReferrals($patientId)
    {
        return Referral::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['fromDoctor.user', 'toDoctor.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getDoctorReferrals($doctorId, $type = 'incoming')
    {
        $query = Referral::where('tenant_id', $this->tenantId)
            ->with(['patient.user', 'fromDoctor.user', 'toDoctor.user']);

        if ($type === 'incoming') {
            $query->where('to_doctor_id', $doctorId);
        } else {
            $query->where('from_doctor_id', $doctorId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function accept($referralId): Referral
    {
        $referral = Referral::where('tenant_id', $this->tenantId)->findOrFail($referralId);
        $referral->accept();
        return $referral->fresh();
    }

    public function reject($referralId): Referral
    {
        $referral = Referral::where('tenant_id', $this->tenantId)->findOrFail($referralId);
        $referral->reject();
        return $referral->fresh();
    }

    public function complete($referralId): Referral
    {
        $referral = Referral::where('tenant_id', $this->tenantId)->findOrFail($referralId);
        $referral->complete();
        return $referral->fresh();
    }
}
