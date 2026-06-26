<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SpecialtyController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
    }

    public function index(Request $request)
    {
        $query = Specialty::with('media');

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $specialties = $query->orderBy('name')
            ->paginate($request->get('per_page', 20));

        // اضافه کردن URL عکس‌ها
        $specialties->getCollection()->transform(function ($specialty) {
            $specialty->icon_url = $specialty->icon_url;
            $specialty->icon_thumb = $specialty->icon_thumb;
            $specialty->icon_medium = $specialty->icon_medium;
            $specialty->icon_large = $specialty->icon_large;
            return $specialty;
        });

        return $this->success($specialties);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:specialties,name',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $specialty = Specialty::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ]);

            // اگر عکس ارسال شده
            if ($request->hasFile('icon')) {
                $specialty->addMediaFromRequest('icon')
                    ->toMediaCollection('specialty_icon');
            }

            return $this->success($specialty, 'تخصص با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show($id)
    {
        try {
            $specialty = Specialty::with('media')->findOrFail($id);
            return $this->success($specialty);
        } catch (\Exception $e) {
            return $this->error('تخصص یافت نشد', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $specialty = Specialty::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|string|max:255|unique:specialties,name,' . $id,
                'description' => 'nullable|string',
                'is_active' => 'nullable|boolean',
            ]);

            $data = $request->except(['icon']);
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $specialty->update($data);

            // اگر عکس جدید ارسال شده
            if ($request->hasFile('icon')) {
                $specialty->clearMediaCollection('specialty_icon');
                $specialty->addMediaFromRequest('icon')
                    ->toMediaCollection('specialty_icon');
            }

            return $this->success($specialty, 'تخصص با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $specialty = Specialty::findOrFail($id);
            $specialty->delete();
            return $this->success(null, 'تخصص با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $specialty = Specialty::findOrFail($id);
            $specialty->update(['is_active' => !$specialty->is_active]);
            return $this->success($specialty, 'وضعیت تخصص با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تخصص‌های فعال برای استفاده عمومی
     */
    public function activeSpecialties()
    {
        $specialties = Specialty::active()
            ->with('media')
            ->orderBy('name')
            ->get();

        return $this->success($specialties);
    }
}
