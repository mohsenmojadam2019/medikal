<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function generateSlug(): string
    {
        $slug = Str::slug($this->name);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    protected static function booted()
    {
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = $tag->generateSlug();
            }
        });
    }
}
