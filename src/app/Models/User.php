<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',              // nullable - برای کاربران عادی
        'email',             // nullable - برای ادمین‌ها
        'phone',             // nullable - برای کاربران عادی
        'password',          // nullable - برای ادمین‌ها
        'phone_verified_at', // زمان تایید موبایل
        'email_verified_at', // زمان تایید ایمیل
        'is_active',         // فعال/غیرفعال
        'remember_token',
        'last_login_at',     // آخرین ورود
        'last_login_ip',     // آی‌پی آخرین ورود
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * ثبت‌نام با شماره موبایل (بدون نام)
     */
    public static function registerWithPhone(string $phone): self
    {
        return self::create([
            'phone' => $phone,
            'is_active' => true,
        ]);
    }

    /**
     * ثبت‌نام با ایمیل (برای ادمین‌ها)
     */
    public static function registerWithEmail(string $email, string $password): self
    {
        return self::create([
            'email' => $email,
            'password' => bcrypt($password),
            'is_active' => true,
        ]);
    }

    /**
     * بررسی تایید موبایل
     */
    public function isPhoneVerified(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * بررسی تایید ایمیل
     */
    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * بررسی فعال بودن کاربر
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Register media collections for Spatie MediaLibrary
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useDisk('minio')
            ->registerMediaConversions(function () {
                $this->addMediaConversion('thumb')
                    ->width(100)
                    ->height(100)
                    ->sharpen(10);

                $this->addMediaConversion('medium')
                    ->width(300)
                    ->height(300)
                    ->sharpen(10);
            });

        $this->addMediaCollection('gallery')
            ->useDisk('minio');
    }

    /**
     * Relations
     */
    public function verificationCodes()
    {
        return $this->hasMany(VerificationCode::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for phone users
     */
    public function scopePhoneUsers($query)
    {
        return $query->whereNotNull('phone')->whereNull('email');
    }

    /**
     * Scope for email users (admins)
     */
    public function scopeEmailUsers($query)
    {
        return $query->whereNotNull('email')->whereNotNull('password');
    }

    /**
     * Accessor for full name or fallback
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->phone ?? $this->email ?? 'کاربر مهمان';
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user has vendor role
     */
    public function isVendor(): bool
    {
        return $this->hasRole('vendor');
    }
}
