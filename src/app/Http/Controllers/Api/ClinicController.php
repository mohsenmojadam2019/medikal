<?php
// app/Http/Controllers/Api/ClinicController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    use ApiResponse;

    /**
     * لیست کلینیک‌ها (عمومی)
     */
    public function index(Request $request)
    {
        $query = Clinic::with(['province', 'city'])
            ->where('is_active', true)
            ->where('is_verified', true);

        // فیلتر بر اساس استان
        if ($request->has('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        // فیلتر بر اساس شهر
        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        // جستجو
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // موقعیت مکانی (نزدیک‌ترین)
        if ($request->has('lat') && $request->has('lng')) {
            $query->nearby($request->lat, $request->lng, $request->radius ?? 10);
        }

        $clinics = $query->paginate($request->get('per_page', 15));

        return $this->success($clinics);
    }

    /**
     * نمایش یک کلینیک (عمومی)
     */
    public function show($id)
    {
        try {
            $clinic = Clinic::with(['province', 'city', 'doctors', 'doctors.specialty'])
                ->where('is_active', true)
                ->where('is_verified', true)
                ->findOrFail($id);

            return $this->success($clinic);
        } catch (\Exception $e) {
            return $this->error('کلینیک یافت نشد', 404);
        }
    }

    /**
     * دریافت تنظیمات عمومی کلینیک (بدون احراز هویت)
     */
    public function settings()
    {
        $clinic = Clinic::with(['province', 'city'])
            ->where('is_active', true)
            ->first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        return $this->success([
            'id' => $clinic->id,
            'name' => $clinic->name,
            'address' => $clinic->address,
            'phone' => $clinic->phone,
            'email' => $clinic->email,
            'website' => $clinic->website,
            'logo_url' => $clinic->logo_url,
            'favicon_url' => $clinic->favicon_url,
            'primary_color' => $clinic->primary_color,
            'secondary_color' => $clinic->secondary_color,
            'timezone' => $clinic->timezone,
            'currency' => $clinic->currency,
            'language' => $clinic->language,
            'tax_rate' => $clinic->tax_rate,
            'invoice_prefix' => $clinic->invoice_prefix,
            'appointment_prefix' => $clinic->appointment_prefix,
            'province' => $clinic->province?->name,
            'city' => $clinic->city?->name,
            'full_address' => $clinic->full_address,
        ]);
    }

    /**
     * دریافت لیست استان‌های دارای کلینیک
     */
    public function provinces()
    {
        $provinces = Clinic::where('is_active', true)
            ->where('is_verified', true)
            ->whereNotNull('province_id')
            ->with('province')
            ->get()
            ->pluck('province')
            ->filter()
            ->unique('id')
            ->values();

        return $this->success($provinces);
    }

    /**
     * دریافت لیست شهرهای دارای کلینیک در یک استان
     */
    public function cities(Request $request, $provinceId)
    {
        $cities = Clinic::where('is_active', true)
            ->where('is_verified', true)
            ->where('province_id', $provinceId)
            ->whereNotNull('city_id')
            ->with('city')
            ->get()
            ->pluck('city')
            ->filter()
            ->unique('id')
            ->values();

        return $this->success($cities);
    }
}
