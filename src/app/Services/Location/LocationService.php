<?php

namespace App\Services\Location;

use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\City;
use App\Models\Province;
use Illuminate\Support\Facades\Cache;

class LocationService
{
    /**
     * جستجوی پزشکان بر اساس موقعیت مکانی
     */
    public function findNearbyDoctors($lat, $lng, $radius = 10, $filters = [], $perPage = 15)
    {
        $query = Doctor::with(['user', 'specialty', 'primaryAddress'])
            ->active()
            ->verified()
            ->available();

        // فیلتر بر اساس فاصله
        $query->nearby($lat, $lng, $radius);

        // فیلتر بر اساس تخصص
        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        // فیلتر بر اساس جستجو
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // فیلتر بر اساس بیمه
        if (isset($filters['insurance'])) {
            $query->byInsurance($filters['insurance']);
        }

        // مرتب‌سازی بر اساس فاصله یا امتیاز
        $sortBy = $filters['sort_by'] ?? 'distance';
        if ($sortBy === 'rating') {
            $query->orderBy('rating', 'desc');
        } else {
            $query->orderBy('distance', 'asc');
        }

        return $query->paginate($perPage);
    }

    /**
     * دریافت کلینیک‌های نزدیک
     */
    public function findNearbyClinics($lat, $lng, $radius = 10, $perPage = 15)
    {
        return Clinic::active()
            ->nearby($lat, $lng, $radius)
            ->orderBy('distance', 'asc')
            ->paginate($perPage);
    }

    /**
     * دریافت لیست استان‌ها (با کش)
     */
    public function getProvinces()
    {
        return Cache::remember('provinces_list', 3600, function () {
            return Province::active()->orderBy('name')->get();
        });
    }

    /**
     * دریافت لیست شهرهای یک استان
     */
    public function getCitiesByProvince($provinceId)
    {
        $cacheKey = "cities_province_{$provinceId}";
        return Cache::remember($cacheKey, 3600, function () use ($provinceId) {
            return City::where('province_id', $provinceId)
                ->active()
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * دریافت لیست تخصص‌ها
     */
    public function getSpecialties()
    {
        return Cache::remember('specialties_list', 3600, function () {
            return Specialty::active()->orderBy('name')->get();
        });
    }

    /**
     * محاسبه فاصله بین دو نقطه
     */
    public function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $theta = $lng1 - $lng2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344;
    }

    /**
     * دریافت اطلاعات کامل یک پزشک برای نمایش در پروفایل
     */
    public function getDoctorProfile($id)
    {
        return Doctor::with([
            'user',
            'specialty',
            'primaryAddress',
            'primaryAddress.province',
            'primaryAddress.city',
            'schedules',
            'ratings' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            },
            'ratings.patient.user',
        ])->findOrFail($id);
    }

    /**
     * دریافت نظرات پزشک
     */
    public function getDoctorReviews($doctorId, $perPage = 15)
    {
        return Rating::where('doctor_id', $doctorId)
            ->with(['patient.user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
