<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seo extends Model
{
    protected $table = 'seo';  // ✅ نام جدول درست

    protected $fillable = [
        'tenant_id',
        'seoable_type',
        'seoable_id',
        'title',
        'description',
        'keywords',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'robots',
        'schema_json',
        'meta_tags',
    ];

    protected $casts = [
        'meta_tags' => 'array',
        'schema_json' => 'array',
    ];

    public function seoable()
    {
        return $this->morphTo();
    }

    public function renderMetaTags(): string
    {
        $html = '';

        if ($this->title) {
            $html .= "<title>{$this->title}</title>\n";
            $html .= "<meta property=\"og:title\" content=\"{$this->title}\">\n";
            $html .= "<meta name=\"twitter:title\" content=\"{$this->title}\">\n";
        }

        if ($this->description) {
            $html .= "<meta name=\"description\" content=\"{$this->description}\">\n";
            $html .= "<meta property=\"og:description\" content=\"{$this->description}\">\n";
            $html .= "<meta name=\"twitter:description\" content=\"{$this->description}\">\n";
        }

        if ($this->keywords) {
            $html .= "<meta name=\"keywords\" content=\"{$this->keywords}\">\n";
        }

        if ($this->canonical_url) {
            $html .= "<link rel=\"canonical\" href=\"{$this->canonical_url}\">\n";
        }

        if ($this->robots) {
            $html .= "<meta name=\"robots\" content=\"{$this->robots}\">\n";
        }

        if ($this->og_image) {
            $html .= "<meta property=\"og:image\" content=\"{$this->og_image}\">\n";
        }

        if ($this->twitter_image) {
            $html .= "<meta name=\"twitter:image\" content=\"{$this->twitter_image}\">\n";
        }

        if ($this->schema_json) {
            $html .= "<script type=\"application/ld+json\">\n";
            $html .= json_encode($this->schema_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $html .= "\n</script>\n";
        }

        if ($this->meta_tags) {
            foreach ($this->meta_tags as $name => $content) {
                $html .= "<meta name=\"{$name}\" content=\"{$content}\">\n";
            }
        }

        return $html;
    }
}
