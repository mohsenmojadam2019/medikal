<?php
// app/Http/Controllers/Admin/PACSController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PACS\MedicalImage;
use App\Models\Clinic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PACSController extends Controller
{
    use ApiResponse;

    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    /**
     * لیست همه تصاویر (ادمین)
     */
    public function index(Request $request)
    {
        $query = MedicalImage::where('tenant_id', $this->tenantId)
            ->with(['patient', 'doctor', 'clinic', 'province', 'city']);

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

        // فیلتر بر اساس بیمار
        if ($request->has('patient_id') && $request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }

        // فیلتر بر اساس نوع
        if ($request->has('image_type') && $request->image_type) {
            $query->where('image_type', $request->image_type);
        }

        // فیلتر بر اساس تاریخ
        if ($request->has('from_date')) {
            $query->whereDate('study_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('study_date', '<=', $request->to_date);
        }

        $images = $query->orderBy('study_date', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success($images);
    }

    /**
     * نمایش یک تصویر (ادمین)
     */
    public function show($id)
    {
        try {
            $image = MedicalImage::where('tenant_id', $this->tenantId)
                ->with(['patient', 'doctor', 'clinic', 'province', 'city'])
                ->findOrFail($id);

            return $this->success($image);

        } catch (\Exception $e) {
            return $this->error('تصویر یافت نشد', 404);
        }
    }

    /**
     * حذف تصویر (ادمین)
     */
    public function destroy($id)
    {
        try {
            $image = MedicalImage::where('tenant_id', $this->tenantId)->findOrFail($id);

            if (Storage::disk('public')->exists($image->file_path)) {
                Storage::disk('public')->delete($image->file_path);
            }

            $image->delete();

            return $this->success(null, 'تصویر با موفقیت حذف شد');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار کلی تصاویر (ادمین)
     */
    public function stats(Request $request)
    {
        try {
            $query = MedicalImage::where('tenant_id', $this->tenantId);

            if ($request->has('clinic_id') && $request->clinic_id) {
                $query->where('clinic_id', $request->clinic_id);
            }

            $stats = [
                'total_images' => (clone $query)->count(),
                'by_type' => (clone $query)
                    ->selectRaw('image_type, COUNT(*) as count')
                    ->groupBy('image_type')
                    ->get(),
                'by_modality' => (clone $query)
                    ->whereNotNull('modality')
                    ->selectRaw('modality, COUNT(*) as count')
                    ->groupBy('modality')
                    ->get(),
                'by_clinic' => (clone $query)
                    ->selectRaw('clinic_id, COUNT(*) as count')
                    ->groupBy('clinic_id')
                    ->with('clinic')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'clinic_id' => $item->clinic_id,
                            'clinic_name' => $item->clinic?->name,
                            'count' => $item->count,
                        ];
                    }),
                'by_month' => (clone $query)
                    ->selectRaw('DATE_FORMAT(study_date, "%Y-%m") as month, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month', 'desc')
                    ->limit(12)
                    ->get(),
                'total_size' => (clone $query)->sum('file_size'),
                'confidential_count' => (clone $query)->where('is_confidential', true)->count(),
                'today_uploads' => (clone $query)->whereDate('created_at', today())->count(),
            ];

            return $this->success($stats);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
