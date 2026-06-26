<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'phone',
        'email',
        'website',
        'logo',
        'favicon',
        'latitude',
        'longitude',
        'timezone',
        'currency',
        'language',
        'tax_rate',
        'invoice_prefix',
        'appointment_prefix',
        'primary_color',
        'secondary_color',
        'theme',
        'is_active',
        'is_verified',
        'metadata',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'tax_rate' => 'decimal:2',
        'metadata' => 'array',
        'settings' => 'array',
    ];

    // ========== Relationships ==========
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    public function patients()
    {
        return $this->hasMany(Patient::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // ========== Accessors ==========
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? asset('storage/' . $this->favicon) : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'فعال' : 'غیرفعال';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'success' : 'danger';
    }

    public function getIsVerifiedLabelAttribute(): string
    {
        return $this->is_verified ? 'تایید شده' : 'تایید نشده';
    }

    // ========== Scopes ==========
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('slug', 'LIKE', "%{$term}%")
                ->orWhere('address', 'LIKE', "%{$term}%")
                ->orWhere('phone', 'LIKE', "%{$term}%")
                ->orWhere('email', 'LIKE', "%{$term}%");
        });
    }

    public function scopeNearby($query, $lat, $lng, $radius = 10)
    {
        return $query->selectRaw("
            *,
            (6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )) AS distance
        ", [$lat, $lng, $lat])
        ->having('distance', '<', $radius)
        ->orderBy('distance', 'asc');
    }

    // ========== Methods ==========
    public function generateSlug(): string
    {
        $slug = \Illuminate\Support\Str::slug($this->name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function verify(): void
    {
        $this->update(['is_verified' => true]);
    }

    public function unverify(): void
    {
        $this->update(['is_verified' => false]);
    }

    // ========== Boot ==========
    protected static function booted()
    {
        static::creating(function ($clinic) {
            if (empty($clinic->slug)) {
                $clinic->slug = $clinic->generateSlug();
            }
        });
    }
}
