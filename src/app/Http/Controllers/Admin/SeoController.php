<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seo;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $seo = Seo::with(['seoable'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success($seo);
    }

    public function show($id)
    {
        try {
            $seo = Seo::with(['seoable'])->findOrFail($id);
            return $this->success($seo);
        } catch (\Exception $e) {
            return $this->error('SEO یافت نشد', 404);
        }
    }

    public function getByModel(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        $modelClass = 'App\\Models\\' . ucfirst($request->type);
        $model = $modelClass::find($request->id);

        if (!$model) {
            return $this->error('مدل یافت نشد', 404);
        }

        $seo = $model->seo;

        if (!$seo) {
            $seo = $model->seo()->create([
                'title' => $model->getSeoTitleAttribute(),
                'description' => $model->getSeoDescriptionAttribute(),
                'keywords' => $model->getSeoKeywordsAttribute(),
                'robots' => 'index, follow',
            ]);
        }

        return $this->success($seo);
    }

    public function store(Request $request)
    {
        $request->validate([
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

        try {
            $modelClass = 'App\\Models\\' . ucfirst($request->seoable_type);
            $model = $modelClass::find($request->seoable_id);

            if (!$model) {
                return $this->error('مدل یافت نشد', 404);
            }

            $seo = $model->seo()->updateOrCreate(
                ['seoable_type' => $request->seoable_type, 'seoable_id' => $request->seoable_id],
                $request->except(['seoable_type', 'seoable_id'])
            );

            return $this->success($seo, 'SEO با موفقیت ذخیره شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $seo = Seo::findOrFail($id);

            $request->validate([
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

            $seo->update($request->all());

            return $this->success($seo, 'SEO با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $seo = Seo::findOrFail($id);
            $seo->delete();
            return $this->success(null, 'SEO با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
