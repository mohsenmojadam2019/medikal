<?php

// app/Http/Controllers/Api/BrandController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ApiResponse;

    /**
     * لیست برندها
     */
    public function index(Request $request)
    {
        $query = Brand::where('is_active', true);

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('has_products')) {
            $query->has('products');
        }

        $brands = $query->withCount('products')
            ->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return $this->success($brands);
    }

    /**
     * نمایش یک برند
     */
    public function show($slug)
    {
        try {
            $brand = Brand::where('slug', $slug)
                ->where('is_active', true)
                ->withCount('products')
                ->firstOrFail();

            return $this->success($brand);
        } catch (\Exception $e) {
            return $this->error('برند یافت نشد', 404);
        }
    }

    /**
     * دریافت محصولات یک برند
     */
    public function products(Request $request, $slug)
    {
        try {
            $brand = Brand::where('slug', $slug)
                ->where('is_active', true)
                ->firstOrFail();

            $query = Product::where('brand_id', $brand->id)
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->with(['categories', 'tags']);

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

            // فرمت کردن محصولات
            $products->getCollection()->transform(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->display_price,
                    'stock' => $product->stock,
                    'is_in_stock' => $product->is_in_stock,
                    'image' => $product->image_url,
                    'thumbnail' => $product->thumbnail_url,
                    'rating' => $product->avg_rating,
                    'categories' => $product->categories->pluck('name'),
                ];
            });

            return $this->success([
                'brand' => $brand,
                'products' => $products,
            ]);
        } catch (\Exception $e) {
            return $this->error('برند یافت نشد', 404);
        }
    }

    /**
     * برندهای محبوب (با بیشترین محصول)
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 10);

        $brands = Brand::where('is_active', true)
            ->withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('products_count', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($brands);
    }

    /**
     * برندهای برتر (با بیشترین امتیاز)
     */
    public function topRated(Request $request)
    {
        $limit = $request->get('limit', 10);

        $brands = Brand::where('is_active', true)
            ->with(['products' => function ($query) {
                $query->where('is_active', true)
                    ->where('avg_rating', '>=', 4);
            }])
            ->withCount(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get()
            ->filter(function ($brand) {
                return $brand->products_count > 0;
            })
            ->sortByDesc(function ($brand) {
                return $brand->products->avg('avg_rating');
            })
            ->take($limit)
            ->values();

        return $this->success($brands);
    }
}
