<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserService
{
    /**
     * لیست کاربران با فیلتر
     */
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = User::query();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('email', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('mobile', 'LIKE', "%{$filters['search']}%");
            });
        }

        if (isset($filters['role'])) {
            $query->role($filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * ایجاد کاربر جدید
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'mobile' => $data['mobile'],
                'password' => Hash::make($data['password'] ?? '12345678'),
                'is_active' => $data['is_active'] ?? true,
            ]);

            // اختصاص نقش
            if (isset($data['role'])) {
                $user->assignRole($data['role']);
            }

            // ایجاد آدرس
            if (isset($data['address'])) {
                $user->addresses()->create($data['address']);
            }

            return $user;
        });
    }

    /**
     * به‌روزرسانی کاربر
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['email'])) {
                $updateData['email'] = $data['email'];
            }

            if (isset($data['mobile'])) {
                $updateData['mobile'] = $data['mobile'];
            }

            if (isset($data['is_active'])) {
                $updateData['is_active'] = $data['is_active'];
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // تغییر نقش
            if (isset($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            // تغییر آدرس
            if (isset($data['address'])) {
                $address = $user->primaryAddress;
                if ($address) {
                    $address->update($data['address']);
                } else {
                    $user->addresses()->create(array_merge($data['address'], ['is_primary' => true]));
                }
            }

            return $user->fresh();
        });
    }

    /**
     * تغییر رمز عبور
     */
    public function changePassword(User $user, string $oldPassword, string $newPassword): bool
    {
        if (!Hash::check($oldPassword, $user->password)) {
            throw new \Exception('رمز عبور فعلی اشتباه است');
        }

        $user->update(['password' => Hash::make($newPassword)]);
        return true;
    }

    /**
     * تغییر استان و شهر کاربر
     */
    public function updateLocation(User $user, int $provinceId, int $cityId): User
    {
        $address = $user->primaryAddress;
        if ($address) {
            $address->update([
                'province_id' => $provinceId,
                'city_id' => $cityId,
            ]);
        }

        return $user->fresh();
    }

    /**
     * حذف کاربر
     */
    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * تغییر وضعیت کاربر
     */
    public function toggleStatus(User $user): User
    {
        $user->update(['is_active' => !$user->is_active]);
        return $user->fresh();
    }
}
