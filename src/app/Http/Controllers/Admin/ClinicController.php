<?php
// app/Http/Controllers/Admin/ClinicController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClinicController extends Controller
{
    use ApiResponse;

    /**
     * نمایش اطلاعات کلینیک
     */
    public function show()
    {
        $clinic = Clinic::with(['province', 'city'])->first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        // ✅ درست - فقط کلینیک رو برمی‌گردونیم، Accessorها خودشون کار می‌کنن
        return $this->success($clinic);
    }

    /**
     * بروزرسانی کلینیک
     */
    public function update(Request $request)
    {
        $clinic = Clinic::first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'timezone' => 'nullable|string',
            'currency' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'invoice_prefix' => 'nullable|string|max:10',
            'appointment_prefix' => 'nullable|string|max:10',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'theme' => 'nullable|string|max:50',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $clinic->update($data);

            return $this->success(
                $clinic->fresh()->load(['province', 'city']),
                'اطلاعات کلینیک با موفقیت بروزرسانی شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آپلود لوگو
     */
    public function uploadLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,webp,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $clinic = Clinic::first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        try {
            $clinic->uploadLogo($request->file('logo'));

            // ✅ درست - Accessorها خودشون URLها رو برمی‌گردونن
            return $this->success([
                'logo_url' => $clinic->logo_url,
                'logo_thumb' => $clinic->logo_thumb,
                'logo_medium' => $clinic->logo_medium,
                'logo_large' => $clinic->logo_large,
            ], 'لوگو با موفقیت آپلود شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف لوگو
     */
    public function deleteLogo()
    {
        $clinic = Clinic::first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        try {
            $clinic->deleteLogo();
            return $this->success(null, 'لوگو با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آپلود favicon
     */
    public function uploadFavicon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'favicon' => 'required|image|mimes:jpeg,png,webp,svg,ico|max:1024',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $clinic = Clinic::first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        try {
            $clinic->uploadFavicon($request->file('favicon'));

            // ✅ درست - Accessorها خودشون URLها رو برمی‌گردونن
            return $this->success([
                'favicon_url' => $clinic->favicon_url,
                'favicon_icon' => $clinic->favicon_icon,
            ], 'فاوآیکون با موفقیت آپلود شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف favicon
     */
    public function deleteFavicon()
    {
        $clinic = Clinic::first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        try {
            $clinic->deleteFavicon();
            return $this->success(null, 'فاوآیکون با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تنظیمات عمومی (بدون احراز هویت)
     */
    public function publicSettings()
    {
        $clinic = Clinic::with(['province', 'city'])->first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        // ✅ درست - Accessorها خودشون کار می‌کنن
        return $this->success([
            'name' => $clinic->name,
            'address' => $clinic->address,
            'phone' => $clinic->phone,
            'email' => $clinic->email,
            'website' => $clinic->website,
            'logo_url' => $clinic->logo_url,
            'logo_thumb' => $clinic->logo_thumb,
            'logo_medium' => $clinic->logo_medium,
            'logo_large' => $clinic->logo_large,
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
        ]);
    }

    /**
     * تغییر وضعیت کلینیک
     */
    public function toggleStatus()
    {
        $clinic = Clinic::first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        $clinic->update(['is_active' => !$clinic->is_active]);

        return $this->success($clinic->fresh(), 'وضعیت کلینیک تغییر کرد');
    }

    /**
     * لیست کلینیک‌ها (ادمین)
     */
    public function index(Request $request)
    {
        $query = Clinic::with(['province', 'city']);

        if ($request->has('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $clinics = $query->paginate($request->get('per_page', 15));

        // ✅ درست - فقط کلینیک‌ها رو برمی‌گردونیم، Accessorها خودشون کار می‌کنن
        return $this->success($clinics);
    }

    /**
     * ایجاد کلینیک جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'timezone' => 'nullable|string',
            'currency' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $clinic = Clinic::create($request->all());
            return $this->success(
                $clinic->load(['province', 'city']),
                'کلینیک با موفقیت ایجاد شد',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
