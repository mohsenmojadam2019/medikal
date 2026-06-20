<?php


namespace App\Services\Admin;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class RoleService
{
    /**
     * دریافت لیست نقش‌ها
     */
    public function getRoles(): Collection
    {
        return Role::with('permissions')->get();
    }

    /**
     * دریافت یک نقش
     */
    public function getRole(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    /**
     * ایجاد نقش جدید
     */
    public function createRole(string $name): Role
    {
        return Role::create(['name' => $name, 'guard_name' => 'web']);
    }

    /**
     * بروزرسانی نقش
     */
    public function updateRole(Role $role, string $name): Role
    {
        $role->update(['name' => $name]);
        return $role->fresh();
    }

    /**
     * حذف نقش
     */
    public function deleteRole(Role $role): bool
    {
        return $role->delete();
    }

    /**
     * همگام‌سازی مجوزها با نقش
     */
    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        $role->syncPermissions($permissions);
        return $role->fresh();
    }

    /**
     * دریافت مجوزهای یک نقش
     */
    public function getRolePermissions(Role $role): Collection
    {
        return $role->permissions;
    }
}
