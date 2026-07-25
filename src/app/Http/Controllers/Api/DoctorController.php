<?php
// app/Http/Controllers/Api/DoctorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    use ApiResponse;

    /**
     * لیست پزشکان (عمومی - API)
     */
    public function index(Request $request)
    {
        $query = Doctor::with([
            'user',
            'specialty',
            'clinic',
            'province',
            'city',
            'primaryAddress'
        ])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->where('is_active', true);

        // فیلتر بر اساس کلینیک
        if ($request->has('clinic_id') && $request->clinic_id) {
            $query->where('clinic_id', $request->clinic_id);
        }

        // فیلتر بر اساس استان
        if ($request->has('province_id') && $request->province_id) {
            $query->where('province_id', $request->province_id);
        }

        // فیلتر بر اساس شهر
        if ($request->has('city_id') && $request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        // فیلتر بر اساس تخصص
        if ($request->has('specialty_id') && $request->specialty_id) {
            $query->where('specialty_id', $request->specialty_id);
        }

        // فیلتر بر اساس هزینه (رایگان/پولی)
        if ($request->has('fee_type') && $request->fee_type !== 'all') {
            $query->where('appointment_fee_type', $request->fee_type);
        }

        // جستجو
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // موقعیت مکانی (نزدیک‌ترین)
        if ($request->has('lat') && $request->has('lng')) {
            $query->nearby($request->lat, $request->lng, $request->radius ?? 10);
        }

        $doctors = $query->orderBy('rating', 'desc')
            ->paginate($request->get('per_page', 15));

        // اضافه کردن اطلاعات هزینه
        $doctors->getCollection()->transform(function ($doctor) {
            $doctor->fee_label = $doctor->appointment_fee_label;
            $doctor->fee_value = $doctor->getFeeForAppointment();
            $doctor->is_free = $doctor->isFreeAppointment();
            return $doctor;
        });

        return $this->success($doctors);
    }

    /**
     * نمایش یک پزشک (عمومی - API)
     */
    public function show($id)
    {
        try {
            $doctor = Doctor::with([
                'user',
                'specialty',
                'primaryAddress',
                'schedules',
                'clinic',
                'province',
                'city'
            ])
                ->where('is_available', true)
                ->where('is_verified', true)
                ->where('is_active', true)
                ->findOrFail($id);

            // اضافه کردن اطلاعات هزینه
            $doctor->fee_label = $doctor->appointment_fee_label;
            $doctor->fee_value = $doctor->getFeeForAppointment();
            $doctor->is_free = $doctor->isFreeAppointment();

            return $this->success($doctor);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * دریافت پزشکان بر اساس هزینه (عمومی - API)
     */
    public function byFee(Request $request)
    {
        $request->validate([
            'fee_type' => 'required|in:free,paid,all',
        ]);

        $query = Doctor::with(['user', 'specialty', 'clinic', 'province', 'city'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->where('is_active', true);

        if ($request->fee_type !== 'all') {
            $query->where('appointment_fee_type', $request->fee_type);
        }

        $doctors = $query->orderBy('rating', 'desc')
            ->paginate($request->get('per_page', 15));

        $doctors->getCollection()->transform(function ($doctor) {
            $doctor->fee_label = $doctor->appointment_fee_label;
            $doctor->fee_value = $doctor->getFeeForAppointment();
            $doctor->is_free = $doctor->isFreeAppointment();
            return $doctor;
        });

        return $this->success($doctors);
    }

    /**
     * دریافت پزشکان نزدیک (عمومی - API)
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50',
            'specialty_id' => 'nullable|exists:specialties,id',
        ]);

        $radius = $request->radius ?? 10;

        $query = Doctor::with(['user', 'specialty', 'clinic', 'province', 'city'])
            ->where('is_available', true)
            ->where('is_verified', true)
            ->where('is_active', true)
            ->nearby($request->lat, $request->lng, $radius);

        if ($request->has('specialty_id') && $request->specialty_id) {
            $query->where('specialty_id', $request->specialty_id);
        }

        $doctors = $query->orderBy('distance', 'asc')
            ->paginate($request->get('per_page', 15));

        $doctors->getCollection()->transform(function ($doctor) {
            $doctor->fee_label = $doctor->appointment_fee_label;
            $doctor->fee_value = $doctor->getFeeForAppointment();
            $doctor->is_free = $doctor->isFreeAppointment();
            return $doctor;
        });

        return $this->success($doctors);
    }
}
