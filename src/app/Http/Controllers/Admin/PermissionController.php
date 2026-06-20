<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * لیست مجوزها
     */
    public function index()
    {
        $permissions = $this->permissionService->getPermissions();
        $groupedPermissions = $this->permissionService->getGroupedPermissions();

        return view('admin.permissions.index', compact('permissions', 'groupedPermissions'));
    }

    /**
     * نمایش فرم ایجاد مجوز
     */
    public function create()
    {
        return view('admin.permissions.create');
    }

    /**
     * ذخیره مجوز جدید
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        $this->permissionService->createPermission($request->name);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'مجوز با موفقیت ایجاد شد.');
    }

    /**
     * نمایش فرم ویرایش مجوز
     */
    public function edit(int $id)
    {
        $permission = $this->permissionService->getPermission($id);

        if (!$permission) {
            abort(404, 'مجوز یافت نشد.');
        }

        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * بروزرسانی مجوز
     */
    public function update(Request $request, int $id)
    {
        $permission = $this->permissionService->getPermission($id);

        if (!$permission) {
            abort(404, 'مجوز یافت نشد.');
        }

        $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $id,
        ]);

        $this->permissionService->updatePermission($permission, $request->name);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'مجوز با موفقیت بروزرسانی شد.');
    }

    /**
     * حذف مجوز
     */
    public function destroy(int $id)
    {
        $permission = $this->permissionService->getPermission($id);

        if (!$permission) {
            abort(404, 'مجوز یافت نشد.');
        }

        $this->permissionService->deletePermission($permission);

        return redirect()->route('admin.permissions.index')
            ->with('success', 'مجوز با موفقیت حذف شد.');
    }
}
