<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'category_id',
        'title',
        'slug',
        'summary',
        'content',
        'featured_image',
        'status',
        'is_featured',
        'is_breaking',
        'views',
        'likes',
        'published_at',
        'meta_tags',
        'settings',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_breaking' => 'boolean',
        'views' => 'integer',
        'likes' => 'integer',
        'published_at' => 'datetime',
        'meta_tags' => 'array',
        'settings' => 'array',
    ];
    public function seo()
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    public function getSeoTitleAttribute()
    {
        return $this->title ?? null;
    }

    public function getSeoDescriptionAttribute()
    {
        return $this->summary ?? $this->excerpt ?? null;
    }

    public function getSeoKeywordsAttribute()
    {
        return isset($this->meta_tags['keywords']) ? implode(', ', $this->meta_tags['keywords']) : null;
    }
    // ========== Media Library ==========
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(200)
                    ->height(150)
                    ->fit('crop', 200, 150)
                    ->nonQueued();

                $this->addMediaConversion('medium')
                    ->width(600)
                    ->height(400)
                    ->fit('crop', 600, 400)
                    ->nonQueued();

                $this->addMediaConversion('large')
                    ->width(1200)
                    ->height(600)
                    ->fit('crop', 1200, 600)
                    ->nonQueued();
            });
    }

    // ========== Relationships ==========
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class);
    }

    public function approvedComments()
    {
        return $this->hasMany(PostComment::class)->where('status', 'approved');
    }

    // ========== Accessors ==========
    public function getFeaturedImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('featured_image');
        return $media ? $media->getUrl() : $this->featured_image;
    }

    public function getFeaturedImageThumbAttribute(): ?string
    {
        $media = $this->getFirstMedia('featured_image');
        return $media ? $media->getUrl('thumb') : null;
    }

    public function getFeaturedImageMediumAttribute(): ?string
    {
        $media = $this->getFirstMedia('featured_image');
        return $media ? $media->getUrl('medium') : null;
    }

    public function getExcerptAttribute(): string
    {
        return Str::limit(strip_tags($this->content), 150);
    }

    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return ceil($wordCount / 200);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'draft' => 'پیش‌نویس',
            'published' => 'منتشر شده',
            'archived' => 'بایگانی شده',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            'draft' => 'warning',
            'published' => 'success',
            'archived' => 'secondary',
        ];
        return $colors[$this->status] ?? 'secondary';
    }

    public function getUrlAttribute(): string
    {
        return route('blog.post', $this->slug);
    }

    // ========== Scopes ==========
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeBreaking($query)
    {
        return $query->where('is_breaking', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
                ->orWhere('content', 'LIKE', "%{$term}%")
                ->orWhere('summary', 'LIKE', "%{$term}%");
        });
    }

    // ========== Methods ==========
    public function generateSlug(): string
    {
        $slug = Str::slug($this->title);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function incrementLikes(): void
    {
        $this->increment('likes');
    }

    public function decrementLikes(): void
    {
        $this->decrement('likes');
    }

    // ========== Boot ==========
    protected static function booted()
    {
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = $post->generateSlug();
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && !$post->isDirty('slug')) {
                $post->slug = $post->generateSlug();
            }
        });
    }
}
