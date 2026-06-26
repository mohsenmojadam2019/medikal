<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Blog\BlogService;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\PostComment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
{
    use ApiResponse;

    protected BlogService $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    // ========== PUBLIC ROUTES ==========

    /**
     * لیست پست‌های منتشر شده
     */
    public function posts(Request $request)
    {
        $posts = $this->blogService->getPublishedPosts(
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($posts);
    }

    /**
     * نمایش یک پست با اسلاگ
     */
    public function show($slug)
    {
        try {
            $post = $this->blogService->getPostBySlug($slug);
            $related = $this->blogService->getRelatedPosts($post);
            $comments = $this->blogService->getPostComments($post->id);

            return $this->success([
                'post' => $post,
                'related' => $related,
                'comments' => $comments,
            ]);
        } catch (\Exception $e) {
            return $this->error('پست یافت نشد', 404);
        }
    }

    /**
     * پست‌های برتر
     */
    public function featured(Request $request)
    {
        $posts = $this->blogService->getFeaturedPosts($request->get('limit', 5));
        return $this->success($posts);
    }

    /**
     * پست‌های فوری
     */
    public function breaking(Request $request)
    {
        $posts = $this->blogService->getBreakingPosts($request->get('limit', 3));
        return $this->success($posts);
    }

    /**
     * پست‌های محبوب
     */
    public function popular(Request $request)
    {
        $posts = $this->blogService->getPopularPosts($request->get('limit', 5));
        return $this->success($posts);
    }

    /**
     * لیست دسته‌بندی‌ها
     */
    public function categories()
    {
        $categories = $this->blogService->getActiveCategories();
        return $this->success($categories);
    }

    /**
     * لیست تگ‌ها
     */
    public function tags()
    {
        $tags = $this->blogService->getActiveTags();
        return $this->success($tags);
    }

    /**
     * ثبت نظر جدید
     */
    public function comment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
            'content' => 'required|string|min:3|max:1000',
            'parent_id' => 'nullable|exists:post_comments,id',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['user_id'] = auth()->id();
            $data['status'] = 'pending';

            $comment = $this->blogService->createComment($data);

            return $this->success($comment, 'نظر شما با موفقیت ثبت شد و در انتظار تایید است', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ========== ADMIN ROUTES ==========

    /**
     * لیست پست‌ها (ادمین)
     */
    public function adminPosts(Request $request)
    {
        $posts = $this->blogService->getPosts(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($posts);
    }

    /**
     * ایجاد پست جدید (ادمین)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'summary' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:post_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'nullable|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'is_breaking' => 'nullable|boolean',
            'featured_image' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->except(['featured_image']);
            $data['user_id'] = auth()->id();

            $post = $this->blogService->createPost($data);

            // آپلود عکس
            if ($request->hasFile('featured_image')) {
                $post->addMediaFromRequest('featured_image')
                    ->toMediaCollection('featured_image');
            }

            return $this->success($post->fresh(), 'پست با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش پست (ادمین)
     */
    public function adminShow($id)
    {
        try {
            $post = Post::with(['user', 'category', 'tags', 'comments'])->findOrFail($id);
            return $this->success($post);
        } catch (\Exception $e) {
            return $this->error('پست یافت نشد', 404);
        }
    }

    /**
     * بروزرسانی پست (ادمین)
     */
    public function update(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('پست یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'summary' => 'nullable|string|max:500',
            'category_id' => 'nullable|exists:post_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'nullable|in:draft,published,archived',
            'is_featured' => 'nullable|boolean',
            'is_breaking' => 'nullable|boolean',
            'featured_image' => 'nullable|image|max:5120',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->except(['featured_image']);

            $post = $this->blogService->updatePost($post, $data);

            // آپلود عکس
            if ($request->hasFile('featured_image')) {
                $post->clearMediaCollection('featured_image');
                $post->addMediaFromRequest('featured_image')
                    ->toMediaCollection('featured_image');
            }

            return $this->success($post->fresh(), 'پست با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف پست (ادمین)
     */
    public function destroy($id)
    {
        try {
            $post = Post::findOrFail($id);
            $this->blogService->deletePost($post);
            return $this->success(null, 'پست با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * انتشار پست (ادمین)
     */
    public function publish($id)
    {
        try {
            $post = Post::findOrFail($id);
            $post->publish();
            return $this->success($post->fresh(), 'پست با موفقیت منتشر شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لغو انتشار پست (ادمین)
     */
    public function unpublish($id)
    {
        try {
            $post = Post::findOrFail($id);
            $post->unpublish();
            return $this->success($post->fresh(), 'انتشار پست با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ========== CATEGORY MANAGEMENT ==========

    public function adminCategories(Request $request)
    {
        $categories = $this->blogService->getCategories(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($categories);
    }

    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:post_categories,name',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $category = $this->blogService->createCategory($request->all());
            return $this->success($category, 'دسته‌بندی با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateCategory(Request $request, $id)
    {
        try {
            $category = PostCategory::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('دسته‌بندی یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100|unique:post_categories,name,' . $id,
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $category = $this->blogService->updateCategory($category, $request->all());
            return $this->success($category, 'دسته‌بندی با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteCategory($id)
    {
        try {
            $category = PostCategory::findOrFail($id);
            $this->blogService->deleteCategory($category);
            return $this->success(null, 'دسته‌بندی با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ========== TAG MANAGEMENT ==========

    public function adminTags(Request $request)
    {
        $tags = $this->blogService->getTags(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($tags);
    }

    public function storeTag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:tags,name',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $tag = $this->blogService->createTag($request->all());
            return $this->success($tag, 'تگ با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateTag(Request $request, $id)
    {
        try {
            $tag = Tag::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('تگ یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50|unique:tags,name,' . $id,
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $tag = $this->blogService->updateTag($tag, $request->all());
            return $this->success($tag, 'تگ با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteTag($id)
    {
        try {
            $tag = Tag::findOrFail($id);
            $this->blogService->deleteTag($tag);
            return $this->success(null, 'تگ با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ========== COMMENT MANAGEMENT ==========

    public function adminComments(Request $request)
    {
        $comments = $this->blogService->getComments(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($comments);
    }

    public function approveComment($id)
    {
        try {
            $comment = PostComment::findOrFail($id);
            $this->blogService->approveComment($comment);
            return $this->success($comment->fresh(), 'نظر با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function rejectComment($id)
    {
        try {
            $comment = PostComment::findOrFail($id);
            $this->blogService->rejectComment($comment);
            return $this->success($comment->fresh(), 'نظر با موفقیت رد شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteComment($id)
    {
        try {
            $comment = PostComment::findOrFail($id);
            $this->blogService->deleteComment($comment);
            return $this->success(null, 'نظر با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ========== STATS ==========

    public function stats()
    {
        $stats = $this->blogService->getStats();
        $monthly = $this->blogService->getMonthlyStats(12);
        return $this->success([
            'overview' => $stats,
            'monthly' => $monthly,
        ]);
    }
}
