<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        // ===== Permissions =====
        $permissions = [
            // مدیریت کاربران
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            // مدیریت محصولات
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',

            // مدیریت سفارشات
            'view_orders',
            'create_orders',
            'edit_orders',
            'delete_orders',

            // مدیریت فروشندگان
            'view_vendors',
            'create_vendors',
            'edit_vendors',
            'delete_vendors',

            // مدیریت بلاگ
            'view_blogs',
            'create_blogs',
            'edit_blogs',
            'delete_blogs',

            // مدیریت نظرات
            'view_comments',
            'edit_comments',
            'delete_comments',

            // مدیریت تخفیف‌ها
            'view_discounts',
            'create_discounts',
            'edit_discounts',
            'delete_discounts',

            // مدیریت تنظیمات
            'view_settings',
            'edit_settings',

            // مدیریت گزارشات
            'view_reports',

            // مدیریت پشتیبانی
            'view_tickets',
            'reply_tickets',
            'close_tickets',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // ===== Roles =====
        // سوپر ادمین - همه دسترسی‌ها
        $superAdmin = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        // ادمین - اکثر دسترسی‌ها (به جز حذف کاربران)
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo([
            'view_users',
            'create_users',
            'edit_users',
            'view_products',
            'create_products',
            'edit_products',
            'delete_products',
            'view_orders',
            'edit_orders',
            'view_vendors',
            'create_vendors',
            'edit_vendors',
            'view_blogs',
            'create_blogs',
            'edit_blogs',
            'delete_blogs',
            'view_comments',
            'edit_comments',
            'delete_comments',
            'view_discounts',
            'create_discounts',
            'edit_discounts',
            'delete_discounts',
            'view_settings',
            'edit_settings',
            'view_reports',
            'view_tickets',
            'reply_tickets',
            'close_tickets',
        ]);

        // فروشنده
        $vendor = Role::create(['name' => 'vendor', 'guard_name' => 'web']);
        $vendor->givePermissionTo([
            'view_products',
            'create_products',
            'edit_products',
            'view_orders',
            'view_vendors',
            'view_comments',
            'view_discounts',
        ]);

        // کاربر عادی
        $user = Role::create(['name' => 'user', 'guard_name' => 'web']);
        $user->givePermissionTo([
            'view_products',
            'view_orders',
            'create_orders',
        ]);
    }
}
