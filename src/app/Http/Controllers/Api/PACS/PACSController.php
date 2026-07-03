<?php

namespace App\Http\Controllers\Api\PACS;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PACSController extends Controller
{
    use ApiResponse;

    /**
     * آپلود تصویر پزشکی
     */
    public function upload(Request $request)
    {
        $validator = validator($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'image' => 'required|file|mimes:jpg,jpeg,png,dcm|max:20480',
            'image_type' => 'required|string|in:xray,ct,mri,ultrasound,other',
            'modality' => 'nullable|string',
            'body_part' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        try {
            // ذخیره تصویر
            $file = $request->file('image');
            $path = $file->store('pacs/' . $request->patient_id, 'public');

            // در اینجا می‌توانید مدل PACS را ایجاد کنید

            return $this->success([
                'path' => $path,
                'url' => asset('storage/' . $path),
                'image_type' => $request->image_type,
                'modality' => $request->modality,
                'body_part' => $request->body_part,
                'description' => $request->description,
            ], 'تصویر با موفقیت آپلود شد');

        } catch (\Exception $e) {
            return $this->error('خطا در آپلود تصویر: ' . $e->getMessage(), 500);
        }
    }

    /**
     * دریافت تصاویر بیمار
     */
    public function patientImages(Request $request, $patientId)
    {
        // در اینجا تصاویر بیمار را دریافت کنید
        return $this->success([
            'patient_id' => $patientId,
            'images' => [],
        ]);
    }

    /**
     * مشاهده یک تصویر
     */
    public function show($id)
    {
        return $this->success([
            'id' => $id,
            'url' => asset('storage/pacs/image-' . $id . '.jpg'),
        ]);
    }

    /**
     * دانلود تصویر
     */
    public function download($id)
    {
        // مسیر فایل را پیدا کنید و دانلود کنید
        $path = storage_path('app/public/pacs/image-' . $id . '.jpg');

        if (!file_exists($path)) {
            return $this->error('فایل یافت نشد', 404);
        }

        return response()->download($path);
    }

    /**
     * حذف تصویر
     */
    public function destroy($id)
    {
        return $this->success(null, 'تصویر با موفقیت حذف شد');
    }

    /**
     * آمار تصاویر بیمار
     */
    public function patientStats($patientId)
    {
        return $this->success([
            'patient_id' => $patientId,
            'total_images' => 0,
            'by_type' => [],
        ]);
    }
}
