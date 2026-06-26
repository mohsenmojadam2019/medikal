<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
    }

    /**
     * لیست نقش‌ها
     */
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return $this->success($roles);
    }

    /**
     * ایجاد نقش جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return $this->success($role->load('permissions'), 'نقش با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش نقش
     */
    public function show($id)
    {
        $role = Role::with('permissions')->find($id);
        if (!$role) {
            return $this->error('نقش یافت نشد', 404);
        }

        return $this->success($role);
    }

    /**
     * به‌روزرسانی نقش
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if (!$role) {
            return $this->error('نقش یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name,' . $id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $role->update(['name' => $request->name]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return $this->success($role->load('permissions'), 'نقش با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف نقش
     */
    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return $this->error('نقش یافت نشد', 404);
        }

        if ($role->name === 'super_admin') {
            return $this->error('نقش سوپر ادمین قابل حذف نیست', 400);
        }

        try {
            $role->delete();
            return $this->success(null, 'نقش با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
