<?php


namespace App\Services\Admin;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    /**
     * دریافت لیست مجوزها
     */
    public function getPermissions(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    /**
     * دریافت یک مجوز
     */
    public function getPermission(int $id): ?Permission
    {
        return Permission::find($id);
    }

    /**
     * ایجاد مجوز جدید
     */
    public function createPermission(string $name): Permission
    {
        return Permission::create(['name' => $name, 'guard_name' => 'web']);
    }

    /**
     * بروزرسانی مجوز
     */
    public function updatePermission(Permission $permission, string $name): Permission
    {
        $permission->update(['name' => $name]);
        return $permission->fresh();
    }

    /**
     * حذف مجوز
     */
    public function deletePermission(Permission $permission): bool
    {
        return $permission->delete();
    }

    /**
     * دریافت مجوزهای گروه‌بندی شده
     */
    public function getGroupedPermissions(): array
    {
        $permissions = $this->getPermissions();
        $groups = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $group = $parts[0] ?? 'other';

            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }

            $groups[$group][] = $permission;
        }

        return $groups;
    }
}
