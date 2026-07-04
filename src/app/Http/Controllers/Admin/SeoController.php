<?php
// app/Http/Controllers/Admin/SeoController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seo;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeoController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $tenantId = session('tenant_id', 1);

            $query = Seo::where('tenant_id', $tenantId)
                ->with(['seoable']);

            // جستجو
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('keywords', 'like', "%{$search}%")
                        ->orWhereHas('seoable', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%")
                                ->orWhere('title', 'like', "%{$search}%")
                                ->orWhere('full_name', 'like', "%{$search}%");
                        });
                });
            }

            // فیلتر بر اساس نوع
            if ($request->has('seoable_type') && !empty($request->seoable_type)) {
                $query->where('seoable_type', $request->seoable_type);
            }

            $seo = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return $this->success($seo);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $seo = Seo::where('tenant_id', $tenantId)
                ->with(['seoable'])
                ->findOrFail($id);
            return $this->success($seo);
        } catch (\Exception $e) {
            return $this->error('SEO یافت نشد', 404);
        }
    }

    public function getByModel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $modelClass = 'App\\Models\\' . ucfirst($request->type);

            if (!class_exists($modelClass)) {
                return $this->error('مدل یافت نشد', 404);
            }

            $model = $modelClass::find($request->id);

            if (!$model) {
                return $this->error('مدل یافت نشد', 404);
            }

            // بررسی وجود تریل seo
            if (!method_exists($model, 'seo')) {
                return $this->error('مدل تریل seo ندارد', 400);
            }

            $seo = $model->seo;

            if (!$seo) {
                // ایجاد سئو پیش‌فرض
                $seo = $model->seo()->create([
                    'tenant_id' => session('tenant_id', 1),
                    'title' => method_exists($model, 'getSeoTitleAttribute') ? $model->getSeoTitleAttribute() : null,
                    'description' => method_exists($model, 'getSeoDescriptionAttribute') ? $model->getSeoDescriptionAttribute() : null,
                    'keywords' => method_exists($model, 'getSeoKeywordsAttribute') ? $model->getSeoKeywordsAttribute() : null,
                    'robots' => 'index, follow',
                ]);
            }

            return $this->success($seo);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seoable_type' => 'required|string',
            'seoable_id' => 'required|integer',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'keywords' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:255',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string|max:500',
            'twitter_image' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:255',
            'robots' => 'nullable|string|max:255',
            'schema_json' => 'nullable|array',
            'meta_tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $modelClass = 'App\\Models\\' . ucfirst($request->seoable_type);

            if (!class_exists($modelClass)) {
                return $this->error('مدل یافت نشد', 404);
            }

            $model = $modelClass::find($request->seoable_id);

            if (!$model) {
                return $this->error('مدل یافت نشد', 404);
            }

            $data = $request->all();
            $data['tenant_id'] = session('tenant_id', 1);

            $seo = $model->seo()->updateOrCreate(
                [
                    'seoable_type' => $request->seoable_type,
                    'seoable_id' => $request->seoable_id,
                ],
                $data
            );

            return $this->success($seo, 'SEO با موفقیت ذخیره شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $seo = Seo::where('tenant_id', $tenantId)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:500',
                'keywords' => 'nullable|string|max:255',
                'og_title' => 'nullable|string|max:255',
                'og_description' => 'nullable|string|max:500',
                'og_image' => 'nullable|string|max:255',
                'twitter_title' => 'nullable|string|max:255',
                'twitter_description' => 'nullable|string|max:500',
                'twitter_image' => 'nullable|string|max:255',
                'canonical_url' => 'nullable|string|max:255',
                'robots' => 'nullable|string|max:255',
                'schema_json' => 'nullable|array',
                'meta_tags' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
            }

            $seo->update($request->all());

            return $this->success($seo->fresh(), 'SEO با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $seo = Seo::where('tenant_id', $tenantId)->findOrFail($id);
            $seo->delete();
            return $this->success(null, 'SEO با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
