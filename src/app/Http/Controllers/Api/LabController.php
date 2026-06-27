<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Lab\LabService;
use App\Traits\ApiResponse;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\LabCategory;
use App\Http\Requests\Api\LabOrderRequest;
use App\Http\Requests\Api\LabResultRequest;
use App\Http\Requests\Api\LabTestRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LabController extends Controller
{
    use ApiResponse;

    protected LabService $labService;

    public function __construct(LabService $labService)
    {
        $this->labService = $labService;
        $this->middleware(['auth:sanctum']);
    }

    // ============================================================
    // ORDERS
    // ============================================================

    public function orders(Request $request)
    {
        $orders = $this->labService->getOrders(
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($orders);
    }

    public function createOrder(LabOrderRequest $request)
    {
        try {
            $order = $this->labService->createOrder($request->validated());
            return $this->success($order, 'سفارش آزمایش با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function showOrder($id)
    {
        try {
            $order = $this->labService->getOrder($id);

            // بررسی دسترسی
            $user = auth()->user();
            if (!$user->isAdmin() &&
                $order->patient->user_id != $user->id &&
                $order->doctor->user_id != $user->id) {
                return $this->error('شما دسترسی به این سفارش ندارید', 403);
            }

            return $this->success($order);
        } catch (\Exception $e) {
            return $this->error('سفارش یافت نشد', 404);
        }
    }

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
    // MY ORDERS (Patient)
    // ============================================================

    public function myOrders(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $orders = $this->labService->getPatientOrders(
            $patient->id,
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($orders);
    }

    public function myDoctorOrders(Request $request)
    {
        $user = auth()->user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('پزشک یافت نشد', 404);
        }

        $orders = $this->labService->getDoctorOrders(
            $doctor->id,
            $request->all(),
            $request->get('per_page', 15)
        );
        return $this->success($orders);
    }

    // ============================================================
    // RESULTS
    // ============================================================

    public function addResult(LabResultRequest $request)
    {
        try {
            $result = $this->labService->addResult($request->validated());
            return $this->success($result, 'نتیجه آزمایش با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function addResults(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'results' => 'required|array|min:1',
            'results.*.lab_order_id' => 'required|exists:lab_orders,id',
            'results.*.lab_order_test_id' => 'nullable|exists:lab_order_tests,id',
            'results.*.lab_test_id' => 'required|exists:lab_tests,id',
            'results.*.value' => 'nullable|numeric',
            'results.*.comment' => 'nullable|string|max:1000',
            'results.*.interpretation' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $results = $this->labService->addResults($request->results);
            return $this->success($results, 'نتایج آزمایش با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function verifyResult($id)
    {
        try {
            $result = $this->labService->verifyResult($id);
            return $this->success($result, 'نتیجه آزمایش با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteResult($id)
    {
        try {
            $this->labService->deleteResult($id);
            return $this->success(null, 'نتیجه آزمایش با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // TESTS
    // ============================================================

    public function tests(Request $request)
    {
        $tests = $this->labService->getTests(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($tests);
    }

    public function activeTests()
    {
        $tests = $this->labService->getActiveTests();
        return $this->success($tests);
    }

    public function showTest($id)
    {
        try {
            $test = LabTest::with(['category'])->findOrFail($id);
            return $this->success($test);
        } catch (\Exception $e) {
            return $this->error('تست یافت نشد', 404);
        }
    }

    // Admin methods for tests
    public function storeTest(LabTestRequest $request)
    {
        try {
            $test = $this->labService->createTest($request->validated());
            return $this->success($test, 'تست با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateTest(LabTestRequest $request, $id)
    {
        try {
            $test = LabTest::findOrFail($id);
            $test = $this->labService->updateTest($test, $request->validated());
            return $this->success($test, 'تست با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

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
    // CATEGORIES
    // ============================================================

    public function categories(Request $request)
    {
        $categories = $this->labService->getCategories(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($categories);
    }

    public function activeCategories()
    {
        $categories = LabCategory::active()
            ->ordered()
            ->withCount('tests')
            ->get();
        return $this->success($categories);
    }

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

    public function updateCategory(Request $request, $id)
    {
        try {
            $category = LabCategory::findOrFail($id);
            $category = $this->labService->updateCategory($category, $request->all());
            return $this->success($category, 'دسته‌بندی با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

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
    // STATS
    // ============================================================

    public function stats(Request $request)
    {
        $stats = $this->labService->getStats($request->all());
        return $this->success($stats);
    }

    public function myStats()
    {
        $user = auth()->user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return $this->error('شما پزشک نیستید', 403);
        }

        $stats = $this->labService->getStats(['doctor_id' => $doctor->id]);
        return $this->success($stats);
    }
}
