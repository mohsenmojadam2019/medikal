<?php
// app/Models/ProductReview.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductReview extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'user_id',
        'rating',
        'comment',
        'pros',
        'cons',
        'is_approved',
        'is_purchased',
    ];

    protected $casts = [
        'pros' => 'array',
        'cons' => 'array',
        'is_approved' => 'boolean',
        'is_purchased' => 'boolean',
        'rating' => 'integer',
    ];

    // ============================================
    // ✅ Media Library - تصاویر نظر
    // ============================================

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('review_images')
            ->registerMediaConversions(function ($media) {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150)
                    ->fit(\Spatie\Image\Enums\Fit::Crop, 150, 150);
            });
    }

    // ============================================
    // ✅ Relationships
    // ============================================

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ✅ پاسخ‌های این نظر
     */
    public function replies()
    {
        return $this->hasMany(ReviewReply::class)
            ->orderBy('created_at', 'asc');
    }

    /**
     * ✅ پاسخ‌های تایید شده این نظر
     */
    public function approvedReplies()
    {
        return $this->replies()->where('is_approved', true);
    }

    // ============================================
    // ✅ Accessors
    // ============================================

    public function getImagesAttribute(): array
    {
        return $this->getMedia('review_images')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'name' => $media->file_name,
            ];
        })->toArray();
    }

    /**
     * تاریخ انتشار به صورت فارسی
     */
    public function getPersianCreatedAtAttribute(): string
    {
        return verta($this->created_at)->format('Y/m/d H:i');
    }

    /**
     * تاریخ انتشار به صورت نسبی
     */
    public function getRelativeCreatedAtAttribute(): string
    {
        return verta($this->created_at)->relativeFormat();
    }

    /**
     * تعداد پاسخ‌های تایید شده
     */
    public function getRepliesCountAttribute(): int
    {
        return $this->approvedReplies()->count();
    }

    // ============================================
    // ✅ Scopes
    // ============================================

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePurchased($query)
    {
        return $query->where('is_purchased', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeHighRating($query, $min = 4)
    {
        return $query->where('rating', '>=', $min);
    }

    public function scopeLowRating($query, $max = 2)
    {
        return $query->where('rating', '<=', $max);
    }

    // ============================================
    // ✅ Methods
    // ============================================

    /**
     * تایید نظر
     */
    public function approve(): void
    {
        $this->update(['is_approved' => true]);

        // بروزرسانی امتیاز محصول
        $this->product->recalculateRating();
    }

    /**
     * عدم تایید نظر
     */
    public function reject(): void
    {
        $this->update(['is_approved' => false]);

        // بروزرسانی امتیاز محصول
        $this->product->recalculateRating();
    }

    /**
     * افزودن پاسخ به نظر
     */
    public function addReply(int $userId, string $reply): ReviewReply
    {
        return $this->replies()->create([
            'user_id' => $userId,
            'reply' => $reply,
            'is_approved' => true,
        ]);
    }

    /**
     * بررسی اینکه آیا نظر تایید شده است
     */
    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    /**
     * بررسی اینکه آیا کاربر خریدار واقعی است
     */
    public function isPurchased(): bool
    {
        return $this->is_purchased;
    }

    // ============================================
    // ✅ Boot
    // ============================================

    protected static function booted()
    {
        static::creating(function ($review) {
            if (empty($review->tenant_id)) {
                $review->tenant_id = session('tenant_id', 1);
            }
        });

        static::deleting(function ($review) {
            // حذف تصاویر نظر از مدیا لایبرری
            $review->clearMediaCollection('review_images');

            // حذف پاسخ‌های مرتبط
            $review->replies()->delete();
        });
    }
}
