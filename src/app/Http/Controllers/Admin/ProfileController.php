<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    /**
     * نمایش پروفایل کاربر
     */
    public function index()
    {
        $user = Auth::user();
        return view('admin.profile.index', compact('user'));
    }

    /**
     * بروزرسانی پروفایل
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('admin.profile')
            ->with('profile_success', 'اطلاعات پروفایل با موفقیت بروزرسانی شد.');
    }

    /**
     * بروزرسانی رمز عبور
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->route('admin.profile')
                ->with('password_error', 'رمز عبور فعلی اشتباه است.');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.profile')
            ->with('password_success', 'رمز عبور با موفقیت تغییر کرد.');
    }

    /**
     * آپلود عکس پروفایل
     */
    public function uploadAvatar(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        // حذف عکس قبلی
        $user->clearMediaCollection('avatar');

        // ذخیره عکس جدید
        $user->addMedia($request->file('avatar'))
            ->toMediaCollection('avatar');

        return response()->json([
            'success' => true,
            'message' => 'عکس پروفایل با موفقیت آپلود شد.',
            'avatar' => $user->getFirstMediaUrl('avatar'),
        ]);
    }
}
