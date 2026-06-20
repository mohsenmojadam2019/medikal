<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\RoleService;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * لیست نقش‌ها
     */
    public function index()
    {
        $roles = $this->roleService->getRoles();
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * نمایش فرم ایجاد نقش
     */
    public function create()
    {
        $permissions = Permission::all();
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * ذخیره نقش جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = $this->roleService->createRole($request->name);

        if ($request->has('permissions')) {
            $this->roleService->syncPermissions($role, $request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت ایجاد شد.');
    }

    /**
     * نمایش فرم ویرایش نقش
     */
    public function edit(int $id)
    {
        $role = $this->roleService->getRole($id);

        if (!$role) {
            abort(404, 'نقش یافت نشد.');
        }

        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * بروزرسانی نقش
     */
    public function update(Request $request, int $id)
    {
        $role = $this->roleService->getRole($id);

        if (!$role) {
            abort(404, 'نقش یافت نشد.');
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $this->roleService->updateRole($role, $request->name);

        if ($request->has('permissions')) {
            $this->roleService->syncPermissions($role, $request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت بروزرسانی شد.');
    }

    /**
     * حذف نقش
     */
    public function destroy(int $id)
    {
        $role = $this->roleService->getRole($id);

        if (!$role) {
            abort(404, 'نقش یافت نشد.');
        }

        $this->roleService->deleteRole($role);

        return redirect()->route('admin.roles.index')
            ->with('success', 'نقش با موفقیت حذف شد.');
    }

    /**
     * مدیریت مجوزهای نقش
     */
    public function permissions(int $id)
    {
        $role = $this->roleService->getRole($id);

        if (!$role) {
            abort(404, 'نقش یافت نشد.');
        }

        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * همگام‌سازی مجوزها
     */
    public function syncPermissions(Request $request, int $id)
    {
        $role = $this->roleService->getRole($id);

        if (!$role) {
            abort(404, 'نقش یافت نشد.');
        }

        $request->validate([
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $this->roleService->syncPermissions($role, $request->permissions ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'مجوزهای نقش با موفقیت بروزرسانی شد.');
    }
}
