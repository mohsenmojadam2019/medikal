<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // اجرای سیدر roles و permissions
        $this->call(RolesAndPermissionsSeeder::class);

        // ایجاد کاربر ادمین
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@medikal.com',
            'password' => bcrypt('password123'),
        ]);

        // اختصاص نقش super-admin به کاربر
        $admin->assignRole('super-admin');


    }
}
