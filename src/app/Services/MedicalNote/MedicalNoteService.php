<?php

namespace App\Services\MedicalNote;

use App\Models\MedicalNote;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MedicalNoteService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getNotes(array $filters = [], int $perPage = 20)
    {
        $query = MedicalNote::where('tenant_id', $this->tenantId)
            ->with(['patient', 'doctor', 'appointment']);

        if (isset($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (isset($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (isset($filters['appointment_id'])) {
            $query->where('appointment_id', $filters['appointment_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('note_status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%")
                    ->orWhere('subjective', 'LIKE', "%{$search}%")
                    ->orWhere('assessment', 'LIKE', "%{$search}%")
                    ->orWhere('plan', 'LIKE', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getNote($id): MedicalNote
    {
        return MedicalNote::where('tenant_id', $this->tenantId)
            ->with(['patient', 'doctor', 'appointment'])
            ->findOrFail($id);
    }

    public function createNote(array $data): MedicalNote
    {
        return DB::transaction(function () use ($data) {
            $data['tenant_id'] = $this->tenantId;
            
            // اگر doctor_id وارد نشده، از کاربر فعلی استفاده کن
            if (!isset($data['doctor_id'])) {
                $doctor = Doctor::where('user_id', Auth::id())->first();
                $data['doctor_id'] = $doctor?->id;
            }

            // اگر patient_id وارد نشده، از appointment بگیر
            if (!isset($data['patient_id']) && isset($data['appointment_id'])) {
                $appointment = \App\Models\Appointment::find($data['appointment_id']);
                $data['patient_id'] = $appointment?->patient_id;
            }

            return MedicalNote::create($data);
        });
    }

    public function updateNote(MedicalNote $note, array $data): MedicalNote
    {
        return DB::transaction(function () use ($note, $data) {
            $note->update($data);
            return $note->fresh();
        });
    }

    public function deleteNote(MedicalNote $note): bool
    {
        return $note->delete();
    }

    public function getPatientNotes(int $patientId, array $filters = [], int $perPage = 20)
    {
        $query = MedicalNote::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['doctor', 'appointment']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('note_status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getDoctorNotes(int $doctorId, array $filters = [], int $perPage = 20)
    {
        $query = MedicalNote::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->with(['patient', 'appointment']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getPatientNoteSummary(int $patientId): array
    {
        $notes = MedicalNote::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->where('note_status', 'final')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'total_notes' => $notes->count(),
            'by_type' => $notes->groupBy('type')->map->count(),
            'latest_notes' => $notes->take(5),
            'diagnoses' => $notes->pluck('diagnoses')->filter()->flatten(1)->unique('name')->values(),
            'prescriptions' => $notes->pluck('prescriptions')->filter()->flatten(1)->unique('name')->values(),
            'lab_requests' => $notes->pluck('lab_requests')->filter()->flatten(1)->unique('name')->values(),
            'imaging_requests' => $notes->pluck('imaging_requests')->filter()->flatten(1)->unique('name')->values(),
            'referrals' => $notes->pluck('referrals')->filter()->flatten(1)->unique('to_doctor')->values(),
        ];
    }

    public function shareNote(int $noteId): MedicalNote
    {
        $note = $this->getNote($noteId);
        $note->markAsShared();
        return $note;
    }

    public function unshareNote(int $noteId): MedicalNote
    {
        $note = $this->getNote($noteId);
        $note->update([
            'is_shared' => false,
            'note_status' => 'final',
        ]);
        return $note;
    }
}
