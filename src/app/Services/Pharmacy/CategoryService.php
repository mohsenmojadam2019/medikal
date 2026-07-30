<?php
// app/Services/Pharmacy/CategoryService.php

namespace App\Services\Pharmacy;

use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    protected int $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id', 1);
    }

    // ============================================
    // مدیریت دسته‌بندی‌ها
    // ============================================

    public function getAllCategories(array $filters = []): Collection
    {
        $query = ProductCategory::where('tenant_id', $this->tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        return $query->orderBy('order')->orderBy('name')->get();
    }

    public function getTreeStructure(): Collection
    {
        return Cache::remember('categories_tree_' . $this->tenantId, 3600, function () {
            return ProductCategory::where('tenant_id', $this->tenantId)
                ->where('is_active', true)
                ->root()
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('order')
                        ->orderBy('name');
                }])
                ->orderBy('order')
                ->orderBy('name')
                ->get();
        });
    }

    public function getCategory($id): ProductCategory
    {
        return ProductCategory::where('tenant_id', $this->tenantId)
            ->with(['parent', 'children', 'products'])
            ->findOrFail($id);
    }

    public function getCategoryBySlug($slug): ProductCategory
    {
        return ProductCategory::where('tenant_id', $this->tenantId)
            ->where('slug', $slug)
            ->with(['parent', 'children'])
            ->firstOrFail();
    }

    // ============================================
    // ایجاد و ویرایش
    // ============================================

    public function createCategory(array $data): ProductCategory
    {
        return DB::transaction(function () use ($data) {
            $category = ProductCategory::create([
                'tenant_id' => $this->tenantId,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'order' => $data['order'] ?? 0,
                'icon' => $data['icon'] ?? null,
                'color' => $data['color'] ?? null,
                'image' => $data['image'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_featured' => $data['is_featured'] ?? false,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
            ]);

            if (isset($data['image_file'])) {
                $this->uploadCategoryImage($category, $data['image_file']);
            }

            $this->clearCache();

            Log::info('✅ Category created', [
                'category_id' => $category->id,
                'name' => $category->name,
                'tenant_id' => $this->tenantId,
            ]);

            return $category;
        });
    }

    public function updateCategory($id, array $data): ProductCategory
    {
        return DB::transaction(function () use ($id, $data) {
            $category = $this->getCategory($id);

            $category->update([
                'name' => $data['name'] ?? $category->name,
                'slug' => $data['slug'] ?? $category->slug,
                'description' => $data['description'] ?? $category->description,
                'parent_id' => $data['parent_id'] ?? $category->parent_id,
                'order' => $data['order'] ?? $category->order,
                'icon' => $data['icon'] ?? $category->icon,
                'color' => $data['color'] ?? $category->color,
                'image' => $data['image'] ?? $category->image,
                'is_active' => $data['is_active'] ?? $category->is_active,
                'is_featured' => $data['is_featured'] ?? $category->is_featured,
                'meta_title' => $data['meta_title'] ?? $category->meta_title,
                'meta_description' => $data['meta_description'] ?? $category->meta_description,
                'meta_keywords' => $data['meta_keywords'] ?? $category->meta_keywords,
            ]);

            if (isset($data['image_file'])) {
                $this->uploadCategoryImage($category, $data['image_file']);
            }

            $this->clearCache();

            Log::info('✅ Category updated', [
                'category_id' => $category->id,
                'name' => $category->name,
            ]);

            return $category->fresh();
        });
    }

    public function deleteCategory($id): bool
    {
        return DB::transaction(function () use ($id) {
            $category = $this->getCategory($id);

            if ($category->children()->exists()) {
                throw new \Exception('این دسته دارای زیردسته است. ابتدا زیردسته‌ها را حذف کنید.');
            }

            $category->products()->detach();
            $category->delete();
            $this->clearCache();

            Log::info('🗑️ Category deleted', [
                'category_id' => $category->id,
                'name' => $category->name,
            ]);

            return true;
        });
    }

    public function toggleCategoryStatus($id): ProductCategory
    {
        $category = $this->getCategory($id);
        $category->update(['is_active' => !$category->is_active]);
        $this->clearCache();
        return $category->fresh();
    }

    // ============================================
    // ارتباط با داروها (Product)
    // ============================================

    /**
     * ✅ اتصال داروها به دسته‌بندی
     */
    public function attachProductsToCategory($categoryId, array $productIds, bool $isPrimary = false): void
    {
        $category = $this->getCategory($categoryId);

        $attachData = [];
        foreach ($productIds as $productId) {
            $attachData[$productId] = ['is_primary' => $isPrimary];
        }

        $category->products()->syncWithoutDetaching($attachData);

        $this->clearCache();
    }

    /**
     * ✅ قطع ارتباط دارو از دسته‌بندی
     */
    public function detachProductFromCategory($categoryId, $productId): void
    {
        $category = $this->getCategory($categoryId);
        $category->products()->detach($productId);
        $this->clearCache();
    }

    /**
     * ✅ تنظیم دسته اصلی دارو
     */
    public function setPrimaryCategory($productId, $categoryId): void
    {
        $product = Product::findOrFail($productId);

        $product->categories()->updateExistingPivot(null, ['is_primary' => false]);

        if ($categoryId) {
            $product->categories()->updateExistingPivot($categoryId, ['is_primary' => true]);
        }

        $this->clearCache();
    }

    // ============================================
    // دریافت داروها بر اساس دسته‌بندی
    // ============================================

    /**
     * ✅ دریافت داروهای یک دسته (به همراه زیردسته‌ها)
     */
    public function getCategoryProducts($categoryId, array $filters = [], int $perPage = 15)
    {
        $category = $this->getCategory($categoryId);

        $categoryIds = $category->getAllChildrenIds();
        $categoryIds[] = $categoryId;

        $query = Product::where('is_active', true)
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('category_id', $categoryIds);
            });

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('generic_name', 'LIKE', "%{$filters['search']}%");
            });
        }

        if (isset($filters['requires_prescription'])) {
            $query->where('requires_prescription', $filters['requires_prescription']);
        }

        if (isset($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        if (isset($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        if (isset($filters['pharmacy_id'])) {
            $query->where('pharmacy_id', $filters['pharmacy_id']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * ✅ دریافت دسته‌بندی‌های یک دارو
     */
    public function getProductCategories($productId): Collection
    {
        $product = Product::findOrFail($productId);
        return $product->categories;
    }

    // ============================================
    // Breadcrumb
    // ============================================

    public function getBreadcrumb($categoryId): array
    {
        $breadcrumb = [];
        $category = $this->getCategory($categoryId);

        while ($category) {
            array_unshift($breadcrumb, [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'url' => $category->url,
            ]);
            $category = $category->parent;
        }

        return $breadcrumb;
    }

    // ============================================
    // Utility
    // ============================================

    public function uploadCategoryImage(ProductCategory $category, $file): void
    {
        $path = $file->store('categories/' . $category->id, 'public');
        $category->update(['image' => $path]);
    }

    public function clearCache(): void
    {
        Cache::forget('categories_tree_' . $this->tenantId);
        Cache::forget('categories_flat_' . $this->tenantId);
    }

    public function getCategoryOptions($parentId = null, $prefix = ''): array
    {
        $options = [];
        $categories = ProductCategory::where('tenant_id', $this->tenantId)
            ->where('parent_id', $parentId)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $options[$category->id] = $prefix . $category->name;
            if ($category->children()->exists()) {
                $options = array_merge($options, $this->getCategoryOptions($category->id, $prefix . '— '));
            }
        }

        return $options;
    }

    public function getPopularCategories(int $limit = 10): Collection
    {
        return ProductCategory::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('products_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
