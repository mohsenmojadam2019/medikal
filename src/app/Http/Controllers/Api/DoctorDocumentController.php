<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorDocumentController extends Controller
{
    use ApiResponse;

    public function uploadCertificate(Request $request, $doctorId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $validator = Validator::make($request->all(), [
            'certificate' => 'required|file|max:5120',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $media = $doctor->addMediaFromRequest('certificate')
                ->withCustomProperties([
                    'title' => $request->title,
                    'description' => $request->description,
                ])
                ->toMediaCollection('certificates');

            return $this->success([
                'id' => $media->id,
                'name' => $media->file_name,
                'size' => $media->size,
                'url' => $media->getUrl(),
                'title' => $request->title,
                'description' => $request->description,
            ], 'مدرک با موفقیت آپلود شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getCertificates($doctorId)
    {
        try {
            $doctor = Doctor::findOrFail($doctorId);
            $certificates = $doctor->getMedia('certificates')->map(function ($media) {
                return [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                    'thumb' => $media->getUrl('thumb'),
                    'title' => $media->getCustomProperty('title'),
                    'description' => $media->getCustomProperty('description'),
                    'created_at' => $media->created_at,
                ];
            });

            return $this->success($certificates);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function deleteCertificate($doctorId, $mediaId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        try {
            $media = $doctor->getMedia('certificates')->where('id', $mediaId)->first();
            if (!$media) {
                return $this->error('مدرک یافت نشد', 404);
            }
            $media->delete();
            return $this->success(null, 'مدرک با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
