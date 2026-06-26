<?php

namespace App\Services\Blog;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\PostComment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class BlogService
{
    // ========== POSTS ==========

    public function getPosts(array $filters = [], int $perPage = 15)
    {
        $query = Post::with(['user', 'category', 'tags']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['tag_id'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('id', $filters['tag_id']);
            });
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (isset($filters['is_breaking'])) {
            $query->where('is_breaking', $filters['is_breaking']);
        }

        $sortBy = $filters['sort_by'] ?? 'published_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    public function getPublishedPosts(array $filters = [], int $perPage = 15)
    {
        $filters['status'] = 'published';
        return $this->getPosts($filters, $perPage);
    }

    public function getFeaturedPosts(int $limit = 5)
    {
        return Post::published()
            ->featured()
            ->with(['user', 'category', 'tags'])
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getBreakingPosts(int $limit = 3)
    {
        return Post::published()
            ->breaking()
            ->with(['user', 'category', 'tags'])
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getPopularPosts(int $limit = 5)
    {
        return Post::published()
            ->with(['user', 'category', 'tags'])
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getPostBySlug(string $slug)
    {
        $post = Post::with(['user', 'category', 'tags', 'comments.user'])
            ->where('slug', $slug)
            ->firstOrFail();

        $post->incrementViews();
        return $post;
    }

    public function getRelatedPosts(Post $post, int $limit = 3)
    {
        return Post::published()
            ->where('id', '!=', $post->id)
            ->where(function ($query) use ($post) {
                $query->where('category_id', $post->category_id)
                    ->orWhereHas('tags', function ($q) use ($post) {
                        $q->whereIn('id', $post->tags->pluck('id'));
                    });
            })
            ->with(['user', 'category', 'tags'])
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function createPost(array $data): Post
    {
        return DB::transaction(function () use ($data) {
            $post = Post::create([
                'user_id' => $data['user_id'] ?? auth()->id(),
                'category_id' => $data['category_id'] ?? null,
                'title' => $data['title'],
                'slug' => $data['slug'] ?? null,
                'summary' => $data['summary'] ?? null,
                'content' => $data['content'],
                'status' => $data['status'] ?? 'draft',
                'is_featured' => $data['is_featured'] ?? false,
                'is_breaking' => $data['is_breaking'] ?? false,
                'meta_tags' => $data['meta_tags'] ?? null,
                'settings' => $data['settings'] ?? null,
                'published_at' => isset($data['status']) && $data['status'] == 'published' ? now() : null,
            ]);

            if (isset($data['tags']) && is_array($data['tags'])) {
                $tagIds = [];
                foreach ($data['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName], ['slug' => Str::slug($tagName)]);
                    $tagIds[] = $tag->id;
                }
                $post->tags()->sync($tagIds);
            }

            return $post->fresh(['user', 'category', 'tags']);
        });
    }

    public function updatePost(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data) {
            if (isset($data['status']) && $data['status'] == 'published' && $post->status != 'published') {
                $data['published_at'] = now();
            }

            $post->update($data);

            if (isset($data['tags']) && is_array($data['tags'])) {
                $tagIds = [];
                foreach ($data['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName], ['slug' => Str::slug($tagName)]);
                    $tagIds[] = $tag->id;
                }
                $post->tags()->sync($tagIds);
            }

            return $post->fresh(['user', 'category', 'tags']);
        });
    }

    public function deletePost(Post $post): void
    {
        $post->delete();
    }

    // ========== CATEGORIES ==========

    public function getCategories(array $filters = [], int $perPage = 20)
    {
        $query = PostCategory::query();

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getActiveCategories()
    {
        return PostCategory::active()
            ->orderBy('name')
            ->withCount(['posts' => function ($query) {
                $query->published();
            }])
            ->get();
    }

    public function createCategory(array $data): PostCategory
    {
        return PostCategory::create($data);
    }

    public function updateCategory(PostCategory $category, array $data): PostCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    public function deleteCategory(PostCategory $category): void
    {
        $category->delete();
    }

    // ========== TAGS ==========

    public function getTags(array $filters = [], int $perPage = 20)
    {
        $query = Tag::query();

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getActiveTags()
    {
        return Tag::active()
            ->withCount('posts')
            ->orderBy('name')
            ->get();
    }

    public function createTag(array $data): Tag
    {
        return Tag::create($data);
    }

    public function updateTag(Tag $tag, array $data): Tag
    {
        $tag->update($data);
        return $tag->fresh();
    }

    public function deleteTag(Tag $tag): void
    {
        $tag->delete();
    }

    // ========== COMMENTS ==========

    public function getComments(array $filters = [], int $perPage = 20)
    {
        $query = PostComment::with(['user', 'post']);

        if (isset($filters['post_id'])) {
            $query->where('post_id', $filters['post_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where('content', 'LIKE', "%{$filters['search']}%")
                ->orWhere('author_name', 'LIKE', "%{$filters['search']}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getPostComments(int $postId)
    {
        return PostComment::where('post_id', $postId)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function createComment(array $data): PostComment
    {
        return DB::transaction(function () use ($data) {
            $comment = PostComment::create([
                'post_id' => $data['post_id'],
                'user_id' => $data['user_id'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'author_name' => $data['author_name'] ?? null,
                'author_email' => $data['author_email'] ?? null,
                'content' => $data['content'],
                'status' => $data['status'] ?? 'pending',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $comment->fresh(['user']);
        });
    }

    public function approveComment(PostComment $comment): PostComment
    {
        $comment->approve();
        return $comment->fresh();
    }

    public function rejectComment(PostComment $comment): PostComment
    {
        $comment->reject();
        return $comment->fresh();
    }

    public function deleteComment(PostComment $comment): void
    {
        $comment->delete();
    }

    // ========== STATS ==========

    public function getStats(): array
    {
        return [
            'total_posts' => Post::count(),
            'published_posts' => Post::published()->count(),
            'draft_posts' => Post::draft()->count(),
            'total_categories' => PostCategory::count(),
            'total_tags' => Tag::count(),
            'total_comments' => PostComment::count(),
            'pending_comments' => PostComment::pending()->count(),
            'total_views' => Post::sum('views'),
            'total_likes' => Post::sum('likes'),
        ];
    }

    public function getMonthlyStats(int $months = 12): array
    {
        $stats = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $stats[] = [
                'month' => $month->format('Y-m'),
                'posts' => Post::whereMonth('published_at', $month->month)
                    ->whereYear('published_at', $month->year)
                    ->count(),
                'views' => Post::whereMonth('published_at', $month->month)
                    ->whereYear('published_at', $month->year)
                    ->sum('views'),
            ];
        }
        return $stats;
    }
}
