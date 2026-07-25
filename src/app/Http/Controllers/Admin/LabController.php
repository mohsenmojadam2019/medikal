<?php
// app/Http/Controllers/Admin/LabController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Lab\LabService;
use App\Models\LabTest;
use App\Models\LabCategory;
use App\Models\LabOrder;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LabController extends Controller
{
    use ApiResponse;

    protected LabService $labService;

    public function __construct(LabService $labService)
    {
        $this->labService = $labService;
    }

    // ============================================================
    // CATEGORIES
    // ============================================================

    /**
     * لیست دسته‌بندی‌ها (ادمین)
     */
    public function categories(Request $request)
    {
        $categories = $this->labService->getCategories(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($categories);
    }

    /**
     * ایجاد دسته‌بندی جدید (ادمین)
     */
    public function storeCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $category = $this->labService->createCategory($request->all());
            return $this->success($category, 'دسته‌بندی با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * بروزرسانی دسته‌بندی (ادمین)
     */
    public function updateCategory(Request $request, $id)
    {
        try {
            $category = LabCategory::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('دسته‌بندی یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $category = $this->labService->updateCategory($category, $request->all());
            return $this->success($category, 'دسته‌بندی با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف دسته‌بندی (ادمین)
     */
    public function deleteCategory($id)
    {
        try {
            $category = LabCategory::findOrFail($id);
            $this->labService->deleteCategory($category);
            return $this->success(null, 'دسته‌بندی با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // TESTS
    // ============================================================

    /**
     * لیست تست‌ها (ادمین)
     */
    public function tests(Request $request)
    {
        $tests = $this->labService->getTests(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($tests);
    }

    /**
     * ایجاد تست جدید (ادمین)
     */
    public function storeTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:lab_categories,id',
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'sample_type' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:20',
            'min_range' => 'nullable|numeric',
            'max_range' => 'nullable|numeric',
            'critical_low' => 'nullable|numeric',
            'critical_high' => 'nullable|numeric',
            'price' => 'nullable|numeric|min:0',
            'turnaround_time' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'requires_fasting' => 'nullable|boolean',
            'fasting_hours' => 'nullable|integer|min:1',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $test = $this->labService->createTest($request->all());
            return $this->success($test, 'تست با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش یک تست (ادمین)
     */
    public function showTest($id)
    {
        try {
            $test = LabTest::with(['category'])->findOrFail($id);
            return $this->success($test);
        } catch (\Exception $e) {
            return $this->error('تست یافت نشد', 404);
        }
    }

    /**
     * بروزرسانی تست (ادمین)
     */
    public function updateTest(Request $request, $id)
    {
        try {
            $test = LabTest::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('تست یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:lab_categories,id',
            'name' => 'sometimes|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'sample_type' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:20',
            'min_range' => 'nullable|numeric',
            'max_range' => 'nullable|numeric',
            'critical_low' => 'nullable|numeric',
            'critical_high' => 'nullable|numeric',
            'price' => 'nullable|numeric|min:0',
            'turnaround_time' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'requires_fasting' => 'nullable|boolean',
            'fasting_hours' => 'nullable|integer|min:1',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $test = $this->labService->updateTest($test, $request->all());
            return $this->success($test, 'تست با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف تست (ادمین)
     */
    public function deleteTest($id)
    {
        try {
            $test = LabTest::findOrFail($id);
            $this->labService->deleteTest($test);
            return $this->success(null, 'تست با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ✅ تغییر وضعیت تست (فعال/غیرفعال) - ادمین
     */
    public function toggleTestStatus($id)
    {
        try {
            $test = LabTest::findOrFail($id);
            $test = $this->labService->toggleTestStatus($test);
            return $this->success($test, 'وضعیت تست با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // ORDERS (ادمین)
    // ============================================================

    /**
     * لیست سفارشات آزمایشگاه (ادمین)
     */
    public function orders(Request $request)
    {
        $orders = $this->labService->getOrders(
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($orders);
    }

    /**
     * نمایش یک سفارش (ادمین)
     */
    public function showOrder($id)
    {
        try {
            $order = $this->labService->getOrder($id);
            return $this->success($order);
        } catch (\Exception $e) {
            return $this->error('سفارش یافت نشد', 404);
        }
    }

    /**
     * بروزرسانی وضعیت سفارش (ادمین)
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,waiting_payment,paid,scheduled,sample_collected,processing,partial,completed,cancelled,rejected',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $order = $this->labService->updateOrderStatus(
                $id,
                $request->status,
                $request->reason
            );
            return $this->success($order, 'وضعیت سفارش با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // STATS (ادمین)
    // ============================================================

    /**
     * آمار کلی آزمایشگاه (ادمین)
     */
    public function stats(Request $request)
    {
        $stats = $this->labService->getStats($request->all());
        return $this->success($stats);
    }
}
