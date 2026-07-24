<?php
// app/Services/Doctor/DoctorService.php

namespace App\Services\Doctor;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DoctorService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Doctor::where('tenant_id', $this->tenantId)
            ->with(['user', 'specialty', 'clinic', 'province', 'city', 'primaryAddress']);

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        if (isset($filters['clinic_id']) && $filters['clinic_id']) {
            $query->byClinic($filters['clinic_id']);
        }

        if (isset($filters['province_id']) && $filters['province_id']) {
            $query->where('province_id', $filters['province_id']);
        }

        if (isset($filters['city_id']) && $filters['city_id']) {
            $query->where('city_id', $filters['city_id']);
        }

        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
        }

        if (isset($filters['is_verified'])) {
            $query->where('is_verified', $filters['is_verified']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['fee_type']) && $filters['fee_type'] !== 'all') {
            $query->where('appointment_fee_type', $filters['fee_type']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

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

            // 3. ایجاد پزشک (بدون profile_image - از Media Library استفاده می‌شود)
            $doctor = Doctor::create([
                'tenant_id' => $this->tenantId,
                'user_id' => $user->id,
                'clinic_id' => $data['clinic_id'] ?? null,
                'specialty_id' => $data['specialty_id'] ?? null,
                'license_number' => $data['license_number'],
                'province_id' => $data['province_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                // ❌ حذف شد - profile_image
                'bio' => $data['bio'] ?? null,
                'biography' => $data['biography'] ?? null,
                'education' => $data['education'] ?? null,
                'certificates' => $data['certificates'] ?? null,
                'social_links' => $data['social_links'] ?? null,
                'working_hours' => $data['working_hours'] ?? null,
                'experience_years' => $data['experience_years'] ?? 0,
                'consultation_fee' => $data['consultation_fee'] ?? 0,
                'appointment_fee_type' => $data['appointment_fee_type'] ?? 'paid',
                'appointment_fee_amount' => $data['appointment_fee_amount'] ?? null,
                'is_fee_editable_by_admin' => $data['is_fee_editable_by_admin'] ?? true,
                'visit_duration' => $data['visit_duration'] ?? 30,
                'is_available' => $data['is_available'] ?? true,
                'is_verified' => $data['is_verified'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // 4. آپلود عکس پروفایل (اگر ارسال شده باشد)
            if (isset($data['profile_image']) && $data['profile_image'] instanceof \Illuminate\Http\UploadedFile) {
                $doctor->addMedia($data['profile_image'])
                    ->toMediaCollection('profile_image');
            }

            // 5. ایجاد آدرس
            if (isset($data['address']) && is_array($data['address'])) {
                $doctor->addresses()->create(array_merge(
                    $data['address'],
                    ['is_primary' => true, 'tenant_id' => $this->tenantId]
                ));
            }

            return $doctor->load(['user', 'specialty', 'clinic', 'province', 'city', 'primaryAddress']);
        });
    }

    public function show($id): Doctor
    {
        return Doctor::where('tenant_id', $this->tenantId)
            ->with([
                'user',
                'specialty',
                'clinic',
                'province',
                'city',
                'primaryAddress',
                'schedules'
            ])
            ->findOrFail($id);
    }

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

            // 2. به‌روزرسانی پزشک (بدون profile_image)
            $doctorData = array_intersect_key($data, array_flip([
                'clinic_id',
                'specialty_id',
                'license_number',
                'province_id',
                'city_id',
                'latitude',
                'longitude',
                // ❌ حذف شد - profile_image
                'bio',
                'biography',
                'education',
                'certificates',
                'social_links',
                'working_hours',
                'experience_years',
                'consultation_fee',
                'appointment_fee_type',
                'appointment_fee_amount',
                'is_fee_editable_by_admin',
                'visit_duration',
                'is_available',
                'is_verified',
                'is_active',
                'metadata',
            ]));

            if (!empty($doctorData)) {
                $doctor->update($doctorData);
            }

            // 3. آپلود/به‌روزرسانی عکس پروفایل (با Media Library)
            if (isset($data['profile_image']) && $data['profile_image'] instanceof \Illuminate\Http\UploadedFile) {
                $doctor->clearMediaCollection('profile_image');
                $doctor->addMedia($data['profile_image'])
                    ->toMediaCollection('profile_image');
            }

            // 4. به‌روزرسانی آدرس
            if (isset($data['address'])) {
                if (is_array($data['address'])) {
                    $address = $doctor->primaryAddress;
                    if ($address) {
                        $address->update($data['address']);
                    } else {
                        $doctor->addresses()->create(array_merge(
                            $data['address'],
                            ['is_primary' => true, 'tenant_id' => $this->tenantId]
                        ));
                    }
                } else {
                    $doctor->update(['address' => $data['address']]);
                }
            }

            return $doctor->fresh(['user', 'specialty', 'clinic', 'province', 'city', 'primaryAddress']);
        });
    }

    public function toggleAvailability(Doctor $doctor): Doctor
    {
        $doctor->update(['is_available' => !$doctor->is_available]);
        return $doctor->fresh();
    }

    public function verify(Doctor $doctor): Doctor
    {
        $doctor->update(['is_verified' => true]);
        return $doctor->fresh();
    }

    public function unverify(Doctor $doctor): Doctor
    {
        $doctor->update(['is_verified' => false]);
        return $doctor->fresh();
    }

    public function delete(Doctor $doctor): void
    {
        DB::transaction(function () use ($doctor) {
            // حذف عکس‌های پروفایل
            $doctor->clearMediaCollection('profile_image');
            $doctor->addresses()->delete();
            $doctor->delete();
            $doctor->user->update(['is_active' => false]);
        });
    }

    public function publicList(array $filters = [], int $perPage = 15)
    {
        $query = Doctor::where('tenant_id', $this->tenantId)
            ->with(['user', 'specialty', 'clinic', 'province', 'city', 'primaryAddress'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->where('is_active', true);

        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        if (isset($filters['clinic_id']) && $filters['clinic_id']) {
            $query->byClinic($filters['clinic_id']);
        }

        if (isset($filters['province_id']) && $filters['province_id']) {
            $query->where('province_id', $filters['province_id']);
        }

        if (isset($filters['city_id']) && $filters['city_id']) {
            $query->where('city_id', $filters['city_id']);
        }

        if (isset($filters['fee_type']) && $filters['fee_type'] !== 'all') {
            $query->where('appointment_fee_type', $filters['fee_type']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['lat']) && isset($filters['lng'])) {
            $radius = $filters['radius'] ?? 10;
            $query->nearby($filters['lat'], $filters['lng'], $radius);
        }

        return $query->orderBy('rating', 'desc')->paginate($perPage);
    }

    public function getNearbyDoctors($lat, $lng, $radius = 10, array $filters = [], int $perPage = 15)
    {
        $query = Doctor::where('tenant_id', $this->tenantId)
            ->with(['user', 'specialty', 'clinic', 'province', 'city'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->where('is_active', true)
            ->nearby($lat, $lng, $radius);

        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        if (isset($filters['fee_type']) && $filters['fee_type'] !== 'all') {
            $query->where('appointment_fee_type', $filters['fee_type']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('distance', 'asc')->paginate($perPage);
    }

    public function getDoctorsByFee(string $feeType, array $filters = [], int $perPage = 15)
    {
        $query = Doctor::where('tenant_id', $this->tenantId)
            ->with(['user', 'specialty', 'clinic', 'province', 'city'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->where('is_active', true);

        if ($feeType !== 'all') {
            $query->where('appointment_fee_type', $feeType);
        }

        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('rating', 'desc')->paginate($perPage);
    }

    public function setAppointmentFee(Doctor $doctor, string $feeType, ?float $amount = null): Doctor
    {
        $doctor->appointment_fee_type = $feeType;

        if ($feeType === 'paid') {
            $doctor->appointment_fee_amount = $amount;
        } else {
            $doctor->appointment_fee_amount = null;
        }

        $doctor->save();
        return $doctor->fresh();
    }

    public function setFree(Doctor $doctor): Doctor
    {
        return $this->setAppointmentFee($doctor, 'free');
    }

    public function setPaid(Doctor $doctor, float $amount): Doctor
    {
        return $this->setAppointmentFee($doctor, 'paid', $amount);
    }

    public function updateLocation(Doctor $doctor, float $latitude, float $longitude): Doctor
    {
        $doctor->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
        return $doctor->fresh();
    }

    public function getStats(Doctor $doctor): array
    {
        return [
            'total_appointments' => $doctor->appointments()->count(),
            'completed_appointments' => $doctor->appointments()->where('status', 'completed')->count(),
            'cancelled_appointments' => $doctor->appointments()->where('status', 'cancelled')->count(),
            'total_patients' => $doctor->patients()->count(),
            'total_prescriptions' => $doctor->prescriptions()->count(),
            'active_prescriptions' => $doctor->prescriptions()->where('status', 'active')->count(),
            'total_reviews' => $doctor->total_reviews ?? 0,
            'rating' => $doctor->rating ?? 0,
            'today_appointments' => $doctor->appointments()->whereDate('date', today())->count(),
            'upcoming_appointments' => $doctor->appointments()
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereDate('date', '>=', today())
                ->count(),
        ];
    }

    public function getTopDoctors(int $limit = 10, array $filters = [])
    {
        $query = Doctor::where('tenant_id', $this->tenantId)
            ->with(['user', 'specialty', 'clinic'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->where('is_active', true);

        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        if (isset($filters['clinic_id']) && $filters['clinic_id']) {
            $query->byClinic($filters['clinic_id']);
        }

        return $query->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * آپلود عکس پروفایل پزشک
     */
    public function uploadProfileImage(Doctor $doctor, $file): Doctor
    {
        $doctor->clearMediaCollection('profile_image');
        $doctor->addMedia($file)
            ->toMediaCollection('profile_image');
        return $doctor->fresh();
    }

    /**
     * حذف عکس پروفایل پزشک
     */
    public function deleteProfileImage(Doctor $doctor): Doctor
    {
        $doctor->clearMediaCollection('profile_image');
        return $doctor->fresh();
    }

    /**
     * دریافت عکس پروفایل پزشک
     */
    public function getProfileImageUrl(Doctor $doctor, string $conversion = ''): ?string
    {
        $media = $doctor->getFirstMedia('profile_image');
        if (!$media) {
            return null;
        }
        return $conversion ? $media->getUrl($conversion) : $media->getUrl();
    }
}
