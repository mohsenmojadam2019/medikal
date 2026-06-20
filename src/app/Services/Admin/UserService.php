<?php


namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * دریافت لیست کاربران با pagination
     */
    public function getUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::with('roles')->latest()->paginate($perPage);
    }

    /**
     * دریافت یک کاربر
     */
    public function getUser(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    /**
     * ایجاد کاربر جدید
     */
    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => bcrypt($data['password']),
            'is_active' => $data['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        // اختصاص نقش
        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        return $user;
    }

    /**
     * بروزرسانی کاربر
     */
    public function updateUser(User $user, array $data): User
    {
        $updateData = [
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'is_active' => $data['is_active'] ?? $user->is_active,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = bcrypt($data['password']);
        }

        $user->update($updateData);

        // بروزرسانی نقش
        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user->fresh();
    }

    /**
     * حذف کاربر
     */
    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    /**
     * تغییر وضعیت فعال/غیرفعال
     */
    public function toggleStatus(User $user): User
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return $user->fresh();
    }

    /**
     * جستجوی کاربران
     */
    public function searchUsers(string $search, int $perPage = 15): LengthAwarePaginator
    {
        return User::where('name', 'LIKE', "%{$search}%")
            ->orWhere('email', 'LIKE', "%{$search}%")
            ->orWhere('phone', 'LIKE', "%{$search}%")
            ->paginate($perPage);
    }
}
