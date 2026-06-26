<?php

namespace App\Services\Doctor;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DoctorService
{
    /**
     * لیست پزشکان با فیلتر
     */
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Doctor::with(['user', 'specialty', 'primaryAddress']);

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
        }

        if (isset($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * ایجاد پزشک جدید
     */
    public function create(array $data): Doctor
    {
        return DB::transaction(function () use ($data) {
            // 1. ایجاد کاربر
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'mobile' => $data['mobile'],
                'password' => Hash::make($data['password'] ?? '12345678'),
                'is_active' => true,
            ]);

            // 2. اختصاص نقش پزشک
            $doctorRole = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
            $user->assignRole($doctorRole);

            // 3. ایجاد پزشک
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'specialty_id' => $data['specialty_id'] ?? null,
                'license_number' => $data['license_number'],
                'clinic_name' => $data['clinic_name'] ?? null,
                'clinic_address' => $data['clinic_address'] ?? null,
                'clinic_phone' => $data['clinic_phone'] ?? null,
                'clinic_email' => $data['clinic_email'] ?? null,
                'biography' => $data['biography'] ?? null,
                'education' => $data['education'] ?? null,
                'experience_years' => $data['experience_years'] ?? 0,
                'consultation_fee' => $data['consultation_fee'] ?? 0,
                'visit_duration' => $data['visit_duration'] ?? 30,
                'is_available' => $data['is_available'] ?? true,
                'is_verified' => $data['is_verified'] ?? false,
            ]);

            // 4. ایجاد آدرس
            if (isset($data['address'])) {
                $doctor->addresses()->create(array_merge(
                    $data['address'],
                    ['is_primary' => true]
                ));
            }

            return $doctor->load(['user', 'specialty', 'primaryAddress']);
        });
    }

    /**
     * نمایش پزشک
     */
    public function show($id): Doctor
    {
        return Doctor::with(['user', 'specialty', 'primaryAddress', 'schedules'])
            ->findOrFail($id);
    }

    /**
     * به‌روزرسانی پزشک
     */
    public function update(Doctor $doctor, array $data): Doctor
    {
        return DB::transaction(function () use ($doctor, $data) {
            // 1. به‌روزرسانی کاربر
            if (isset($data['name']) || isset($data['email']) || isset($data['mobile'])) {
                $doctor->user->update([
                    'name' => $data['name'] ?? $doctor->user->name,
                    'email' => $data['email'] ?? $doctor->user->email,
                    'mobile' => $data['mobile'] ?? $doctor->user->mobile,
                ]);
            }

            // 2. به‌روزرسانی پزشک
            $doctorData = array_intersect_key($data, array_flip([
                'specialty_id', 'license_number', 'clinic_name', 'clinic_address',
                'clinic_phone', 'clinic_email', 'biography', 'education',
                'experience_years', 'consultation_fee', 'visit_duration',
                'is_available', 'is_verified'
            ]));
            $doctor->update($doctorData);

            // 3. به‌روزرسانی آدرس
            if (isset($data['address'])) {
                $address = $doctor->primaryAddress;
                if ($address) {
                    $address->update($data['address']);
                } else {
                    $doctor->addresses()->create(array_merge(
                        $data['address'],
                        ['is_primary' => true]
                    ));
                }
            }

            return $doctor->fresh(['user', 'specialty', 'primaryAddress']);
        });
    }

    /**
     * تغییر وضعیت پزشک
     */
    public function toggleAvailability(Doctor $doctor): Doctor
    {
        $doctor->update(['is_available' => !$doctor->is_available]);
        return $doctor->fresh();
    }

    /**
     * تایید پزشک
     */
    public function verify(Doctor $doctor): Doctor
    {
        $doctor->update(['is_verified' => true]);
        return $doctor->fresh();
    }

    /**
     * حذف پزشک
     */
    public function delete(Doctor $doctor): void
    {
        DB::transaction(function () use ($doctor) {
            // حذف آدرس‌ها
            $doctor->addresses()->delete();
            // حذف پزشک
            $doctor->delete();
            // غیرفعال کردن کاربر
            $doctor->user->update(['is_active' => false]);
        });
    }

    /**
     * لیست پزشکان عمومی (بدون احراز هویت)
     */
    public function publicList(array $filters = [], int $perPage = 15)
    {
        $query = Doctor::with(['user', 'specialty', 'primaryAddress'])
            ->where('is_available', true)
            ->where('is_verified', true);

        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('rating', 'desc')->paginate($perPage);
    }
}
