<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\User\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use ApiResponse;

    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * لیست کاربران
     */
    public function index(Request $request)
    {
        $users = $this->userService->list($request->all(), $request->get('per_page', 15));
        return $this->success($users);
    }

    /**
     * ایجاد کاربر جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users',
            'mobile' => 'required|regex:/^09[0-9]{9}$/|unique:users',
            'password' => 'nullable|min:6',
            'role' => 'required|exists:roles,name',
            'is_active' => 'nullable|boolean',
            'address_line_1' => 'nullable|string|max:500',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['address'] = $request->only([
                'address_line_1', 'address_line_2', 'neighborhood',
                'province_id', 'city_id', 'postal_code'
            ]);

            $user = $this->userService->create($data);
            return $this->success($user, 'کاربر با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش کاربر
     */
    public function show($id)
    {
        $user = User::with(['primaryAddress', 'primaryAddress.province', 'primaryAddress.city'])->find($id);
        if (!$user) {
            return $this->error('کاربر یافت نشد', 404);
        }

        return $this->success([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * به‌روزرسانی کاربر
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('کاربر یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'mobile' => 'sometimes|regex:/^09[0-9]{9}$/|unique:users,mobile,' . $id,
            'role' => 'sometimes|exists:roles,name',
            'is_active' => 'nullable|boolean',
            'address_line_1' => 'nullable|string|max:500',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['address'] = $request->only([
                'address_line_1', 'address_line_2', 'neighborhood',
                'province_id', 'city_id', 'postal_code'
            ]);

            $user = $this->userService->update($user, $data);
            return $this->success($user, 'کاربر با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف کاربر
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('کاربر یافت نشد', 404);
        }

        try {
            $this->userService->delete($user);
            return $this->success(null, 'کاربر با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت کاربر
     */
    public function toggleStatus($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('کاربر یافت نشد', 404);
        }

        try {
            $user = $this->userService->toggleStatus($user);
            return $this->success($user, 'وضعیت کاربر با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * اختصاص نقش به کاربر
     */
    public function assignRole(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('کاربر یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $user->syncRoles([$request->role]);
            return $this->success([
                'user' => $user,
                'roles' => $user->getRoleNames(),
            ], 'نقش با موفقیت اختصاص داده شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
