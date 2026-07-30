<?php

// app/Http/Controllers/Admin/BrandController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    use ApiResponse;

    /**
     * لیست برندها
     */
    public function index(Request $request)
    {
        $query = Brand::where('tenant_id', session('tenant_id', 1));

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $brands = $query->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return $this->success($brands);
    }

    /**
     * نمایش یک برند
     */
    public function show($id)
    {
        try {
            $brand = Brand::where('tenant_id', session('tenant_id', 1))
                ->with(['products' => function ($query) {
                    $query->where('is_active', true)->limit(10);
                }])
                ->findOrFail($id);

            return $this->success($brand);
        } catch (\Exception $e) {
            return $this->error('برند یافت نشد', 404);
        }
    }

    /**
     * ایجاد برند جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:brands,name',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $brand = Brand::create([
                'tenant_id' => session('tenant_id', 1),
                'name' => $request->name,
                'slug' => $request->slug ?? Str::slug($request->name),
                'website' => $request->website,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ]);

            if ($request->hasFile('logo')) {
                $brand->addMedia($request->file('logo'))->toMediaCollection('brand_logo');
            }

            return $this->success($brand, 'برند با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * بروزرسانی برند
     */
    public function update(Request $request, $id)
    {
        try {
            $brand = Brand::where('tenant_id', session('tenant_id', 1))->findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('برند یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100|unique:brands,name,' . $id,
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $brand->update($request->all());

            if ($request->hasFile('logo')) {
                $brand->clearMediaCollection('brand_logo');
                $brand->addMedia($request->file('logo'))->toMediaCollection('brand_logo');
            }

            return $this->success($brand->fresh(), 'برند با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف برند
     */
    public function destroy($id)
    {
        try {
            $brand = Brand::where('tenant_id', session('tenant_id', 1))->findOrFail($id);

            // بررسی اینکه برند محصول ندارد
            if ($brand->products()->count() > 0) {
                return $this->error('این برند دارای محصول است و قابل حذف نیست', 400);
            }

            $brand->clearMediaCollection('brand_logo');
            $brand->delete();

            return $this->success(null, 'برند با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت برند
     */
    public function toggleStatus($id)
    {
        try {
            $brand = Brand::where('tenant_id', session('tenant_id', 1))->findOrFail($id);
            $brand->update(['is_active' => !$brand->is_active]);

            return $this->success($brand->fresh(), 'وضعیت برند با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف لوگو برند
     */
    public function deleteLogo($id)
    {
        try {
            $brand = Brand::where('tenant_id', session('tenant_id', 1))->findOrFail($id);
            $brand->clearMediaCollection('brand_logo');

            return $this->success(null, 'لوگو با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت تمام برندها (بدون صفحه‌بندی - برای سلیکت)
     */
    public function all()
    {
        $brands = Brand::where('tenant_id', session('tenant_id', 1))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return $this->success($brands);
    }
}
