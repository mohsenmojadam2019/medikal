<?php
// app/Http/Controllers/Admin/BlogController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\PostComment;
use App\Services\Blog\BlogService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    use ApiResponse;

    protected BlogService $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    // ==========================================
    // ========== POST MANAGEMENT ==========
    // ==========================================

    /**
     * لیست مقالات (ادمین)
     */
    public function posts(Request $request)
    {
        try {
            $posts = $this->blogService->getPosts(
                $request->all(),
                $request->get('per_page', 15)
            );
            return $this->success($posts);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * ایجاد مقاله جدید
     */
    public function storePost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'summary' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:post_categories,id',
            'status' => 'nullable|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'is_breaking' => 'nullable|boolean',
            'meta_tags' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['user_id'] = auth()->id();
            $data['tenant_id'] = session('tenant_id', 1);

            $post = $this->blogService->createPost($data);

            // آپلود تصویر شاخص
            if ($request->hasFile('featured_image')) {
                $post->addMedia($request->file('featured_image'))
                    ->toMediaCollection('featured_image');
            }

            return $this->success(
                $post->load(['user', 'category', 'tags']),
                'مقاله با موفقیت ایجاد شد',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش مقاله (ادمین)
     */
    public function adminShowPost($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $post = Post::where('tenant_id', $tenantId)
                ->with(['user', 'category', 'tags', 'comments.user'])
                ->findOrFail($id);
            return $this->success($post);
        } catch (\Exception $e) {
            return $this->error('مقاله یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی مقاله
     */
    public function updatePost(Request $request, $id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $post = Post::where('tenant_id', $tenantId)->findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('مقاله یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'summary' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:post_categories,id',
            'status' => 'nullable|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'is_breaking' => 'nullable|boolean',
            'meta_tags' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();

            if ($request->hasFile('featured_image')) {
                $post->clearMediaCollection('featured_image');
                $post->addMedia($request->file('featured_image'))
                    ->toMediaCollection('featured_image');
            }

            $post = $this->blogService->updatePost($post, $data);

            return $this->success(
                $post->load(['user', 'category', 'tags']),
                'مقاله با موفقیت به‌روزرسانی شد'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف مقاله
     */
    public function deletePost($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $post = Post::where('tenant_id', $tenantId)->findOrFail($id);
            $post->clearMediaCollection('featured_image');
            $this->blogService->deletePost($post);
            return $this->success(null, 'مقاله با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * انتشار مقاله
     */
    public function publishPost($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $post = Post::where('tenant_id', $tenantId)->findOrFail($id);
            $post->publish();
            return $this->success($post->fresh(), 'مقاله با موفقیت منتشر شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * خارج کردن از انتشار
     */
    public function unpublishPost($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $post = Post::where('tenant_id', $tenantId)->findOrFail($id);
            $post->unpublish();
            return $this->success($post->fresh(), 'مقاله از انتشار خارج شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ==========================================
    // ========== CATEGORY MANAGEMENT ==========
    // ==========================================

    /**
     * لیست دسته‌بندی‌ها (ادمین)
     */
    public function adminCategories(Request $request)
    {
        try {
            $categories = $this->blogService->getCategories(
                $request->all(),
                $request->get('per_page', 20)
            );
            return $this->success($categories);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * ایجاد دسته‌بندی
     */
    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:post_categories',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }
            $data['is_active'] = $data['is_active'] ?? true;

            $category = $this->blogService->createCategory($data);
            return $this->success($category, 'دسته‌بندی با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * به‌روزرسانی دسته‌بندی
     */
    public function updateCategory(Request $request, $id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $category = PostCategory::where('tenant_id', $tenantId)->findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('دسته‌بندی یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'slug' => 'nullable|string|max:100|unique:post_categories,slug,' . $id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $category = $this->blogService->updateCategory($category, $request->all());
            return $this->success($category, 'دسته‌بندی با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف دسته‌بندی
     */
    public function deleteCategory($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $category = PostCategory::where('tenant_id', $tenantId)->findOrFail($id);
            $this->blogService->deleteCategory($category);
            return $this->success(null, 'دسته‌بندی با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ==========================================
    // ========== TAG MANAGEMENT ==========
    // ==========================================

    /**
     * لیست تگ‌ها (ادمین)
     */
    public function adminTags(Request $request)
    {
        try {
            $tags = $this->blogService->getTags(
                $request->all(),
                $request->get('per_page', 20)
            );
            return $this->success($tags);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * ایجاد تگ
     */
    public function storeTag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'slug' => 'nullable|string|max:50|unique:tags',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }
            $data['is_active'] = $data['is_active'] ?? true;

            $tag = $this->blogService->createTag($data);
            return $this->success($tag, 'تگ با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * به‌روزرسانی تگ
     */
    public function updateTag(Request $request, $id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $tag = Tag::where('tenant_id', $tenantId)->findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('تگ یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50',
            'slug' => 'nullable|string|max:50|unique:tags,slug,' . $id,
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $tag = $this->blogService->updateTag($tag, $request->all());
            return $this->success($tag, 'تگ با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف تگ
     */
    public function deleteTag($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $tag = Tag::where('tenant_id', $tenantId)->findOrFail($id);
            $this->blogService->deleteTag($tag);
            return $this->success(null, 'تگ با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ==========================================
    // ========== COMMENT MANAGEMENT ==========
    // ==========================================

    /**
     * لیست کامنت‌ها (ادمین)
     */
    public function adminComments(Request $request)
    {
        try {
            $comments = $this->blogService->getComments(
                $request->all(),
                $request->get('per_page', 20)
            );
            return $this->success($comments);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * تایید کامنت
     */
    public function approveComment($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $comment = PostComment::where('tenant_id', $tenantId)->findOrFail($id);
            $comment = $this->blogService->approveComment($comment);
            return $this->success($comment, 'کامنت با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * رد کامنت
     */
    public function rejectComment($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $comment = PostComment::where('tenant_id', $tenantId)->findOrFail($id);
            $comment = $this->blogService->rejectComment($comment);
            return $this->success($comment, 'کامنت با موفقیت رد شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف کامنت
     */
    public function deleteComment($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $comment = PostComment::where('tenant_id', $tenantId)->findOrFail($id);
            $this->blogService->deleteComment($comment);
            return $this->success(null, 'کامنت با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ==========================================
    // ========== STATS ==========
    // ==========================================

    /**
     * آمار وبلاگ
     */
    public function stats()
    {
        try {
            $stats = $this->blogService->getStats();
            return $this->success($stats);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
