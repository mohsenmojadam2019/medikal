<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Services\Location\LocationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use ApiResponse;

    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * جستجوی پزشکان نزدیک
     */
    public function nearByDoctors(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
            'specialty_id' => 'nullable|exists:specialties,id',
            'search' => 'nullable|string|max:255',
            'insurance' => 'nullable|string',
            'sort_by' => 'nullable|in:distance,rating',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $result = $this->locationService->findNearbyDoctors(
                $request->lat,
                $request->lng,
                $request->radius ?? 10,
                $request->only(['specialty_id', 'search', 'insurance', 'sort_by']),
                $request->per_page ?? 15
            );

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * جستجوی کلینیک‌های نزدیک
     */
    public function nearByClinics(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $result = $this->locationService->findNearbyClinics(
                $request->lat,
                $request->lng,
                $request->radius ?? 10,
                $request->per_page ?? 15
            );

            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت پروفایل کامل پزشک
     */
    public function doctorProfile($id)
    {
        try {
            $doctor = $this->locationService->getDoctorProfile($id);
            return $this->success($doctor);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * دریافت نظرات پزشک
     */
    public function doctorReviews(Request $request, $id)
    {
        try {
            $reviews = $this->locationService->getDoctorReviews($id, $request->per_page ?? 15);
            return $this->success($reviews);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * دریافت لیست استان‌ها
     */
    public function provinces()
    {
        $provinces = $this->locationService->getProvinces();
        return $this->success($provinces);
    }

    /**
     * دریافت لیست شهرهای یک استان
     */
    public function cities($provinceId)
    {
        try {
            $cities = $this->locationService->getCitiesByProvince($provinceId);
            return $this->success($cities);
        } catch (\Exception $e) {
            return $this->error('استان یافت نشد', 404);
        }
    }

    /**
     * دریافت لیست تخصص‌ها
     */
    public function specialties()
    {
        $specialties = $this->locationService->getSpecialties();
        return $this->success($specialties);
    }

    /**
     * محاسبه فاصله بین دو نقطه
     */
    public function calculateDistance(Request $request)
    {
        $request->validate([
            'lat1' => 'required|numeric|between:-90,90',
            'lng1' => 'required|numeric|between:-180,180',
            'lat2' => 'required|numeric|between:-90,90',
            'lng2' => 'required|numeric|between:-180,180',
        ]);

        $distance = $this->locationService->calculateDistance(
            $request->lat1,
            $request->lng1,
            $request->lat2,
            $request->lng2
        );

        return $this->success([
            'distance_km' => round($distance, 2),
            'distance_m' => round($distance * 1000, 0),
        ]);
    }

    /**
     * به‌روزرسانی موقعیت پزشک (ادمین)
     */
    public function updateDoctorLocation(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $doctor = Doctor::findOrFail($id);
            $doctor->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            return $this->success($doctor, 'موقعیت پزشک با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
