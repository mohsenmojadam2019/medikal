<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClinicController extends Controller
{
    use ApiResponse;

    /**
     * نمایش اطلاعات کلینیک
     */
    public function show()
    {
        $clinic = Clinic::first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

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
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:1024',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->except(['logo', 'favicon']);

            // آپلود لوگو
            if ($request->hasFile('logo')) {
                if ($clinic->logo) {
                    Storage::disk('public')->delete($clinic->logo);
                }
                $path = $request->file('logo')->store('clinics/logos', 'public');
                $data['logo'] = $path;
            }

            // آپلود favicon
            if ($request->hasFile('favicon')) {
                if ($clinic->favicon) {
                    Storage::disk('public')->delete($clinic->favicon);
                }
                $path = $request->file('favicon')->store('clinics/favicons', 'public');
                $data['favicon'] = $path;
            }

            $clinic->update($data);

            return $this->success($clinic->fresh(), 'اطلاعات کلینیک با موفقیت بروزرسانی شد');
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
            'logo' => 'required|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        $clinic = Clinic::first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        try {
            if ($clinic->logo) {
                Storage::disk('public')->delete($clinic->logo);
            }

            $path = $request->file('logo')->store('clinics/logos', 'public');
            $clinic->update(['logo' => $path]);

            return $this->success([
                'logo_url' => $clinic->logo_url,
            ], 'لوگو با موفقیت آپلود شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تنظیمات عمومی (بدون احراز هویت)
     */
    public function publicSettings()
    {
        $clinic = Clinic::first();

        if (!$clinic) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        return $this->success([
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
}
