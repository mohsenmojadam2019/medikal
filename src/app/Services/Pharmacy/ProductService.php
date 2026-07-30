<?php

// app/Services/Pharmacy/ProductService.php

namespace App\Services\Pharmacy;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\ProductReview;
use App\Models\ProductTag;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ReviewReply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductService
{
    protected int $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id', 1);
    }

    // ============================================
    // ✅ مدیریت محصولات
    // ============================================

    /**
     * لیست محصولات با فیلتر
     */
    public function getProducts(array $filters = [], int $perPage = 15)
    {
        $query = Product::where('tenant_id', $this->tenantId)
            ->with(['brand', 'categories', 'tags', 'pharmacy']);

        // فیلترها
        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['category_id'])) {
            $query->inCategory($filters['category_id']);
        }

        if (isset($filters['pharmacy_id'])) {
            $query->byPharmacy($filters['pharmacy_id']);
        }

        if (isset($filters['brand_id'])) {
            $query->byBrand($filters['brand_id']);
        }

        if (isset($filters['requires_prescription'])) {
            $query->where('requires_prescription', filter_var($filters['requires_prescription'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['on_sale'])) {
            $query->onSale();
        }

        if (isset($filters['in_stock'])) {
            $query->inStock();
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        // مرتب‌سازی
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $allowedSorts = ['name', 'price', 'created_at', 'avg_rating', 'stock'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * دریافت یک محصول
     */
    public function getProduct($id): Product
    {
        return Product::where('tenant_id', $this->tenantId)
            ->with([
                'brand',
                'categories',
                'tags',
                'pharmacy',
                'attributeValues.attribute',
                'reviews' => function ($query) {
                    $query->approved()->with(['user', 'replies.user']);
                },
            ])
            ->findOrFail($id);
    }

    /**
     * دریافت محصول بر اساس اسلاگ
     */
    public function getProductBySlug($slug): Product
    {
        return Product::where('tenant_id', $this->tenantId)
            ->where('slug', $slug)
            ->with([
                'brand',
                'categories',
                'tags',
                'pharmacy',
                'attributeValues.attribute',
                'reviews' => function ($query) {
                    $query->approved()->with(['user', 'replies.user']);
                },
            ])
            ->firstOrFail();
    }


    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // ✅ اول کد را تولید کن (قبل از ایجاد محصول)
            $code = $data['code'] ?? $this->generateProductCode();

            // ایجاد محصول
            $product = Product::create([
                'tenant_id' => $this->tenantId,
                'pharmacy_id' => $data['pharmacy_id'] ?? session('pharmacy_id', 1),
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Product::generateUniqueSlug($data['name']),
                'generic_name' => $data['generic_name'] ?? null,
                'code' => $code, // ✅ از متغیر استفاده کن
                'category' => $data['category'] ?? null,
                'form' => $data['form'] ?? null,
                'strength' => $data['strength'] ?? null,
                'manufacturer' => $data['manufacturer'] ?? null,
                'country_of_origin' => $data['country_of_origin'] ?? null,
                'license_from' => $data['license_from'] ?? null,
                'product_form' => $data['product_form'] ?? null,
                'volume' => $data['volume'] ?? null,
                'container_type' => $data['container_type'] ?? null,
                'container_material' => $data['container_material'] ?? null,
                'weight' => $data['weight'] ?? null,
                'dimensions' => $data['dimensions'] ?? null,
                'price' => $data['price'] ?? 0,
                'discount_percent' => $data['discount_percent'] ?? 0,
                'has_discount' => $data['has_discount'] ?? false,
                'stock' => $data['stock'] ?? 0,
                'requires_prescription' => $data['requires_prescription'] ?? true,
                'is_active' => $data['is_active'] ?? true,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? null,
                'product_features' => $data['product_features'] ?? null,
                'usage_instructions' => $data['usage_instructions'] ?? null,
                'tags' => $data['tags'] ?? null,
                'allow_reviews' => $data['allow_reviews'] ?? true,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // اگر تخفیف فعال بود، قیمت تخفیف‌خورده را محاسبه کن
            if ($product->has_discount && $product->discount_percent > 0) {
                $product->discounted_price = $product->price - ($product->price * $product->discount_percent / 100);
                $product->save();
            }

            // اتصال به دسته‌بندی‌ها
            if (isset($data['category_ids']) && is_array($data['category_ids'])) {
                $syncData = [];
                foreach ($data['category_ids'] as $categoryId) {
                    $syncData[$categoryId] = ['is_primary' => false];
                }
                if (isset($data['primary_category_id'])) {
                    $syncData[$data['primary_category_id']]['is_primary'] = true;
                }
                $product->categories()->sync($syncData);
            }

            // اتصال به برچسب‌ها
            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                $product->tags()->sync($data['tag_ids']);
            }

            // ذخیره ویژگی‌ها
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                foreach ($data['attributes'] as $attributeId => $value) {
                    ProductAttributeValue::create([
                        'attribute_id' => $attributeId,
                        'product_id' => $product->id,
                        'value' => $value,
                    ]);
                }
            }

            // آپلود تصاویر
            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $image) {
                    $product->addMedia($image)->toMediaCollection('product_images');
                }
            }

            if (isset($data['cover_image'])) {
                $product->addMedia($data['cover_image'])->toMediaCollection('product_cover');
            }

            Log::info('✅ محصول ایجاد شد', [
                'product_id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'tenant_id' => $this->tenantId,
            ]);

            return $product;
        });
    }

    /**
     * ✅ تولید کد منحصر‌به‌فرد برای محصول
     */
    private function generateProductCode(): string
    {
        $prefix = 'PRD';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $code = "{$prefix}-{$year}{$month}-{$random}";

        while (Product::where('code', $code)->exists()) {
            $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $code = "{$prefix}-{$year}{$month}-{$random}";
        }

        return $code;
    }
    /**
     * به‌روزرسانی محصول
     */
    public function updateProduct($id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->getProduct($id);

            $product->update([
                'pharmacy_id' => $data['pharmacy_id'] ?? $product->pharmacy_id,
                'brand_id' => $data['brand_id'] ?? $product->brand_id,
                'name' => $data['name'] ?? $product->name,
                'slug' => $data['slug'] ?? $product->slug,
                'generic_name' => $data['generic_name'] ?? $product->generic_name,
                'code' => $data['code'] ?? $product->code,
                'category' => $data['category'] ?? $product->category,
                'form' => $data['form'] ?? $product->form,
                'strength' => $data['strength'] ?? $product->strength,
                'manufacturer' => $data['manufacturer'] ?? $product->manufacturer,
                'country_of_origin' => $data['country_of_origin'] ?? $product->country_of_origin,
                'license_from' => $data['license_from'] ?? $product->license_from,
                'product_form' => $data['product_form'] ?? $product->product_form,
                'volume' => $data['volume'] ?? $product->volume,
                'container_type' => $data['container_type'] ?? $product->container_type,
                'container_material' => $data['container_material'] ?? $product->container_material,
                'weight' => $data['weight'] ?? $product->weight,
                'dimensions' => $data['dimensions'] ?? $product->dimensions,
                'price' => $data['price'] ?? $product->price,
                'discount_percent' => $data['discount_percent'] ?? $product->discount_percent,
                'has_discount' => $data['has_discount'] ?? $product->has_discount,
                'stock' => $data['stock'] ?? $product->stock,
                'requires_prescription' => $data['requires_prescription'] ?? $product->requires_prescription,
                'is_active' => $data['is_active'] ?? $product->is_active,
                'short_description' => $data['short_description'] ?? $product->short_description,
                'description' => $data['description'] ?? $product->description,
                'product_features' => $data['product_features'] ?? $product->product_features,
                'usage_instructions' => $data['usage_instructions'] ?? $product->usage_instructions,
                'tags' => $data['tags'] ?? $product->tags,
                'allow_reviews' => $data['allow_reviews'] ?? $product->allow_reviews,
                'metadata' => $data['metadata'] ?? $product->metadata,
            ]);

            // محاسبه مجدد قیمت تخفیف‌خورده
            if ($product->has_discount && $product->discount_percent > 0) {
                $product->discounted_price = $product->price - ($product->price * $product->discount_percent / 100);
                $product->save();
            }

            // به‌روزرسانی دسته‌بندی‌ها
            if (isset($data['category_ids']) && is_array($data['category_ids'])) {
                $syncData = [];
                foreach ($data['category_ids'] as $categoryId) {
                    $syncData[$categoryId] = ['is_primary' => false];
                }
                if (isset($data['primary_category_id'])) {
                    $syncData[$data['primary_category_id']]['is_primary'] = true;
                }
                $product->categories()->sync($syncData);
            }

            // به‌روزرسانی برچسب‌ها
            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                $product->tags()->sync($data['tag_ids']);
            }

            // به‌روزرسانی ویژگی‌ها
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                foreach ($data['attributes'] as $attributeId => $value) {
                    ProductAttributeValue::updateOrCreate(
                        [
                            'attribute_id' => $attributeId,
                            'product_id' => $product->id,
                        ],
                        ['value' => $value]
                    );
                }
            }

            // آپلود تصاویر جدید
            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $image) {
                    $product->addMedia($image)->toMediaCollection('product_images');
                }
            }

            if (isset($data['cover_image'])) {
                $product->clearMediaCollection('product_cover');
                $product->addMedia($data['cover_image'])->toMediaCollection('product_cover');
            }

            Log::info('✅ محصول به‌روزرسانی شد', [
                'product_id' => $product->id,
                'name' => $product->name,
            ]);

            return $product->fresh();
        });
    }

    /**
     * حذف محصول
     */
    public function deleteProduct($id): bool
    {
        $product = $this->getProduct($id);
        $product->delete();

        Log::info('🗑️ محصول حذف شد', [
            'product_id' => $product->id,
            'name' => $product->name,
        ]);

        return true;
    }

    /**
     * تغییر وضعیت محصول
     */
    public function toggleStatus($id): Product
    {
        $product = $this->getProduct($id);
        $product->update(['is_active' => !$product->is_active]);

        return $product->fresh();
    }

    // ============================================
    // ✅ مدیریت برندها
    // ============================================

    public function getBrands(array $filters = [], int $perPage = 15)
    {
        $query = Brand::where('tenant_id', $this->tenantId);

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getAllBrands()
    {
        return Brand::where('tenant_id', $this->tenantId)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function createBrand(array $data): Brand
    {
        return DB::transaction(function () use ($data) {
            $brand = Brand::create([
                'tenant_id' => $this->tenantId,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (isset($data['logo'])) {
                $brand->addMedia($data['logo'])->toMediaCollection('brand_logo');
            }

            return $brand;
        });
    }

    public function updateBrand($id, array $data): Brand
    {
        $brand = Brand::findOrFail($id);
        $brand->update($data);

        if (isset($data['logo'])) {
            $brand->clearMediaCollection('brand_logo');
            $brand->addMedia($data['logo'])->toMediaCollection('brand_logo');
        }

        return $brand->fresh();
    }

    public function deleteBrand($id): bool
    {
        $brand = Brand::findOrFail($id);
        $brand->delete();
        return true;
    }

    // ============================================
    // ✅ مدیریت برچسب‌ها
    // ============================================

    public function getTags(array $filters = [], int $perPage = 15)
    {
        $query = ProductTag::where('tenant_id', $this->tenantId);

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getAllTags()
    {
        return ProductTag::where('tenant_id', $this->tenantId)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function createTag(array $data): ProductTag
    {
        return ProductTag::create([
            'tenant_id' => $this->tenantId,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'color' => $data['color'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateTag($id, array $data): ProductTag
    {
        $tag = ProductTag::findOrFail($id);
        $tag->update($data);
        return $tag->fresh();
    }

    public function deleteTag($id): bool
    {
        $tag = ProductTag::findOrFail($id);
        $tag->delete();
        return true;
    }

    // ============================================
    // ✅ مدیریت ویژگی‌ها
    // ============================================

    public function getAttributes(array $filters = [], int $perPage = 15)
    {
        $query = ProductAttribute::where('tenant_id', $this->tenantId);

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['is_filterable'])) {
            $query->where('is_filterable', filter_var($filters['is_filterable'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('order')->orderBy('name')->paginate($perPage);
    }

    public function getAllAttributes()
    {
        return ProductAttribute::where('tenant_id', $this->tenantId)
            ->active()
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }

    public function createAttribute(array $data): ProductAttribute
    {
        return ProductAttribute::create([
            'tenant_id' => $this->tenantId,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'type' => $data['type'] ?? 'text',
            'options' => $data['options'] ?? null,
            'is_filterable' => $data['is_filterable'] ?? true,
            'order' => $data['order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateAttribute($id, array $data): ProductAttribute
    {
        $attribute = ProductAttribute::findOrFail($id);
        $attribute->update($data);
        return $attribute->fresh();
    }

    public function deleteAttribute($id): bool
    {
        $attribute = ProductAttribute::findOrFail($id);
        $attribute->delete();
        return true;
    }

    // ============================================
    // ✅ مدیریت نظرات
    // ============================================

    public function getProductReviews($productId, array $filters = [], int $perPage = 15)
    {
        $query = ProductReview::where('product_id', $productId)
            ->where('tenant_id', $this->tenantId)
            ->with(['user', 'replies.user']);

        if (isset($filters['is_approved'])) {
            $query->where('is_approved', filter_var($filters['is_approved'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    public function createReview(array $data): ProductReview
    {
        return DB::transaction(function () use ($data) {
            $review = ProductReview::create([
                'tenant_id' => $this->tenantId,
                'product_id' => $data['product_id'],
                'user_id' => $data['user_id'],
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'pros' => $data['pros'] ?? null,
                'cons' => $data['cons'] ?? null,
                'is_approved' => $data['is_approved'] ?? false,
                'is_purchased' => $data['is_purchased'] ?? false,
            ]);

            if ($review->is_approved) {
                $review->product->recalculateRating();
            }

            return $review;
        });
    }

    public function approveReview($id): ProductReview
    {
        $review = ProductReview::findOrFail($id);
        $review->approve();
        return $review;
    }

    public function deleteReview($id): bool
    {
        $review = ProductReview::findOrFail($id);
        $review->delete();
        return true;
    }

    public function addReplyToReview($reviewId, $userId, $reply): ReviewReply
    {
        $review = ProductReview::findOrFail($reviewId);
        return $review->addReply($userId, $reply);
    }

    public function deleteReply($id): bool
    {
        $reply = ReviewReply::findOrFail($id);
        $reply->delete();
        return true;
    }
}
