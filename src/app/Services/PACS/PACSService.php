<?php

namespace App\Services\PACS;

use App\Models\PACS\MedicalImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PACSService
{
    public function uploadImage(array $data, $file): MedicalImage
    {
        $path = $file->store('pacs/' . $data['patient_id'], 'public');

        return MedicalImage::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'] ?? null,
            'admission_id' => $data['admission_id'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'image_type' => $data['image_type'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'study_uid' => $data['study_uid'] ?? null,
            'series_uid' => $data['series_uid'] ?? null,
            'instance_uid' => $data['instance_uid'] ?? null,
            'body_part' => $data['body_part'] ?? null,
            'modality' => $data['modality'] ?? null,
            'description' => $data['description'] ?? null,
            'study_date' => $data['study_date'] ?? now(),
            'report' => $data['report'] ?? null,
            'is_confidential' => $data['is_confidential'] ?? false,
            'uploaded_by' => auth()->id(),
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function getPatientImages(int $patientId, array $filters = [], int $perPage = 20)
    {
        $query = MedicalImage::where('patient_id', $patientId);

        if (isset($filters['image_type'])) {
            $query->where('image_type', $filters['image_type']);
        }

        if (isset($filters['modality'])) {
            $query->where('modality', $filters['modality']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('study_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('study_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('study_date', 'desc')->paginate($perPage);
    }

    public function deleteImage(int $imageId): void
    {
        $image = MedicalImage::findOrFail($imageId);
        Storage::disk('public')->delete($image->file_path);
        $image->delete();
    }

    public function getImageStats(int $patientId): array
    {
        return [
            'total' => MedicalImage::where('patient_id', $patientId)->count(),
            'by_type' => MedicalImage::where('patient_id', $patientId)
                ->selectRaw('image_type, count(*) as count')
                ->groupBy('image_type')
                ->get()
                ->pluck('count', 'image_type')
                ->toArray(),
            'by_modality' => MedicalImage::where('patient_id', $patientId)
                ->selectRaw('modality, count(*) as count')
                ->groupBy('modality')
                ->get()
                ->pluck('count', 'modality')
                ->toArray(),
        ];
    }
}
