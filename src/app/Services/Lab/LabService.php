<?php

namespace App\Services\Lab;

use App\Models\LabOrder;
use App\Models\LabOrderTest;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\LabCategory;
use App\Enums\LabOrderStatusEnum;
use App\Enums\LabPriorityEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id', 1);
    }

    // ============================================================
    // ORDERS
    // ============================================================

    public function getOrders(array $filters = [], int $perPage = 15)
    {
        $query = LabOrder::with(['patient', 'doctor', 'orderTests', 'orderTests.labTest'])
            ->where('tenant_id', $this->tenantId);

        if (isset($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (isset($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getOrder(int $id): LabOrder
    {
        return LabOrder::with(['patient', 'doctor', 'orderTests', 'orderTests.labTest', 'results', 'results.labTest'])
            ->where('tenant_id', $this->tenantId)
            ->findOrFail($id);
    }

    public function createOrder(array $data): LabOrder
    {
        return DB::transaction(function () use ($data) {
            $order = LabOrder::create([
                'tenant_id' => $this->tenantId,
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'priority' => $data['priority'] ?? 'routine',
                'sample_type' => $data['sample_type'] ?? null,
                'notes' => $data['notes'] ?? null,
                'clinical_history' => $data['clinical_history'] ?? null,
                'status' => 'pending',
                'metadata' => $data['metadata'] ?? null,
            ]);

            $totalPrice = 0;

            foreach ($data['tests'] as $testData) {
                $test = LabTest::findOrFail($testData['test_id']);
                $unitPrice = $testData['unit_price'] ?? $test->price;
                $quantity = $testData['quantity'] ?? 1;
                $discount = $testData['discount'] ?? 0;
                $totalPrice += ($unitPrice * $quantity) - $discount;

                LabOrderTest::create([
                    'tenant_id' => $this->tenantId,
                    'lab_order_id' => $order->id,
                    'lab_test_id' => $testData['test_id'],
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'discount' => $discount,
                    'total_price' => ($unitPrice * $quantity) - $discount,
                    'notes' => $testData['notes'] ?? null,
                    'is_urgent' => $testData['is_urgent'] ?? false,
                ]);
            }

            $order->update([
                'total_price' => $totalPrice,
            ]);

            Log::info('Lab order created', [
                'order_id' => $order->id,
                'patient_id' => $order->patient_id,
                'tests_count' => count($data['tests']),
            ]);

            return $order->fresh(['patient', 'doctor', 'orderTests', 'orderTests.labTest']);
        });
    }

    public function updateOrderStatus(int $orderId, string $status, ?string $reason = null): LabOrder
    {
        $order = $this->getOrder($orderId);
        
        $order->update([
            'status' => $status,
            'cancelled_at' => $status === 'cancelled' ? now() : null,
            'cancelled_reason' => $status === 'cancelled' ? $reason : null,
            'rejected_at' => $status === 'rejected' ? now() : null,
            'rejected_reason' => $status === 'rejected' ? $reason : null,
        ]);

        Log::info('Lab order status updated', [
            'order_id' => $order->id,
            'new_status' => $status,
        ]);

        return $order->fresh();
    }

    public function getPatientOrders(int $patientId, array $filters = [], int $perPage = 15)
    {
        $query = LabOrder::with(['doctor', 'orderTests', 'orderTests.labTest'])
            ->where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getDoctorOrders(int $doctorId, array $filters = [], int $perPage = 15)
    {
        $query = LabOrder::with(['patient', 'orderTests', 'orderTests.labTest'])
            ->where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    // ============================================================
    // RESULTS
    // ============================================================

    public function addResult(array $data): LabResult
    {
        return DB::transaction(function () use ($data) {
            $result = LabResult::create([
                'tenant_id' => $this->tenantId,
                'lab_order_id' => $data['lab_order_id'],
                'lab_order_test_id' => $data['lab_order_test_id'] ?? null,
                'lab_test_id' => $data['lab_test_id'],
                'value' => $data['value'] ?? null,
                'range_low' => $data['range_low'] ?? null,
                'range_high' => $data['range_high'] ?? null,
                'unit' => $data['unit'] ?? null,
                'comment' => $data['comment'] ?? null,
                'interpretation' => $data['interpretation'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // بررسی وضعیت نتیجه
            $test = LabTest::find($data['lab_test_id']);
            if ($test && $data['value'] !== null) {
                $isAbnormal = $test->isAbnormal($data['value']);
                $isCritical = $test->isCritical($data['value']);
                
                $result->update([
                    'is_abnormal' => $isAbnormal,
                    'is_critical' => $isCritical,
                    'status' => $isCritical ? 'critical' : ($isAbnormal ? 'abnormal' : 'completed'),
                ]);
            }

            Log::info('Lab result added', [
                'result_id' => $result->id,
                'order_id' => $data['lab_order_id'],
                'test_id' => $data['lab_test_id'],
            ]);

            return $result->fresh();
        });
    }

    public function addResults(array $results): array
    {
        return DB::transaction(function () use ($results) {
            $created = [];
            foreach ($results as $data) {
                $created[] = $this->addResult($data);
            }
            return $created;
        });
    }

    public function verifyResult(int $resultId): LabResult
    {
        $result = LabResult::findOrFail($resultId);
        
        $result->update([
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'status' => 'completed',
        ]);

        Log::info('Lab result verified', [
            'result_id' => $result->id,
            'verified_by' => auth()->id(),
        ]);

        return $result->fresh();
    }

    public function deleteResult(int $resultId): void
    {
        $result = LabResult::findOrFail($resultId);
        $result->delete();

        Log::info('Lab result deleted', [
            'result_id' => $resultId,
        ]);
    }

    // ============================================================
    // TESTS
    // ============================================================

    public function getTests(array $filters = [], int $perPage = 20)
    {
        $query = LabTest::with(['category'])
            ->where('tenant_id', $this->tenantId);

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getActiveTests()
    {
        return LabTest::with(['category'])
            ->where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function createTest(array $data): LabTest
    {
        $data['tenant_id'] = $this->tenantId;
        return LabTest::create($data);
    }

    public function updateTest(LabTest $test, array $data): LabTest
    {
        $test->update($data);
        return $test->fresh();
    }

    public function deleteTest(LabTest $test): void
    {
        $test->delete();
    }

    public function toggleTestStatus(LabTest $test): LabTest
    {
        $test->update(['is_active' => !$test->is_active]);
        return $test->fresh();
    }

    // ============================================================
    // CATEGORIES
    // ============================================================

    public function getCategories(array $filters = [], int $perPage = 20)
    {
        $query = LabCategory::where('tenant_id', $this->tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->ordered()->paginate($perPage);
    }

    public function getActiveCategories()
    {
        return LabCategory::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->ordered()
            ->get();
    }

    public function createCategory(array $data): LabCategory
    {
        $data['tenant_id'] = $this->tenantId;
        if (empty($data['slug'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }
        return LabCategory::create($data);
    }

    public function updateCategory(LabCategory $category, array $data): LabCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    public function deleteCategory(LabCategory $category): void
    {
        $category->delete();
    }

    // ============================================================
    // STATS
    // ============================================================

    public function getStats(array $filters = []): array
    {
        $query = LabOrder::where('tenant_id', $this->tenantId);

        if (isset($filters['doctor_id'])) {
            $query->where('doctor_id', $filters['doctor_id']);
        }

        if (isset($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'processing' => (clone $query)->whereIn('status', ['sample_collected', 'processing'])->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];
    }
}
