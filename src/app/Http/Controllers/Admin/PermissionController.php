<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
    }

    /**
     * لیست مجوزها
     */
    public function index()
    {
        $permissions = Permission::all();
        return $this->success($permissions);
    }

    /**
     * ایجاد مجوز جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:permissions,name',
            'guard_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $permission = Permission::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web',
            ]);

            return $this->success($permission, 'مجوز با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * به‌روزرسانی مجوز
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);
        if (!$permission) {
            return $this->error('مجوز یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:permissions,name,' . $id,
            'guard_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $permission->update([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web',
            ]);

            return $this->success($permission, 'مجوز با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف مجوز
     */
    public function destroy($id)
    {
        $permission = Permission::find($id);
        if (!$permission) {
            return $this->error('مجوز یافت نشد', 404);
        }

        try {
            $permission->delete();
            return $this->success(null, 'مجوز با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * اختصاص مجوز به نقش
     */
    public function assignToRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'permission' => 'required|exists:permissions,name',
            'role' => 'required|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            $permission = Permission::findByName($request->permission);
            $role = Role::findByName($request->role);
            $role->givePermissionTo($permission);

            return $this->success(null, 'مجوز با موفقیت به نقش اختصاص داده شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
