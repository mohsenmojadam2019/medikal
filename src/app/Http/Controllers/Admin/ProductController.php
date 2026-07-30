<?php
// app/Http/Controllers/Admin/ProductController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Pharmacy;
use App\Models\Brand;
use App\Models\ProductTag;
use App\Models\ProductAttribute;
use App\Models\ProductCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * ✅ لیست محصولات (با فیلترهای کامل)
     */
    public function index(Request $request)
    {
        $query = Product::with(['pharmacy', 'brand', 'categories', 'tags']);

        // ✅ فیلتر بر اساس داروخانه
        if ($request->has('pharmacy_id') && $request->pharmacy_id) {
            $query->where('pharmacy_id', $request->pharmacy_id);
        }

        // ✅ فیلتر بر اساس برند
        if ($request->has('brand_id') && $request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        // ✅ فیلتر بر اساس دسته‌بندی
        if ($request->has('category_id') && $request->category_id) {
            $query->inCategory($request->category_id);
        }

        // ✅ جستجو
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // ✅ فیلتر بر اساس نیاز به نسخه
        if ($request->has('requires_prescription')) {
            if ($request->requires_prescription) {
                $query->requiresPrescription();
            } else {
                $query->overTheCounter();
            }
        }

        // ✅ فیلتر بر اساس موجودی
        if ($request->has('in_stock')) {
            if ($request->in_stock) {
                $query->where('stock', '>', 0);
            } else {
                $query->where('stock', '<=', 0);
            }
        }

        // ✅ فیلتر بر اساس تخفیف
        if ($request->has('on_sale')) {
            $query->onSale();
        }

        // ✅ فیلتر بر اساس فعال
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // ✅ مرتب‌سازی
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['name', 'price', 'stock', 'created_at', 'avg_rating'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate($request->get('per_page', 20));

        return $this->success($products);
    }

    /**
     * ✅ نمایش یک محصول
     */
    public function show($id)
    {
        try {
            $product = Product::with([
                'pharmacy',
                'brand',
                'categories',
                'tags',
                'attributeValues.attribute',
                'reviews' => function ($query) {
                    $query->approved()->with(['user', 'replies.user']);
                }
            ])->findOrFail($id);

            return $this->success($product);
        } catch (\Exception $e) {
            return $this->error('محصول یافت نشد', 404);
        }
    }

    /**
     * ✅ ایجاد محصول جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pharmacy_id' => 'nullable|exists:pharmacies,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'code' => 'nullable|string|unique:products,code',
            'category' => 'nullable|string|max:100',
            'form' => 'nullable|string|max:50',
            'strength' => 'nullable|string|max:50',
            'manufacturer' => 'nullable|string|max:255',
            'country_of_origin' => 'nullable|string|max:100',
            'product_form' => 'nullable|string|max:50',
            'volume' => 'nullable|string|max:50',
            'container_type' => 'nullable|string|max:50',
            'container_material' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'stock' => 'nullable|integer|min:0',
            'requires_prescription' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'product_features' => 'nullable|array',
            'usage_instructions' => 'nullable|array',
            'tags' => 'nullable|array',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:product_categories,id',
            'primary_category_id' => 'nullable|exists:product_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:product_tags,id',
            'attributes' => 'nullable|array',
            'cover_image' => 'nullable|image|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $product = app(\App\Services\Pharmacy\ProductService::class)->createProduct($request->all());
            return $this->success($product, 'محصول با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ✅ بروزرسانی محصول
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('محصول یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'pharmacy_id' => 'nullable|exists:pharmacies,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'sometimes|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'code' => 'sometimes|string|unique:products,code,' . $id,
            'category' => 'nullable|string|max:100',
            'form' => 'nullable|string|max:50',
            'strength' => 'nullable|string|max:50',
            'manufacturer' => 'nullable|string|max:255',
            'country_of_origin' => 'nullable|string|max:100',
            'product_form' => 'nullable|string|max:50',
            'volume' => 'nullable|string|max:50',
            'container_type' => 'nullable|string|max:50',
            'container_material' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'dimensions' => 'nullable|string|max:50',
            'price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'stock' => 'nullable|integer|min:0',
            'requires_prescription' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'product_features' => 'nullable|array',
            'usage_instructions' => 'nullable|array',
            'tags' => 'nullable|array',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:product_categories,id',
            'primary_category_id' => 'nullable|exists:product_categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:product_tags,id',
            'attributes' => 'nullable|array',
            'cover_image' => 'nullable|image|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $product = app(\App\Services\Pharmacy\ProductService::class)->updateProduct($id, $request->all());
            return $this->success($product, 'محصول با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ✅ حذف محصول
     */
    public function destroy($id)
    {
        try {
            app(\App\Services\Pharmacy\ProductService::class)->deleteProduct($id);
            return $this->success(null, 'محصول با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ✅ تغییر وضعیت محصول
     */
    public function toggleStatus($id)
    {
        try {
            $product = app(\App\Services\Pharmacy\ProductService::class)->toggleStatus($id);
            return $this->success($product, 'وضعیت محصول با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ✅ افزایش موجودی
     */
    public function increaseStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $product = Product::findOrFail($id);
            $product->increaseStock($request->quantity);
            return $this->success($product->fresh(), 'موجودی با موفقیت افزایش یافت');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ✅ کاهش موجودی
     */
    public function decreaseStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $product = Product::findOrFail($id);
            $result = $product->decreaseStock($request->quantity);

            if (!$result) {
                return $this->error('موجودی کافی نیست', 400);
            }

            return $this->success($product->fresh(), 'موجودی با موفقیت کاهش یافت');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ✅ جستجوی محصولات
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $query = Product::active()->search($request->q);

        if ($request->has('pharmacy_id') && $request->pharmacy_id) {
            $query->where('pharmacy_id', $request->pharmacy_id);
        }

        $products = $query->limit($request->get('limit', 20))->get();

        return $this->success($products);
    }

    /**
     * ✅ دریافت محصولات یک داروخانه
     */
    public function getPharmacyProducts($pharmacyId, Request $request)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($pharmacyId);

            $query = Product::where('pharmacy_id', $pharmacyId)
                ->with(['brand', 'categories']);

            if ($request->has('search')) {
                $query->search($request->search);
            }

            if ($request->has('in_stock')) {
                if ($request->in_stock) {
                    $query->where('stock', '>', 0);
                } else {
                    $query->where('stock', '<=', 0);
                }
            }

            $products = $query->orderBy('name')
                ->paginate($request->get('per_page', 20));

            return $this->success([
                'pharmacy' => $pharmacy,
                'products' => $products,
            ]);
        } catch (\Exception $e) {
            return $this->error('داروخانه یافت نشد', 404);
        }
    }

    // ============================================
    // ✅ مدیریت برندها (در همین کنترلر)
    // ============================================

    /**
     * لیست برندها
     */
    public function brands(Request $request)
    {
        $query = Brand::where('tenant_id', session('tenant_id', 1));

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $brands = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return $this->success($brands);
    }

    /**
     * ایجاد برند
     */
    public function storeBrand(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:brands,name',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $brand = app(\App\Services\Pharmacy\ProductService::class)->createBrand($request->all());
            return $this->success($brand, 'برند با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * بروزرسانی برند
     */
    public function updateBrand(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100|unique:brands,name,' . $id,
            'website' => 'nullable|url',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $brand = app(\App\Services\Pharmacy\ProductService::class)->updateBrand($id, $request->all());
            return $this->success($brand, 'برند با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف برند
     */
    public function destroyBrand($id)
    {
        try {
            app(\App\Services\Pharmacy\ProductService::class)->deleteBrand($id);
            return $this->success(null, 'برند با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // ✅ مدیریت برچسب‌ها
    // ============================================

    public function tags(Request $request)
    {
        $query = ProductTag::where('tenant_id', session('tenant_id', 1));

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $tags = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return $this->success($tags);
    }

    public function storeTag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:product_tags,name',
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $tag = app(\App\Services\Pharmacy\ProductService::class)->createTag($request->all());
            return $this->success($tag, 'برچسب با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateTag(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50|unique:product_tags,name,' . $id,
            'color' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $tag = app(\App\Services\Pharmacy\ProductService::class)->updateTag($id, $request->all());
            return $this->success($tag, 'برچسب با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroyTag($id)
    {
        try {
            app(\App\Services\Pharmacy\ProductService::class)->deleteTag($id);
            return $this->success(null, 'برچسب با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // ✅ مدیریت ویژگی‌ها
    // ============================================

    public function attributes(Request $request)
    {
        $query = ProductAttribute::where('tenant_id', session('tenant_id', 1));

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('is_filterable')) {
            $query->where('is_filterable', filter_var($request->is_filterable, FILTER_VALIDATE_BOOLEAN));
        }

        $attributes = $query->orderBy('order')->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return $this->success($attributes);
    }

    public function storeAttribute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:product_attributes,name',
            'type' => 'required|in:text,select,number,color',
            'options' => 'nullable|array',
            'is_filterable' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $attribute = app(\App\Services\Pharmacy\ProductService::class)->createAttribute($request->all());
            return $this->success($attribute, 'ویژگی با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateAttribute(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100|unique:product_attributes,name,' . $id,
            'type' => 'sometimes|in:text,select,number,color',
            'options' => 'nullable|array',
            'is_filterable' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $attribute = app(\App\Services\Pharmacy\ProductService::class)->updateAttribute($id, $request->all());
            return $this->success($attribute, 'ویژگی با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroyAttribute($id)
    {
        try {
            app(\App\Services\Pharmacy\ProductService::class)->deleteAttribute($id);
            return $this->success(null, 'ویژگی با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // ✅ مدیریت نظرات
    // ============================================

    public function reviews(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);

            $query = $product->reviews()->with(['user', 'replies.user']);

            if ($request->has('is_approved')) {
                $query->where('is_approved', filter_var($request->is_approved, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('rating')) {
                $query->where('rating', $request->rating);
            }

            $reviews = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->success($reviews);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function approveReview($id)
    {
        try {
            $review = app(\App\Services\Pharmacy\ProductService::class)->approveReview($id);
            return $this->success($review, 'نظر با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroyReview($id)
    {
        try {
            app(\App\Services\Pharmacy\ProductService::class)->deleteReview($id);
            return $this->success(null, 'نظر با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function replyToReview(Request $request, $reviewId)
    {
        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $reply = app(\App\Services\Pharmacy\ProductService::class)->addReplyToReview(
                $reviewId,
                auth()->id(),
                $request->reply
            );
            return $this->success($reply, 'پاسخ با موفقیت ثبت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
