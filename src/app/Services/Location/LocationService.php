<?php

namespace App\Services\Location;

use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\City;
use App\Models\Province;
use App\Models\Specialty;
use App\Models\Rating;
use Illuminate\Support\Facades\Cache;

class LocationService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function findNearbyDoctors($lat, $lng, $radius = 10, $filters = [], $perPage = 15)
    {
        $query = Doctor::where('tenant_id', $this->tenantId)
            ->with(['user', 'specialty', 'primaryAddress'])
            ->active()
            ->verified()
            ->available();

        $query->nearby($lat, $lng, $radius);

        if (isset($filters['specialty_id'])) {
            $query->bySpecialty($filters['specialty_id']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['insurance'])) {
            $query->byInsurance($filters['insurance']);
        }

        $sortBy = $filters['sort_by'] ?? 'distance';
        if ($sortBy === 'rating') {
            $query->orderBy('rating', 'desc');
        } else {
            $query->orderBy('distance', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function findNearbyClinics($lat, $lng, $radius = 10, $perPage = 15)
    {
        return Clinic::where('tenant_id', $this->tenantId)
            ->active()
            ->nearby($lat, $lng, $radius)
            ->orderBy('distance', 'asc')
            ->paginate($perPage);
    }

    public function getProvinces()
    {
        return Cache::remember('provinces_list_' . $this->tenantId, 3600, function () {
            return Province::where('tenant_id', $this->tenantId)
                ->active()
                ->orderBy('name')
                ->get();
        });
    }

    public function getCitiesByProvince($provinceId)
    {
        $cacheKey = "cities_province_{$provinceId}_" . $this->tenantId;
        return Cache::remember($cacheKey, 3600, function () use ($provinceId) {
            return City::where('tenant_id', $this->tenantId)
                ->where('province_id', $provinceId)
                ->active()
                ->orderBy('name')
                ->get();
        });
    }

    public function getSpecialties()
    {
        return Cache::remember('specialties_list_' . $this->tenantId, 3600, function () {
            return Specialty::where('tenant_id', $this->tenantId)
                ->active()
                ->orderBy('name')
                ->get();
        });
    }

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

    public function getDoctorProfile($id)
    {
        return Doctor::where('tenant_id', $this->tenantId)
            ->with([
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
            ])
            ->findOrFail($id);
    }

    public function getDoctorReviews($doctorId, $perPage = 15)
    {
        return Rating::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->with(['patient.user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
