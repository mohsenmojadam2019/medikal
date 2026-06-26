<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\User\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use ApiResponse;

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * تغییر رمز عبور
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|min:6',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $this->userService->changePassword(
                $request->user(),
                $request->old_password,
                $request->new_password
            );
            return $this->success(null, 'رمز عبور با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * به‌روزرسانی اطلاعات کاربر
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'mobile' => 'sometimes|regex:/^09[0-9]{9}$/|unique:users,mobile,' . $request->user()->id,
            'address_line_1' => 'sometimes|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'neighborhood' => 'nullable|string|max:100',
            'province_id' => 'sometimes|exists:provinces,id',
            'city_id' => 'sometimes|exists:cities,id',
            'postal_code' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $user = $request->user();

            // اطلاعات پایه
            $userData = $request->only(['name', 'email', 'mobile']);
            $userData['is_active'] = $user->is_active;

            // آدرس
            $addressData = $request->only([
                'address_line_1', 'address_line_2', 'neighborhood',
                'province_id', 'city_id', 'postal_code'
            ]);

            // به‌روزرسانی کاربر
            if (!empty($userData)) {
                $user->update($userData);
            }

            // به‌روزرسانی آدرس
            if (!empty($addressData)) {
                $address = $user->primaryAddress;
                if ($address) {
                    $address->update($addressData);
                } else {
                    $user->addresses()->create(array_merge($addressData, ['is_primary' => true]));
                }
            }

            $user->load('primaryAddress', 'primaryAddress.province', 'primaryAddress.city');

            return $this->success([
                'user' => $user,
                'roles' => $user->getRoleNames(),
            ], 'اطلاعات با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * به‌روزرسانی آدرس
     */
    public function updateAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_line_1' => 'required|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'neighborhood' => 'nullable|string|max:100',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'postal_code' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $user = $request->user();
            $addressData = $request->all();

            $address = $user->primaryAddress;
            if ($address) {
                $address->update($addressData);
            } else {
                $user->addresses()->create(array_merge($addressData, ['is_primary' => true]));
            }

            return $this->success(null, 'آدرس با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
