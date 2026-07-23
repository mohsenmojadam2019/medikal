<?php
// app/Services/Patient/PatientService.php

namespace App\Services\Patient;

use App\Models\Patient;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PatientService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Patient::where('tenant_id', $this->tenantId)
            ->with(['user', 'doctor', 'province', 'city', 'primaryAddress']);

        // جستجو
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // فیلتر بر اساس پزشک
        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        // ✅ فیلتر بر اساس استان
        if (isset($filters['province_id']) && $filters['province_id']) {
            $query->byProvince($filters['province_id']);
        }

        // ✅ فیلتر بر اساس شهر
        if (isset($filters['city_id']) && $filters['city_id']) {
            $query->byCity($filters['city_id']);
        }

        // فیلتر بر اساس فعال
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // فیلتر بر اساس تایید شده
        if (isset($filters['is_verified'])) {
            if ($filters['is_verified']) {
                $query->verified();
            } else {
                $query->whereNull('verified_at');
            }
        }

        // فیلتر تاریخ
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function create(array $data): Patient
    {
        return DB::transaction(function () use ($data) {
            // ایجاد کاربر
            $userData = [
                'name' => $data['name'] ?? $data['full_name'] ?? 'بیمار',
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
            $patientRole = Role::firstOrCreate(['name' => 'patient', 'guard_name' => 'web']);
            $user->assignRole($patientRole);

            // ایجاد بیمار
            $patientData = [
                'tenant_id' => $this->tenantId,
                'user_id' => $user->id,
                'national_code' => $data['national_code'] ?? null,
                'full_name' => $data['full_name'] ?? $data['name'] ?? null,
                'phone' => $data['phone'] ?? $data['mobile'] ?? null,
                'address' => $data['address'] ?? null,
                'province_id' => $data['province_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'insurance_type' => $data['insurance_type'] ?? null,
                'insurance_number' => $data['insurance_number'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'doctor_id' => $data['doctor_id'] ?? null,
                'verified_at' => isset($data['is_verified']) && $data['is_verified'] ? now() : null,
                'metadata' => $data['metadata'] ?? null,
            ];

            $patient = Patient::create($patientData);

            // ایجاد آدرس
            if (isset($data['address']) && is_array($data['address'])) {
                $patient->addresses()->create(array_merge(
                    $data['address'],
                    ['is_primary' => true, 'tenant_id' => $this->tenantId]
                ));
            }

            return $patient->load(['user', 'doctor', 'province', 'city', 'primaryAddress']);
        });
    }

    public function show(int $id): Patient
    {
        return Patient::where('tenant_id', $this->tenantId)
            ->with([
                'user',
                'doctor.user',
                'doctor.specialty',
                'province',
                'city',
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
            ])
            ->findOrFail($id);
    }

    public function update(Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($patient, $data) {
            // آپدیت اطلاعات کاربر
            if (isset($data['name']) || isset($data['email']) || isset($data['mobile'])) {
                $patient->user->update([
                    'name' => $data['name'] ?? $patient->user->name,
                    'email' => $data['email'] ?? $patient->user->email,
                    'mobile' => $data['mobile'] ?? $patient->user->mobile,
                ]);
            }

            // آپدیت اطلاعات بیمار
            $patientData = array_intersect_key($data, array_flip([
                'national_code', 'full_name', 'phone', 'address',
                'province_id', 'city_id', 'latitude', 'longitude',
                'insurance_type', 'insurance_number', 'is_active',
                'doctor_id', 'verified_at', 'metadata'
            ]));

            // اگر full_name وارد نشده ولی name وارد شده، از name استفاده کن
            if (!isset($patientData['full_name']) && isset($data['name'])) {
                $patientData['full_name'] = $data['name'];
            }

            if (!empty($patientData)) {
                $patient->update($patientData);
            }

            // مدیریت آدرس
            if (isset($data['address'])) {
                if (is_array($data['address'])) {
                    $address = $patient->primaryAddress;
                    if ($address) {
                        $address->update($data['address']);
                    } else {
                        $patient->addresses()->create(array_merge(
                            $data['address'],
                            ['is_primary' => true, 'tenant_id' => $this->tenantId]
                        ));
                    }
                } else {
                    // اگر آدرس به صورت رشته است، آن را ذخیره کن
                    $patient->update(['address' => $data['address']]);
                }
            }

            return $patient->fresh(['user', 'doctor', 'province', 'city', 'primaryAddress']);
        });
    }

    public function delete(Patient $patient): void
    {
        DB::transaction(function () use ($patient) {
            $patient->addresses()->delete();
            $patient->delete();
            $patient->user->update(['is_active' => false]);
        });
    }

    public function toggleStatus(Patient $patient): Patient
    {
        $patient->toggleStatus();
        return $patient->fresh();
    }

    public function verify(Patient $patient): Patient
    {
        $patient->verify();
        return $patient->fresh();
    }

    public function unverify(Patient $patient): Patient
    {
        $patient->unverify();
        return $patient->fresh();
    }

    public function assignDoctor(Patient $patient, int $doctorId): Patient
    {
        $patient->assignDoctor($doctorId);
        return $patient->fresh();
    }

    public function getMedicalHistory(Patient $patient): array
    {
        return [
            'patient' => $patient->load(['user', 'doctor', 'province', 'city']),
            'appointments' => $patient->appointments()->with(['doctor'])->orderBy('date', 'desc')->get(),
            'prescriptions' => $patient->prescriptions()->with(['doctor'])->orderBy('created_at', 'desc')->get(),
            'invoices' => $patient->invoices()->orderBy('created_at', 'desc')->get(),
            'medical_notes' => \App\Models\MedicalNote::where('patient_id', $patient->id)
                ->with(['doctor'])
                ->orderBy('created_at', 'desc')
                ->get(),
        ];
    }

    public function getStatistics(Patient $patient): array
    {
        return [
            'total_appointments' => $patient->appointments()->count(),
            'completed_appointments' => $patient->appointments()->where('status', 'completed')->count(),
            'cancelled_appointments' => $patient->appointments()->where('status', 'cancelled')->count(),
            'total_prescriptions' => $patient->prescriptions()->count(),
            'active_prescriptions' => $patient->prescriptions()->where('status', 'active')->count(),
            'total_invoices' => $patient->invoices()->count(),
            'paid_invoices' => $patient->invoices()->where('status', 'paid')->count(),
            'total_spent' => $patient->invoices()->where('status', 'paid')->sum('total_amount'),
            'last_visit' => $patient->appointments()
                ->where('status', 'completed')
                ->orderBy('date', 'desc')
                ->first(),
            'next_appointment' => $patient->appointments()
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('date', 'asc')
                ->first(),
        ];
    }

    public function findByNationalCode(string $nationalCode): ?Patient
    {
        return Patient::where('tenant_id', $this->tenantId)
            ->where('national_code', $nationalCode)
            ->with(['user', 'doctor', 'province', 'city'])
            ->first();
    }

    public function findByMobile(string $mobile): ?Patient
    {
        return Patient::where('tenant_id', $this->tenantId)
            ->whereHas('user', function ($query) use ($mobile) {
                $query->where('mobile', $mobile);
            })
            ->with(['user', 'doctor', 'province', 'city'])
            ->first();
    }

    public function getPatientsWithoutDoctor()
    {
        return Patient::where('tenant_id', $this->tenantId)
            ->with(['user', 'province', 'city'])
            ->whereNull('doctor_id')
            ->where('is_active', true)
            ->get();
    }

    public function getTopPatients(int $limit = 10)
    {
        return Patient::where('tenant_id', $this->tenantId)
            ->with(['user', 'province', 'city'])
            ->withCount('appointments')
            ->where('is_active', true)
            ->orderBy('appointments_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getCurrentPatient(int $userId): ?Patient
    {
        return Patient::where('tenant_id', $this->tenantId)
            ->where('user_id', $userId)
            ->with(['user', 'province', 'city'])
            ->first();
    }

    public function updateCurrentPatient(int $userId, array $data): Patient
    {
        return DB::transaction(function () use ($userId, $data) {
            $patient = Patient::where('tenant_id', $this->tenantId)
                ->where('user_id', $userId)
                ->first();

            if (!$patient) {
                $user = User::findOrFail($userId);
                $patient = Patient::create([
                    'tenant_id' => $this->tenantId,
                    'user_id' => $userId,
                    'full_name' => $user->name,
                    'phone' => $user->mobile,
                    'is_active' => true,
                ]);
            }

            $patientData = array_intersect_key($data, array_flip([
                'national_code', 'full_name', 'phone', 'address',
                'province_id', 'city_id', 'latitude', 'longitude',
                'insurance_type', 'insurance_number'
            ]));

            if (!empty($patientData)) {
                $patient->update($patientData);
            }

            return $patient->fresh(['user', 'province', 'city']);
        });
    }
}
