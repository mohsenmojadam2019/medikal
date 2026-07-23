<?php
// app/Http/Controllers/Api/PACS/PACSController.php

namespace App\Http\Controllers\Api\PACS;

use App\Http\Controllers\Controller;
use App\Models\PACS\MedicalImage;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PACSController extends Controller
{
    use ApiResponse;

    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    /**
     * آپلود تصویر پزشکی
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'clinic_id' => 'nullable|exists:clinics,id',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'admission_id' => 'nullable|exists:admissions,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'image' => 'required|file|mimes:jpg,jpeg,png,dcm|max:20480',
            'image_type' => 'required|string|in:xray,ct,mri,ultrasound,pet,spect,mammogram,dental,other',
            'modality' => 'nullable|string|in:DX,CR,CT,MR,US,PT,NM,MG,IO',
            'body_part' => 'nullable|string',
            'description' => 'nullable|string',
            'study_date' => 'nullable|date',
            'report' => 'nullable|string',
            'is_confidential' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            $file = $request->file('image');

            // بررسی وجود پوشه
            $patientFolder = 'pacs/' . $request->patient_id . '/' . date('Y/m');
            $path = $file->store($patientFolder, 'public');

            // ایجاد رکورد در دیتابیس
            $image = MedicalImage::create([
                'tenant_id' => $this->tenantId,
                'patient_id' => $request->patient_id,
                'doctor_id' => $request->doctor_id ?? $this->getDoctorId($user),
                'clinic_id' => $request->clinic_id,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'admission_id' => $request->admission_id,
                'appointment_id' => $request->appointment_id,
                'image_type' => $request->image_type,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'study_uid' => $this->generateStudyUID(),
                'series_uid' => $this->generateSeriesUID(),
                'instance_uid' => $this->generateInstanceUID(),
                'body_part' => $request->body_part,
                'modality' => $request->modality,
                'description' => $request->description,
                'study_date' => $request->study_date ?? now(),
                'report' => $request->report,
                'is_confidential' => $request->is_confidential ?? false,
                'uploaded_by' => $user->id,
                'metadata' => $request->metadata,
            ]);

            return $this->success([
                'id' => $image->id,
                'path' => $path,
                'url' => $image->file_url,
                'image_type' => $request->image_type,
                'image_type_label' => $image->image_type_label,
                'modality' => $request->modality,
                'modality_label' => $image->modality_label,
                'body_part' => $request->body_part,
                'description' => $request->description,
                'file_size' => $image->file_size_display,
                'study_date' => $image->study_date?->format('Y-m-d H:i'),
            ], 'تصویر با موفقیت آپلود شد');

        } catch (\Exception $e) {
            return $this->error('خطا در آپلود تصویر: ' . $e->getMessage(), 500);
        }
    }

    /**
     * دریافت تصاویر یک بیمار
     */
    public function patientImages(Request $request, $patientId)
    {
        try {
            $query = MedicalImage::where('patient_id', $patientId)
                ->where('tenant_id', $this->tenantId)
                ->with(['doctor', 'clinic', 'province', 'city']);

            // فیلتر بر اساس نوع تصویر
            if ($request->has('image_type')) {
                $query->where('image_type', $request->image_type);
            }

            // فیلتر بر اساس کلینیک
            if ($request->has('clinic_id') && $request->clinic_id) {
                $query->where('clinic_id', $request->clinic_id);
            }

            // فیلتر بر اساس تاریخ
            if ($request->has('from_date')) {
                $query->whereDate('study_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('study_date', '<=', $request->to_date);
            }

            // فیلتر بر اساس محرمانه بودن
            if ($request->has('confidential')) {
                $query->where('is_confidential', $request->confidential);
            }

            $images = $query->orderBy('study_date', 'desc')
                ->paginate($request->get('per_page', 20));

            // اضافه کردن URL به هر تصویر
            $images->getCollection()->transform(function ($image) {
                $image->url = $image->file_url;
                return $image;
            });

            return $this->success($images);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * نمایش یک تصویر
     */
    public function show($id)
    {
        try {
            $image = MedicalImage::where('tenant_id', $this->tenantId)
                ->with(['patient', 'doctor', 'clinic', 'province', 'city'])
                ->findOrFail($id);

            // بررسی دسترسی (فقط بیمار، پزشک یا ادمین)
            $user = auth()->user();
            if (!$this->hasAccess($image, $user)) {
                return $this->error('شما دسترسی به این تصویر ندارید', 403);
            }

            $image->url = $image->file_url;

            return $this->success($image);

        } catch (\Exception $e) {
            return $this->error('تصویر یافت نشد', 404);
        }
    }

    /**
     * دانلود تصویر
     */
    public function download($id)
    {
        try {
            $image = MedicalImage::where('tenant_id', $this->tenantId)->findOrFail($id);

            // بررسی دسترسی
            $user = auth()->user();
            if (!$this->hasAccess($image, $user)) {
                return $this->error('شما دسترسی به این تصویر ندارید', 403);
            }

            $path = storage_path('app/public/' . $image->file_path);

            if (!file_exists($path)) {
                return $this->error('فایل یافت نشد', 404);
            }

            return response()->download($path, $image->file_name);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * حذف تصویر
     */
    public function destroy($id)
    {
        try {
            $image = MedicalImage::where('tenant_id', $this->tenantId)->findOrFail($id);

            // فقط ادمین یا پزشک می‌تواند حذف کند
            $user = auth()->user();
            if (!$user->isAdmin() && $image->doctor_id != $this->getDoctorId($user)) {
                return $this->error('شما دسترسی به حذف این تصویر ندارید', 403);
            }

            // حذف فایل از دیسک
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
     * آمار تصاویر بیمار
     */
    public function patientStats($patientId)
    {
        try {
            $stats = [
                'patient_id' => $patientId,
                'total_images' => MedicalImage::where('patient_id', $patientId)
                    ->where('tenant_id', $this->tenantId)
                    ->count(),
                'by_type' => MedicalImage::where('patient_id', $patientId)
                    ->where('tenant_id', $this->tenantId)
                    ->selectRaw('image_type, COUNT(*) as count')
                    ->groupBy('image_type')
                    ->get()
                    ->map(function ($item) {
                        $labels = [
                            'xray' => 'رادیوگرافی',
                            'ct' => 'سی‌تی اسکن',
                            'mri' => 'ام‌آر‌آی',
                            'ultrasound' => 'سونوگرافی',
                            'pet' => 'پت اسکن',
                            'spect' => 'اسپکت',
                            'mammogram' => 'ماموگرافی',
                            'dental' => 'دندانپزشکی',
                            'other' => 'سایر',
                        ];
                        return [
                            'type' => $item->image_type,
                            'label' => $labels[$item->image_type] ?? $item->image_type,
                            'count' => $item->count,
                        ];
                    }),
                'by_modality' => MedicalImage::where('patient_id', $patientId)
                    ->where('tenant_id', $this->tenantId)
                    ->whereNotNull('modality')
                    ->selectRaw('modality, COUNT(*) as count')
                    ->groupBy('modality')
                    ->get()
                    ->map(function ($item) {
                        $labels = [
                            'DX' => 'رادیوگرافی دیجیتال',
                            'CR' => 'رادیوگرافی کامپیوتری',
                            'CT' => 'سی‌تی اسکن',
                            'MR' => 'ام‌آر‌آی',
                            'US' => 'سونوگرافی',
                            'PT' => 'پت اسکن',
                            'NM' => 'پزشکی هسته‌ای',
                            'MG' => 'ماموگرافی',
                            'IO' => 'رادیوگرافی داخل دهانی',
                        ];
                        return [
                            'modality' => $item->modality,
                            'label' => $labels[$item->modality] ?? $item->modality,
                            'count' => $item->count,
                        ];
                    }),
                'by_month' => MedicalImage::where('patient_id', $patientId)
                    ->where('tenant_id', $this->tenantId)
                    ->selectRaw('DATE_FORMAT(study_date, "%Y-%m") as month, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month', 'desc')
                    ->get(),
                'total_size' => MedicalImage::where('patient_id', $patientId)
                    ->where('tenant_id', $this->tenantId)
                    ->sum('file_size'),
                'confidential_count' => MedicalImage::where('patient_id', $patientId)
                    ->where('tenant_id', $this->tenantId)
                    ->where('is_confidential', true)
                    ->count(),
            ];

            return $this->success($stats);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * لیست تصاویر یک کلینیک
     */
    public function clinicImages(Request $request, $clinicId)
    {
        try {
            $query = MedicalImage::where('clinic_id', $clinicId)
                ->where('tenant_id', $this->tenantId)
                ->with(['patient', 'doctor']);

            if ($request->has('from_date')) {
                $query->whereDate('study_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('study_date', '<=', $request->to_date);
            }

            $images = $query->orderBy('study_date', 'desc')
                ->paginate($request->get('per_page', 20));

            return $this->success($images);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * جستجوی تصاویر
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2',
            'patient_id' => 'nullable|exists:patients,id',
            'clinic_id' => 'nullable|exists:clinics,id',
            'image_type' => 'nullable|string',
            'modality' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $query = MedicalImage::where('tenant_id', $this->tenantId)
                ->with(['patient', 'doctor', 'clinic']);

            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                    ->orWhere('file_name', 'LIKE', "%{$search}%")
                    ->orWhere('report', 'LIKE', "%{$search}%")
                    ->orWhere('body_part', 'LIKE', "%{$search}%")
                    ->orWhereHas('patient', function ($q2) use ($search) {
                        $q2->where('full_name', 'LIKE', "%{$search}%")
                            ->orWhere('national_code', 'LIKE', "%{$search}%");
                    });
            });

            if ($request->has('patient_id') && $request->patient_id) {
                $query->where('patient_id', $request->patient_id);
            }

            if ($request->has('clinic_id') && $request->clinic_id) {
                $query->where('clinic_id', $request->clinic_id);
            }

            if ($request->has('image_type') && $request->image_type) {
                $query->where('image_type', $request->image_type);
            }

            if ($request->has('modality') && $request->modality) {
                $query->where('modality', $request->modality);
            }

            $images = $query->orderBy('study_date', 'desc')
                ->paginate($request->get('per_page', 20));

            return $this->success($images);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ========== Private Methods ==========

    private function getDoctorId($user): ?int
    {
        $doctor = Doctor::where('user_id', $user->id)->first();
        return $doctor?->id;
    }

    private function hasAccess(MedicalImage $image, $user): bool
    {
        // ادمین دسترسی کامل دارد
        if ($user->isAdmin()) {
            return true;
        }

        // خود بیمار
        if ($image->patient->user_id == $user->id) {
            return true;
        }

        // پزشک معالج
        $doctor = Doctor::where('user_id', $user->id)->first();
        if ($doctor && $image->doctor_id == $doctor->id) {
            return true;
        }

        // پزشک بیمار
        if ($doctor && $image->patient->doctor_id == $doctor->id) {
            return true;
        }

        return false;
    }
    /**
     * تغییر وضعیت تصویر (فعال/غیرفعال)
     */
    public function toggleStatus($id)
    {
        try {
            $image = MedicalImage::where('tenant_id', $this->tenantId)->findOrFail($id);
            $image->update(['is_active' => !$image->is_active]);
            return $this->success($image->fresh(), 'وضعیت تصویر با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
    private function generateStudyUID(): string
    {
        return '1.2.840.113619.2.55.1.1.' . time() . '.' . rand(10000, 99999);
    }

    private function generateSeriesUID(): string
    {
        return '1.2.840.113619.2.55.1.2.' . time() . '.' . rand(10000, 99999);
    }

    private function generateInstanceUID(): string
    {
        return '1.2.840.113619.2.55.1.3.' . time() . '.' . rand(10000, 99999);
    }
}
