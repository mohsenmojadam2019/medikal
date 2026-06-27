<?php

namespace App\Services\MedicalNote;

use App\Models\MedicalNote;
use Illuminate\Support\Facades\Auth;

class MedicalNoteService
{
    public function getAll($filters = [])
    {
        $query = MedicalNote::query();
        
        if (isset($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }
        
        if (isset($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }
        
        if (isset($filters['appointment_id'])) {
            $query->where('appointment_id', $filters['appointment_id']);
        }
        
        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    public function find($id)
    {
        return MedicalNote::with(['patient', 'doctor', 'appointment'])->find($id);
    }

    public function create(array $data)
    {
        $data['doctor_id'] = $data['doctor_id'] ?? Auth::id();
        return MedicalNote::create($data);
    }

    public function update($id, array $data)
    {
        $note = MedicalNote::find($id);
        if (!$note) {
            return null;
        }
        $note->update($data);
        return $note;
    }

    public function delete($id)
    {
        $note = MedicalNote::find($id);
        if (!$note) {
            return false;
        }
        return $note->delete();
    }

    public function getPatientNotes($patientId)
    {
        return MedicalNote::where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getDoctorNotes($doctorId)
    {
        return MedicalNote::where('doctor_id', $doctorId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
