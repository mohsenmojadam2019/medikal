<?php
// app/Http/Controllers/Admin/CategoryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\Pharmacy\CategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    use ApiResponse;

    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    // ============================================
    // لیست و نمایش
    // ============================================

    /**
     * لیست دسته‌بندی‌ها
     */
    public function index(Request $request)
    {
        try {
            $categories = $this->categoryService->getAllCategories([
                'is_active' => $request->has('is_active') ? filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN) : null,
                'parent_id' => $request->has('parent_id') ? $request->parent_id : null,
                'search' => $request->search,
            ]);

            // ساختن درخت
            $tree = [];
            foreach ($categories as $category) {
                if (!$category->parent_id) {
                    $category->children = $this->buildTree($categories, $category->id);
                    $tree[] = $category;
                }
            }

            return $this->success([
                'categories' => $categories,
                'tree' => $tree,
                'options' => $this->categoryService->getCategoryOptions(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * نمایش یک دسته‌بندی
     */
    public function show($id)
    {
        try {
            $category = $this->categoryService->getCategory($id);
            $breadcrumb = $this->categoryService->getBreadcrumb($id);

            return $this->success([
                'category' => $category,
                'breadcrumb' => $breadcrumb,
                'children' => $category->children,
                'products_count' => $category->getTotalProductsCount(), // ✅ اصلاح شد
            ]);
        } catch (\Exception $e) {
            return $this->error('دسته‌بندی یافت نشد', 404);
        }
    }

    // ============================================
    // ایجاد و ویرایش
    // ============================================

    /**
     * ایجاد دسته‌بندی جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:product_categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'order' => 'nullable|integer|min:0',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:150',
            'meta_description' => 'nullable|string|max:300',
            'meta_keywords' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();

            if ($request->hasFile('image')) {
                $data['image_file'] = $request->file('image');
            }

            $category = $this->categoryService->createCategory($data);

            return $this->success($category, 'دسته‌بندی با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * به‌روزرسانی دسته‌بندی
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'slug' => 'nullable|string|max:100|unique:product_categories,slug,' . $id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'order' => 'nullable|integer|min:0',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'is_active' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:150',
            'meta_description' => 'nullable|string|max:300',
            'meta_keywords' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();

            if ($request->hasFile('image')) {
                $data['image_file'] = $request->file('image');
            }

            $category = $this->categoryService->updateCategory($id, $data);

            return $this->success($category, 'دسته‌بندی با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف دسته‌بندی
     */
    public function destroy($id)
    {
        try {
            $this->categoryService->deleteCategory($id);
            return $this->success(null, 'دسته‌بندی با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // مدیریت وضعیت
    // ============================================

    /**
     * تغییر وضعیت دسته‌بندی
     */
    public function toggleStatus($id)
    {
        try {
            $category = $this->categoryService->toggleCategoryStatus($id);
            return $this->success($category, 'وضعیت دسته‌بندی با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر ترتیب دسته‌بندی‌ها (Bulk)
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:product_categories,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            foreach ($request->orders as $item) {
                ProductCategory::where('id', $item['id'])->update(['order' => $item['order']]);
            }
            $this->categoryService->clearCache();

            return $this->success(null, 'ترتیب دسته‌بندی‌ها با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // ✅ مدیریت ارتباط با داروها (Products)
    // ============================================

    /**
     * ✅ اتصال داروها به دسته‌بندی
     */
    public function attachProducts(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $this->categoryService->attachProductsToCategory(
                $id,
                $request->product_ids,
                $request->is_primary ?? false
            );

            return $this->success(null, 'داروها با موفقیت به دسته‌بندی متصل شدند');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ✅ قطع ارتباط دارو از دسته‌بندی
     */
    public function detachProduct($id, $productId)
    {
        try {
            $this->categoryService->detachProductFromCategory($id, $productId);
            return $this->success(null, 'دارو با موفقیت از دسته‌بندی جدا شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ✅ دریافت داروهای یک دسته‌بندی
     */
    public function getProducts($id, Request $request)
    {
        try {
            $category = $this->categoryService->getCategory($id);

            $products = $this->categoryService->getCategoryProducts(
                $id,
                $request->all(),
                $request->get('per_page', 15)
            );

            return $this->success([
                'category' => $category,
                'products' => $products,
                'total' => $products->total(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * ✅ دریافت دسته‌بندی‌های یک دارو
     */
    public function getProductCategories($productId)
    {
        try {
            $categories = $this->categoryService->getProductCategories($productId);
            return $this->success($categories, 'دسته‌بندی‌های دارو');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * ✅ تنظیم دسته اصلی یک دارو
     */
    public function setPrimaryCategory(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:product_categories,id',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $this->categoryService->setPrimaryCategory($productId, $request->category_id);
            return $this->success(null, 'دسته اصلی دارو با موفقیت تنظیم شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // Helper
    // ============================================

    private function buildTree($categories, $parentId = null)
    {
        $branch = [];
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                if ($children) {
                    $category->children = $children;
                }
                $branch[] = $category;
            }
        }
        return $branch;
    }
}
