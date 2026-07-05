<?php
// app/Http/Controllers/Admin/ProfileController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * نمایش پروفایل کاربر جاری
     */
    public function show(Request $request)
    {
        $user = $request->user()->load(['roles']);

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'avatar_url' => $user->getFirstMediaUrl('avatar'),
            'roles' => $user->roles->pluck('name'),
            'last_login_at' => $user->last_login_at,
            'last_login_ip' => $user->last_login_ip,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * به‌روزرسانی پروفایل
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'mobile' => 'sometimes|regex:/^09[0-9]{9}$/|unique:users,mobile,' . $user->id,
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $data = $request->only(['name', 'email', 'mobile']);
            $user->update($data);

            return $this->success([
                'user' => $user->fresh(),
            ], 'پروفایل با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آپلود عکس پروفایل با Media Library
     */
    public function uploadAvatar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('عکس نامعتبر است', 422, $validator->errors());
        }

        try {
            $user = $request->user();

            // حذف عکس قبلی با Media Library
            $user->clearMediaCollection('avatar');

            // ذخیره عکس جدید با Media Library
            $media = $user->addMedia($request->file('avatar'))
                ->toMediaCollection('avatar');

            return $this->success([
                'avatar_url' => $media->getUrl(),
            ], 'عکس پروفایل با موفقیت آپلود شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف عکس پروفایل
     */
    public function deleteAvatar(Request $request)
    {
        try {
            $user = $request->user();
            $user->clearMediaCollection('avatar');

            return $this->success(null, 'عکس پروفایل با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر رمز عبور
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('رمز عبور فعلی اشتباه است', 400);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return $this->success(null, 'رمز عبور با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * فعالیت‌های اخیر کاربر
     */
    public function activities(Request $request)
    {
        try {
            // نمونه فعالیت‌ها (می‌توانید از سیستم لاگ یا مدل Activity استفاده کنید)
            $activities = [
                [
                    'id' => 1,
                    'type' => 'login',
                    'description' => 'ورود به سیستم',
                    'ip' => $request->ip(),
                    'created_at' => now()->subMinutes(5),
                ],
                [
                    'id' => 2,
                    'type' => 'update',
                    'description' => 'بروزرسانی اطلاعات کلینیک',
                    'created_at' => now()->subHours(2),
                ],
                [
                    'id' => 3,
                    'type' => 'create',
                    'description' => 'ایجاد نوبت جدید',
                    'created_at' => now()->subDays(1),
                ],
            ];

            return $this->success($activities);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
