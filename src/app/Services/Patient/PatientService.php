<?php

namespace App\Services\Patient;

use App\Models\Patient;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PatientService
{
    /**
     * لیست بیماران با فیلتر
     */
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Patient::with(['user', 'doctor', 'primaryAddress']);

        // جستجو
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // فیلتر بر اساس پزشک
        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        // فیلتر بر اساس وضعیت
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // فیلتر بر اساس تایید شده
        if (isset($filters['is_verified'])) {
            if ($filters['is_verified']) {
                $query->verified();
            } else {
                $query->unverified();
            }
        }

        // فیلتر بر اساس تاریخ
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * ایجاد بیمار جدید
     */
    public function create(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            // 1. ایجاد کاربر
            $userData = [
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'is_active' => true,
            ];

            if (isset($data['email'])) {
                $userData['email'] = $data['email'];
            }

            if (isset($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            } else {
                $userData['password'] = Hash::make('12345678');
            }

            $user = User::create($userData);

            // 2. اختصاص نقش بیمار
            $patientRole = Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
            $user->assignRole($patientRole);

            // 3. ایجاد بیمار
            $patientData = [
                'user_id' => $user->id,
                'national_code' => $data['national_code'] ?? null,
                'phone' => $data['phone'] ?? $data['mobile'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'doctor_id' => $data['doctor_id'] ?? null,
                'verified_at' => $data['is_verified'] ?? false ? now() : null,
            ];

            // اضافه کردن متادیتا
            if (isset($data['metadata'])) {
                $patientData['metadata'] = $data['metadata'];
            }

            $patient = Patient::create($patientData);

            // 4. ایجاد آدرس
            if (isset($data['address'])) {
                $patient->addresses()->create(array_merge(
                    $data['address'],
                    ['is_primary' => true]
                ));
            }

            return $patient->load(['user', 'doctor', 'primaryAddress']);
        });
    }

    /**
     * دریافت اطلاعات بیمار به همراه تاریخچه کامل
     */
    public function show(int $id): Patient
    {
        return Patient::with([
            'user',
            'doctor.user',
            'doctor.specialty',
            'primaryAddress',
            'primaryAddress.province',
            'primaryAddress.city',
            'appointments' => function ($query) {
                $query->orderBy('date', 'desc')->limit(10);
            },
            'appointments.doctor.user',
            'appointments.doctor.specialty',
            'prescriptions' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'prescriptions.doctor.user',
            'invoices' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
        ])->findOrFail($id);
    }

    /**
     * به‌روزرسانی بیمار
     */
    public function update(Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($patient, $data) {
            // 1. به‌روزرسانی کاربر
            if (isset($data['name']) || isset($data['email']) || isset($data['mobile'])) {
                $patient->user->update([
                    'name' => $data['name'] ?? $patient->user->name,
                    'email' => $data['email'] ?? $patient->user->email,
                    'mobile' => $data['mobile'] ?? $patient->user->mobile,
                ]);
            }

            // 2. به‌روزرسانی بیمار
            $patientData = array_intersect_key($data, array_flip([
                'national_code', 'phone', 'emergency_contact',
                'blood_type', 'is_active', 'doctor_id', 'metadata'
            ]));

            if (!empty($patientData)) {
                $patient->update($patientData);
            }

            // 3. به‌روزرسانی آدرس
            if (isset($data['address'])) {
                $address = $patient->primaryAddress;
                if ($address) {
                    $address->update($data['address']);
                } else {
                    $patient->addresses()->create(array_merge(
                        $data['address'],
                        ['is_primary' => true]
                    ));
                }
            }

            return $patient->fresh(['user', 'doctor', 'primaryAddress']);
        });
    }

    /**
     * حذف بیمار
     */
    public function delete(Patient $patient): void
    {
        DB::transaction(function () use ($patient) {
            // حذف آدرس‌ها
            $patient->addresses()->delete();
            // حذف بیمار
            $patient->delete();
            // غیرفعال کردن کاربر
            $patient->user->update(['is_active' => false]);
        });
    }

    /**
     * تغییر وضعیت بیمار
     */
    public function toggleStatus(Patient $patient): Patient
    {
        $patient->toggleStatus();
        return $patient->fresh();
    }

    /**
     * تایید بیمار
     */
    public function verify(Patient $patient): Patient
    {
        $patient->verify();
        return $patient->fresh();
    }

    /**
     * لغو تایید بیمار
     */
    public function unverify(Patient $patient): Patient
    {
        $patient->unverify();
        return $patient->fresh();
    }

    /**
     * اختصاص پزشک به بیمار
     */
    public function assignDoctor(Patient $patient, int $doctorId): Patient
    {
        $patient->assignDoctor($doctorId);
        return $patient->fresh();
    }

    /**
     * دریافت تاریخچه کامل پزشکی بیمار (پرونده سلامت)
     */
    public function getMedicalHistory(Patient $patient): array
    {
        return $patient->getMedicalHistory();
    }

    /**
     * دریافت آمار بیمار
     */
    public function getStatistics(Patient $patient): array
    {
        return $patient->getStatistics();
    }

    /**
     * جستجوی بیمار با کدملی
     */
    public function findByNationalCode(string $nationalCode): ?Patient
    {
        return Patient::with(['user', 'doctor'])
            ->where('national_code', $nationalCode)
            ->first();
    }

    /**
     * جستجوی بیمار با موبایل
     */
    public function findByMobile(string $mobile): ?Patient
    {
        return Patient::whereHas('user', function ($query) use ($mobile) {
            $query->where('mobile', $mobile);
        })->with(['user', 'doctor'])->first();
    }

    /**
     * دریافت بیماران بدون پزشک
     */
    public function getPatientsWithoutDoctor()
    {
        return Patient::with(['user'])
            ->whereNull('doctor_id')
            ->where('is_active', true)
            ->get();
    }

    /**
     * دریافت بیماران پرمراجعه
     */
    public function getTopPatients(int $limit = 10)
    {
        return Patient::with(['user'])
            ->withCount('appointments')
            ->where('is_active', true)
            ->orderBy('appointments_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
