<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DoctorSocialController extends Controller
{
    use ApiResponse;

    public function updateSocialLinks(Request $request, $doctorId)
    {
        $user = auth()->user();
        $doctor = Doctor::findOrFail($doctorId);

        if (!$user->isAdmin() && $doctor->user_id != $user->id) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $validator = Validator::make($request->all(), [
            'telegram' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'aparat' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $socialLinks = $doctor->social_links ?? [];
            $socialLinks = array_merge($socialLinks, $request->all());
            
            $doctor->update(['social_links' => $socialLinks]);

            return $this->success($doctor->social_links, 'لینک‌های اجتماعی با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function getSocialLinks($doctorId)
    {
        try {
            $doctor = Doctor::findOrFail($doctorId);
            return $this->success($doctor->social_links ?? []);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
