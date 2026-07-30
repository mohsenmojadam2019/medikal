<?php
// app/Http/Controllers/Api/CategoryController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * دریافت ساختار درختی دسته‌بندی‌ها
     */
    public function tree()
    {
        $categories = ProductCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('order')
                    ->orderBy('name')
                    ->with(['children' => function ($q) {
                        $q->where('is_active', true)
                            ->orderBy('order')
                            ->orderBy('name');
                    }]);
            }])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return $this->success($categories);
    }

    /**
     * دریافت دسته‌بندی‌های سطح اول
     */
    public function root()
    {
        $categories = ProductCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return $this->success($categories);
    }

    /**
     * دریافت یک دسته‌بندی با فرزندان
     */
    public function show($slug)
    {
        try {
            $category = ProductCategory::where('slug', $slug)
                ->where('is_active', true)
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('order')
                        ->orderBy('name');
                }])
                ->firstOrFail();

            // مسیر دسته‌بندی (Breadcrumb)
            $breadcrumb = [];
            $current = $category;
            while ($current) {
                array_unshift($breadcrumb, [
                    'id' => $current->id,
                    'name' => $current->name,
                    'slug' => $current->slug,
                ]);
                $current = $current->parent;
            }

            return $this->success([
                'category' => $category,
                'breadcrumb' => $breadcrumb,
                'children' => $category->children,
                'total_products' => $category->getTotalProductsCount(),
            ]);
        } catch (\Exception $e) {
            return $this->error('دسته‌بندی یافت نشد', 404);
        }
    }

    /**
     * دسته‌بندی‌های محبوب (با بیشترین محصول)
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 10);

        $categories = ProductCategory::where('is_active', true)
            ->withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('products_count', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($categories);
    }
}
