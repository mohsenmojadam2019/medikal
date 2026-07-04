<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DoctorProfileController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
    }

    /**
     * به‌روزرسانی پروفایل پزشک
     */
    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        $request->validate([
            'bio' => 'nullable|string|max:1000',
            'clinic_name' => 'nullable|string|max:255',
            'clinic_address' => 'nullable|string|max:500',
            'clinic_phone' => 'nullable|string|max:20',
            'clinic_email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'experience_years' => 'nullable|integer|min:0|max:100',
            'consultation_fee' => 'nullable|numeric|min:0',
            'education' => 'nullable|array',
            'certificates' => 'nullable|array',
            'social_links' => 'nullable|array',
            'working_hours' => 'nullable|array',
            'profile_image' => 'nullable|image|max:2048', // 2MB
        ]);

        try {
            $data = $request->except(['profile_image']);

            // آپلود عکس پروفایل
            if ($request->hasFile('profile_image')) {
                $path = $request->file('profile_image')->store('doctors', 'public');
                $data['profile_image'] = $path;
            }

            $doctor->update($data);

            return $this->success($doctor->fresh(), 'پروفایل پزشک با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید پزشک
     */
    public function verify($id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->update(['is_verified' => true]);

        return $this->success($doctor, 'پزشک با موفقیت تایید شد');
    }

    /**
     * لغو تایید پزشک
     */
    public function unverify($id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->update(['is_verified' => false]);

        return $this->success($doctor, 'تایید پزشک با موفقیت لغو شد');
    }
}
