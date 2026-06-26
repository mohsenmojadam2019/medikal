<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ========== تعریف Permissions ==========
        $permissions = [
            'view_doctors', 'create_doctors', 'edit_doctors', 'delete_doctors',
            'verify_doctors', 'toggle_doctor_status',
            'view_patients', 'create_patients', 'edit_patients', 'delete_patients',
            'view_appointments', 'create_appointments', 'edit_appointments', 'delete_appointments',
            'confirm_appointments', 'cancel_appointments',
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices',
            'process_payments', 'view_reports',
            'manage_settings', 'manage_roles', 'manage_permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ========== تعریف Roles ==========
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'view_doctors', 'create_doctors', 'edit_doctors', 'delete_doctors',
            'verify_doctors', 'toggle_doctor_status',
            'view_patients', 'create_patients', 'edit_patients', 'delete_patients',
            'view_appointments', 'create_appointments', 'edit_appointments', 'confirm_appointments',
            'cancel_appointments', 'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices',
            'process_payments', 'view_reports',
        ]);

        $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $doctor->syncPermissions([
            'view_patients', 'view_appointments', 'create_appointments',
            'edit_appointments', 'confirm_appointments', 'cancel_appointments',
            'view_invoices', 'view_doctors',
        ]);

        $patient = Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
        $patient->syncPermissions([
            'view_appointments', 'create_appointments', 'edit_appointments',
            'cancel_appointments', 'view_invoices',
        ]);

        $receptionist = Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        $receptionist->syncPermissions([
            'view_patients', 'create_patients', 'edit_patients',
            'view_appointments', 'create_appointments', 'edit_appointments',
            'confirm_appointments', 'cancel_appointments',
            'view_invoices', 'create_invoices',
        ]);

        // ========== کاربر ادمین (با فیلدهای موجود) ==========
        $user = User::where('mobile', '09123456789')->first();

        if ($user) {
            $user->assignRole('admin');
            $this->command->info('✅ نقش admin به کاربر موجود اختصاص داده شد.');
        } else {
            $newUser = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@clinic-yar.com',
                'mobile' => '09123456789',
                'password' => Hash::make('12345678'),
                'is_active' => true,
                'email_verified_at' => now(),
                'mobile_verified_at' => now(),
            ]);
            $newUser->assignRole('admin');
            $this->command->info('✅ کاربر ادمین جدید ایجاد شد.');
        }

        $this->command->info('✅ نقش‌ها و مجوزها با موفقیت ایجاد شدند!');
        $this->command->info('📱 موبایل: 09123456789');
        $this->command->info('🔑 رمز عبور: 12345678');
    }
}
