<?php

// app/Http/Controllers/Api/ProductController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\ProductTag;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use ApiResponse;

    // ============================================
    // ✅ محصولات
    // ============================================

    /**
     * لیست محصولات با فیلتر و صفحه‌بندی
     */
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)
            ->where('stock', '>', 0)
            ->with(['brand', 'categories', 'tags', 'pharmacy.city', 'pharmacy.province']);

        // فیلتر بر اساس دسته‌بندی
        if ($request->has('category_id') && $request->category_id) {
            $query->inCategory($request->category_id);
        }

        // فیلتر بر اساس برند
        if ($request->has('brand_id') && $request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        // فیلتر بر اساس داروخانه
        if ($request->has('pharmacy_id') && $request->pharmacy_id) {
            $query->where('pharmacy_id', $request->pharmacy_id);
        }

        // فیلتر بر اساس نیاز به نسخه
        if ($request->has('requires_prescription')) {
            $query->where('requires_prescription', filter_var($request->requires_prescription, FILTER_VALIDATE_BOOLEAN));
        }

        // فیلتر بر اساس تخفیف
        if ($request->has('on_sale')) {
            $query->onSale();
        }

        // فیلتر بر اساس قیمت
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // فیلتر بر اساس ویژگی‌ها
        if ($request->has('attributes') && is_array($request->attributes)) {
            foreach ($request->attributes as $attributeId => $value) {
                $query->whereHas('attributeValues', function ($q) use ($attributeId, $value) {
                    $q->where('attribute_id', $attributeId)
                        ->where('value', $value);
                });
            }
        }

        // جستجو
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // مرتب‌سازی
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSorts = ['name', 'price', 'avg_rating', 'created_at', 'stock'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate($request->get('per_page', 20));

        // اضافه کردن اطلاعات قیمت و تصاویر
        $products->getCollection()->transform(function ($product) {
            return $this->formatProduct($product);
        });

        return $this->success($products);
    }

    /**
     * نمایش یک محصول با جزئیات کامل
     */
    public function show($slug)
    {
        try {
            $product = Product::where('slug', $slug)
                ->where('is_active', true)
                ->with([
                    'brand',
                    'categories',
                    'tags',
                    'pharmacy',
                    'attributeValues.attribute',
                    'reviews' => function ($query) {
                        $query->approved()->with(['user', 'replies.user']);
                    },
                    'reviews.user',
                    'reviews.replies',
                ])
                ->firstOrFail();

            // محصولات مشابه
            $similarProducts = $this->getSimilarProducts($product);

            $data = [
                'product' => $this->formatProduct($product, true),
                'similar_products' => $similarProducts,
                'attributes' => $product->attributes_grouped ?? [],
                'gallery' => $product->gallery_images ?? [],
                'reviews' => [
                    'average' => $product->avg_rating ?? 0,
                    'count' => $product->review_count ?? 0,
                    'list' => $product->reviews ? $product->reviews->map(function ($review) {
                        return [
                            'id' => $review->id,
                            'user_name' => $review->user->name ?? 'کاربر',
                            'rating' => $review->rating,
                            'comment' => $review->comment,
                            'pros' => $review->pros,
                            'cons' => $review->cons,
                            'images' => $review->images ?? [],
                            'is_purchased' => $review->is_purchased,
                            'created_at' => $review->created_at ? $review->created_at->diffForHumans() : null,
                            'replies' => $review->replies ? $review->replies->map(function ($reply) {
                                return [
                                    'user_name' => $reply->user->name ?? 'ادمین',
                                    'reply' => $reply->reply,
                                    'created_at' => $reply->created_at ? $reply->created_at->diffForHumans() : null,
                                ];
                            }) : [],
                        ];
                    }) : [],
                ],
            ];

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error('محصول یافت نشد: ' . $e->getMessage(), 404);
        }
    }

    /**
     * دریافت محصولات یک دسته‌بندی
     */
    public function categoryProducts(Request $request, $slug)
    {
        try {
            $category = ProductCategory::where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();

            // دریافت تمام زیردسته‌ها
            $categoryIds = $category->getAllChildrenIds();
            $categoryIds[] = $category->id;

            $query = Product::where('is_active', true)
                ->where('stock', '>', 0)
                ->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                })
                ->with(['brand', 'tags', 'pharmacy.city', 'pharmacy.province']);

            // فیلترها
            if ($request->has('brand_id')) {
                $query->where('brand_id', $request->brand_id);
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->has('on_sale')) {
                $query->onSale();
            }

            // مرتب‌سازی
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $allowedSorts = ['name', 'price', 'avg_rating', 'created_at'];

            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $products = $query->paginate($request->get('per_page', 20));

            $products->getCollection()->transform(function ($product) {
                return $this->formatProduct($product);
            });

            return $this->success([
                'category' => $category,
                'breadcrumb' => $this->getBreadcrumb($category),
                'products' => $products,
            ]);
        } catch (\Exception $e) {
            return $this->error('دسته‌بندی یافت نشد', 404);
        }
    }

    // ============================================
    // ✅ برندها (عمومی)
    // ============================================

    /**
     * لیست برندها
     */
    public function brands(Request $request)
    {
        $query = Brand::where('is_active', true);

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        $brands = $query->orderBy('name')->get();

        return $this->success($brands);
    }

    /**
     * نمایش یک برند با محصولاتش
     */
    public function brandProducts(Request $request, $slug)
    {
        try {
            $brand = Brand::where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();

            $query = Product::where('brand_id', $brand->id)
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->with(['categories', 'tags', 'pharmacy.city', 'pharmacy.province']);

            // فیلترها
            if ($request->has('category_id')) {
                $query->inCategory($request->category_id);
            }

            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            $products = $query->paginate($request->get('per_page', 20));

            $products->getCollection()->transform(function ($product) {
                return $this->formatProduct($product);
            });

            return $this->success([
                'brand' => $brand,
                'products' => $products,
            ]);
        } catch (\Exception $e) {
            return $this->error('برند یافت نشد', 404);
        }
    }

    // ============================================
    // ✅ ویژگی‌ها و فیلترها (عمومی)
    // ============================================

    /**
     * دریافت ویژگی‌های قابل فیلتر
     */
    public function filterAttributes(Request $request)
    {
        $attributes = \App\Models\ProductAttribute::where('is_active', true)
            ->where('is_filterable', true)
            ->with(['values' => function ($query) {
                $query->select('attribute_id', 'value')->distinct();
            }])
            ->orderBy('order')
            ->get();

        // فیلتر بر اساس دسته‌بندی (اختیاری)
        if ($request->has('category_id') && $request->category_id) {
            $category = ProductCategory::find($request->category_id);
            if ($category) {
                $categoryIds = $category->getAllChildrenIds();
                $categoryIds[] = $category->id;

                $productIds = Product::whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                })->pluck('id');

                $attributes->each(function ($attribute) use ($productIds) {
                    $attribute->values = $attribute->values->filter(function ($value) use ($productIds) {
                        return $productIds->contains($value->product_id);
                    })->values();
                });
            }
        }

        return $this->success($attributes);
    }

    /**
     * دریافت برچسب‌ها (عمومی)
     */
    public function tags()
    {
        $tags = ProductTag::where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->success($tags);
    }

    // ============================================
    // ✅ نظرات (عمومی)
    // ============================================

    /**
     * ثبت نظر برای محصول
     */
    public function storeReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'pros' => 'nullable|array',
            'cons' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $user = auth()->user();
            if (!$user) {
                return $this->error('برای ثبت نظر باید وارد شوید', 401);
            }

            // بررسی اینکه کاربر قبلاً نظر نداده
            $existing = \App\Models\ProductReview::where('product_id', $request->product_id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                return $this->error('شما قبلاً برای این محصول نظر داده‌اید', 400);
            }

            $review = \App\Models\ProductReview::create([
                'tenant_id' => session('tenant_id', 1),
                'product_id' => $request->product_id,
                'user_id' => $user->id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'pros' => $request->pros,
                'cons' => $request->cons,
                'is_approved' => false,
                'is_purchased' => $this->checkIfUserPurchased($user->id, $request->product_id),
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $review->addMedia($image)->toMediaCollection('review_images');
                }
            }

            return $this->success($review, 'نظر شما با موفقیت ثبت شد و در انتظار تایید است', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت نظرات یک محصول
     */
    public function getReviews(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);

            $query = $product->reviews()
                ->where('is_approved', true)
                ->with(['user', 'replies.user']);

            if ($request->has('rating')) {
                $query->where('rating', $request->rating);
            }

            $reviews = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            $stats = [
                'average' => $product->avg_rating ?? 0,
                'total' => $product->review_count ?? 0,
                'ratings' => $product->reviews()
                    ->where('is_approved', true)
                    ->selectRaw('rating, COUNT(*) as count')
                    ->groupBy('rating')
                    ->pluck('count', 'rating')
                    ->toArray(),
            ];

            return $this->success([
                'reviews' => $reviews,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return $this->error('محصول یافت نشد', 404);
        }
    }

    // ============================================
    // ✅ متدهای کمکی
    // ============================================

    /**
     * فرمت کردن محصول برای خروجی
     */
    private function formatProduct($product, $detailed = false)
    {
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'generic_name' => $product->generic_name,
            'code' => $product->code,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
                'logo' => $product->brand->logo_url ?? null,
            ] : null,
            'categories' => $product->categories ? $product->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            }) : [],
            'tags' => $product->tags ? $product->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ];
            }) : [],
            'pharmacy' => $product->pharmacy ? [
                'id' => $product->pharmacy->id, 'name' => $product->pharmacy->name,
                'address' => $product->pharmacy->address, 'city' => $product->pharmacy->city?->name,
                'province' => $product->pharmacy->province?->name,
                'district' => $product->pharmacy->metadata['district'] ?? null,
            ] : null,
            'price' => $product->display_price,
            'stock' => $product->stock,
            'stock_status' => $product->stock_status,
            'is_in_stock' => $product->is_in_stock,
            'requires_prescription' => $product->requires_prescription,
            'image' => $product->image_url ?? null,
            'thumbnail' => $product->thumbnail_url ?? null,
            'rating' => [
                'average' => $product->avg_rating ?? 0,
                'count' => $product->review_count ?? 0,
            ],
        ];

        if ($detailed) {
            $data = array_merge($data, [
                'form' => $product->form,
                'strength' => $product->strength,
                'manufacturer' => $product->manufacturer,
                'country_of_origin' => $product->country_of_origin,
                'license_from' => $product->license_from,
                'product_form' => $product->product_form,
                'volume' => $product->volume,
                'container_type' => $product->container_type,
                'container_material' => $product->container_material,
                'weight' => $product->weight,
                'dimensions' => $product->dimensions,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'product_features' => $product->product_features,
                'usage_instructions' => $product->usage_instructions,
                'pharmacy' => $product->pharmacy ? [
                    'id' => $product->pharmacy->id,
                    'name' => $product->pharmacy->name,
                    'address' => $product->pharmacy->address,
                    'phone' => $product->pharmacy->phone,
                ] : null,
            ]);
        }

        return $data;
    }

    /**
     * دریافت محصولات مشابه
     */
    private function getSimilarProducts($product, $limit = 10)
    {
        $tagIds = $product->tags->pluck('id')->all();
        $categoryIds = $product->categories->pluck('id')->all();
        return Product::where('is_active', true)->where('stock', '>', 0)->whereKeyNot($product->id)
            ->where(function ($query) use ($tagIds, $categoryIds) {
                if ($tagIds) $query->whereHas('tags', fn ($q) => $q->whereIn('product_tags.id', $tagIds));
                if ($categoryIds) $query->orWhereHas('categories', fn ($q) => $q->whereIn('product_categories.id', $categoryIds));
            })->with(['brand','categories','tags','pharmacy.city','pharmacy.province'])->limit($limit)->get()
            ->sortByDesc(fn ($item) => $item->tags->pluck('id')->intersect($tagIds)->count())->values()
            ->map(fn ($item) => ['id'=>$item->id,'name'=>$item->name,'slug'=>$item->slug,'brand'=>$item->brand?->name,
                'price'=>$item->display_price,'image'=>$item->image_url ?? null,'rating'=>$item->avg_rating ?? 0,
                'matched_tags'=>$item->tags->whereIn('id',$tagIds)->pluck('name')->values(),
                'pharmacy'=>['name'=>$item->pharmacy?->name,'city'=>$item->pharmacy?->city?->name,'province'=>$item->pharmacy?->province?->name,'district'=>$item->pharmacy?->metadata['district'] ?? null]]);
    }

    /**
     * دریافت مسیر دسته‌بندی (Breadcrumb)
     */
    private function getBreadcrumb($category)
    {
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

        return $breadcrumb;
    }

    /**
     * بررسی اینکه کاربر محصول را خریده است
     */
    private function checkIfUserPurchased($userId, $productId)
    {
        return false;
    }
}
