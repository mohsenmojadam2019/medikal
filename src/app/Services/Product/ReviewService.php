<?php
// app/Services/Product/ReviewService.php

namespace App\Services\Product;

use App\Models\ProductReview;
use App\Models\ReviewReply;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewService
{
    protected int $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id', 1);
    }

    /**
     * ایجاد نظر جدید
     */
    public function createReview(int $productId, int $userId, array $data): ProductReview
    {
        return DB::transaction(function () use ($productId, $userId, $data) {
            $review = ProductReview::create([
                'tenant_id' => $this->tenantId,
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
                'pros' => $data['pros'] ?? null,
                'cons' => $data['cons'] ?? null,
                'is_approved' => $data['is_approved'] ?? false,
                'is_purchased' => $data['is_purchased'] ?? false,
            ]);

            // اگر نظر تایید شده باشد، امتیاز محصول را بروزرسانی کن
            if ($review->is_approved) {
                $review->product->recalculateRating();
            }

            Log::info('✅ نظر جدید ثبت شد', [
                'review_id' => $review->id,
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $data['rating'],
            ]);

            return $review;
        });
    }

    /**
     * تایید نظر
     */
    public function approveReview(int $reviewId): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->approve();

        Log::info('✅ نظر تایید شد', [
            'review_id' => $reviewId,
            'product_id' => $review->product_id,
        ]);

        return $review;
    }

    /**
     * افزودن پاسخ به نظر
     */
    public function addReply(int $reviewId, int $userId, string $reply): ReviewReply
    {
        $review = ProductReview::findOrFail($reviewId);

        $replyModel = $review->addReply($userId, $reply);

        Log::info('✅ پاسخ به نظر ثبت شد', [
            'review_id' => $reviewId,
            'reply_id' => $replyModel->id,
            'user_id' => $userId,
        ]);

        return $replyModel;
    }

    /**
     * دریافت نظرات یک محصول
     */
    public function getProductReviews(int $productId, array $filters = [], int $perPage = 15)
    {
        $query = ProductReview::where('product_id', $productId)
            ->where('is_approved', true)
            ->with(['user', 'replies.user']);

        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (isset($filters['has_comment'])) {
            $query->whereNotNull('comment');
        }

        if (isset($filters['has_images'])) {
            $query->whereHas('media');
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * دریافت آمار نظرات یک محصول
     */
    public function getReviewStats(int $productId): array
    {
        $product = Product::findOrFail($productId);

        $ratings = ProductReview::where('product_id', $productId)
            ->where('is_approved', true)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // تکمیل رتبه‌های缺失
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($ratings[$i])) {
                $ratings[$i] = 0;
            }
        }
        ksort($ratings);

        return [
            'average' => $product->avg_rating,
            'total' => $product->review_count,
            'ratings' => $ratings,
            'rating_percentages' => $this->calculatePercentages($ratings),
        ];
    }

    /**
     * محاسبه درصد هر رتبه
     */
    private function calculatePercentages(array $ratings): array
    {
        $total = array_sum($ratings);
        if ($total == 0) {
            return array_fill_keys(array_keys($ratings), 0);
        }

        $percentages = [];
        foreach ($ratings as $rating => $count) {
            $percentages[$rating] = round(($count / $total) * 100, 1);
        }

        return $percentages;
    }

    /**
     * حذف نظر
     */
    public function deleteReview(int $reviewId): bool
    {
        $review = ProductReview::findOrFail($reviewId);
        $productId = $review->product_id;

        $review->delete();

        // بروزرسانی امتیاز محصول
        $product = Product::find($productId);
        if ($product) {
            $product->recalculateRating();
        }

        Log::info('🗑️ نظر حذف شد', [
            'review_id' => $reviewId,
            'product_id' => $productId,
        ]);

        return true;
    }
}
