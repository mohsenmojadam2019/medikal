<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpecialtyMediaController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
    }

    /**
     * آپلود عکس برای تخصص
     */
    public function uploadIcon(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'icon' => 'required|image|mimes:jpeg,png,webp,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $specialty = Specialty::findOrFail($id);

            // حذف عکس قبلی
            $specialty->clearMediaCollection('specialty_icon');

            // آپلود عکس جدید
            $specialty->addMediaFromRequest('icon')
                ->toMediaCollection('specialty_icon');

            return $this->success([
                'id' => $specialty->id,
                'name' => $specialty->name,
                'icon_url' => $specialty->icon_url,
                'icon_thumb' => $specialty->icon_thumb,
                'icon_medium' => $specialty->icon_medium,
                'icon_large' => $specialty->icon_large,
            ], 'عکس تخصص با موفقیت آپلود شد');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف عکس تخصص
     */
    public function deleteIcon($id)
    {
        try {
            $specialty = Specialty::findOrFail($id);
            $specialty->clearMediaCollection('specialty_icon');

            return $this->success(null, 'عکس تخصص با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت عکس تخصص
     */
    public function getIcon($id)
    {
        try {
            $specialty = Specialty::findOrFail($id);
            return $this->success([
                'id' => $specialty->id,
                'name' => $specialty->name,
                'icon_url' => $specialty->icon_url,
                'icon_thumb' => $specialty->icon_thumb,
                'icon_medium' => $specialty->icon_medium,
                'icon_large' => $specialty->icon_large,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
