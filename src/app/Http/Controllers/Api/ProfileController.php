<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Patient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    use ApiResponse;

    public function update(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('کاربر یافت نشد', 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'mobile' => 'sometimes|string|max:20|unique:users,mobile,' . $user->id,
            'national_code' => 'sometimes|string|max:10',
            'address' => 'sometimes|string|max:500',
            'insurance_type' => 'nullable|string|max:50',
            'insurance_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            // آپدیت کاربر
            $user->update($request->only(['name', 'full_name', 'email', 'mobile']));

            // آپدیت یا ایجاد پروفایل بیمار
            $patient = Patient::where('user_id', $user->id)->first();
            $patientData = [
                'national_code' => $request->national_code,
                'address' => $request->address,
                'insurance_type' => $request->insurance_type,
                'insurance_number' => $request->insurance_number,
            ];

            if ($patient) {
                $patient->update($patientData);
            } else {
                $patientData['user_id'] = $user->id;
                Patient::create($patientData);
            }

            Log::info('✅ Profile updated for user: ' . $user->id);

            return $this->success(
                $user->load('patient'),
                'اطلاعات با موفقیت به‌روزرسانی شد'
            );

        } catch (\Exception $e) {
            Log::error('❌ Profile update error: ' . $e->getMessage());
            return $this->error('خطا در به‌روزرسانی اطلاعات: ' . $e->getMessage(), 500);
        }
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('کاربر یافت نشد', 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('رمز عبور فعلی اشتباه است', 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        Log::info('✅ Password changed for user: ' . $user->id);

        return $this->success(null, 'رمز عبور با موفقیت تغییر کرد');
    }

    public function uploadAvatar(Request $request)
    {
        Log::info('📸 Upload avatar called');

        $user = $request->user();

        if (!$user) {
            Log::error('❌ User not authenticated');
            return $this->error('کاربر احراز هویت نشده است', 401);
        }

        Log::info('✅ User found: ' . $user->id . ' - ' . $user->mobile);

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            Log::error('❌ Validation failed: ' . json_encode($validator->errors()));
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            if ($user->getFirstMedia('avatar')) {
                $user->clearMediaCollection('avatar');
                Log::info('🗑️ Old avatar removed');
            }

            $media = $user->addMedia($request->file('avatar'))
                ->toMediaCollection('avatar');

            Log::info('✅ Avatar uploaded successfully: ' . $media->id);

            $user->refresh();

            // ساخت آدرس کامل برای عکس
            $avatarUrl = $user->getFirstMediaUrl('avatar', 'thumb');
            $avatarOriginal = $user->getFirstMediaUrl('avatar');

            return $this->success([
                'avatar_url' => $avatarUrl,
                'avatar_original_url' => $avatarOriginal,
                'message' => 'عکس پروفایل با موفقیت آپلود شد',
            ], 'عکس پروفایل با موفقیت آپلود شد');

        } catch (\Exception $e) {
            Log::error('❌ Upload error: ' . $e->getMessage());
            return $this->error('خطا در آپلود عکس: ' . $e->getMessage(), 500);
        }
    }

    public function getAvatar(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('کاربر یافت نشد', 401);
        }

        $avatarUrl = $user->getFirstMediaUrl('avatar', 'thumb');
        $avatarOriginal = $user->getFirstMediaUrl('avatar');

        return $this->success([
            'avatar_url' => $avatarUrl,
            'avatar_original_url' => $avatarOriginal,
            'has_avatar' => $user->getFirstMedia('avatar') ? true : false,
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('کاربر یافت نشد', 401);
        }

        if ($user->getFirstMedia('avatar')) {
            $user->clearMediaCollection('avatar');
            Log::info('🗑️ Avatar deleted for user: ' . $user->id);
        }

        return $this->success(null, 'عکس پروفایل با موفقیت حذف شد');
    }

    /**
     * دریافت اطلاعات کامل کاربر با پروفایل
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return $this->error('کاربر یافت نشد', 401);
        }

        $user->load('patient');

        return $this->success($user);
    }
}
