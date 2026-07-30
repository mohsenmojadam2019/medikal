<?php
// app/Models/ReviewReply.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewReply extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'review_id',
        'user_id',
        'reply',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    // ============================================
    // ✅ Relationships
    // ============================================

    /**
     * نظر مربوطه
     */
    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'review_id');
    }

    /**
     * کاربر پاسخ‌دهنده (ادمین یا مدیر)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // ✅ Accessors
    // ============================================

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

    // ============================================
    // ✅ Scopes
    // ============================================

    /**
     * پاسخ‌های تایید شده
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * پاسخ‌های تایید نشده
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    // ============================================
    // ✅ Methods
    // ============================================

    /**
     * تایید پاسخ
     */
    public function approve(): void
    {
        $this->update(['is_approved' => true]);
    }

    /**
     * عدم تایید پاسخ
     */
    public function reject(): void
    {
        $this->update(['is_approved' => false]);
    }

    /**
     * بررسی اینکه آیا پاسخ تایید شده است
     */
    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    // ============================================
    // ✅ Boot
    // ============================================

    protected static function booted()
    {
        static::creating(function ($reply) {
            // به صورت پیش‌فرض پاسخ‌ها تایید شده هستند (برای ادمین)
            if (is_null($reply->is_approved)) {
                $reply->is_approved = true;
            }
        });

        static::created(function ($reply) {
            // بعد از ایجاد پاسخ، تعداد نظرات را آپدیت کن (اختیاری)
            // یا ارسال نوتیفیکیشن به کاربر
        });
    }
}
