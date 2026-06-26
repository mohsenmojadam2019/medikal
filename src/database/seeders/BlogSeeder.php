<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'پزشکی و سلامت', 'slug' => 'medical-health'],
            ['name' => 'روانشناسی', 'slug' => 'psychology'],
            ['name' => 'تغذیه و رژیم', 'slug' => 'nutrition'],
            ['name' => 'اخبار پزشکی', 'slug' => 'medical-news'],
            ['name' => 'مقالات علمی', 'slug' => 'scientific-articles'],
        ];

        foreach ($categories as $cat) {
            PostCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $cat['name'], 'is_active' => true]
            );
        }

        $tags = ['سلامت', 'پزشکی', 'روانشناسی', 'تغذیه', 'ورزش', 'بیماری', 'درمان', 'پیشگیری'];
        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['name' => $tag],
                ['name' => $tag, 'slug' => \Illuminate\Support\Str::slug($tag), 'is_active' => true]
            );
        }

        $this->command->info('✅ دسته‌بندی‌ها و تگ‌های بلاگ ایجاد شدند.');
    }
}
