<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        $user->update($request->only(['name', 'full_name', 'email']));

        return $this->success($user->fresh(), 'اطلاعات با موفقیت به‌روزرسانی شد');
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

        if (!\Hash::check($request->current_password, $user->password)) {
            return $this->error('رمز عبور فعلی اشتباه است', 400);
        }

        $user->update([
            'password' => \Hash::make($request->new_password),
        ]);

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
        }

        return $this->success(null, 'عکس پروفایل با موفقیت حذف شد');
    }
}
