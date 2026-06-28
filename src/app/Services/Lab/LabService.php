<?php

namespace App\Services\Lab;

use App\Models\LabOrder;
use App\Models\LabTest;
use App\Models\LabResult;
use App\Models\Invoice;
use App\Enums\LabOrderStatusEnum;
use App\Enums\LabResultStatusEnum;
use App\Enums\LabPriorityEnum;
use App\Enums\InvoiceStatusEnum;
use App\Services\Invoice\InvoiceService;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabService
{
    protected $tenantId;
    protected InvoiceService $invoiceService;
    protected NotificationService $notificationService;

    public function __construct(
        InvoiceService $invoiceService,
        NotificationService $notificationService
    ) {
        $this->tenantId = session('tenant_id');
        $this->invoiceService = $invoiceService;
        $this->notificationService = $notificationService;
    }

    public function createOrder(array $data): LabOrder
    {
        return DB::transaction(function () use ($data) {
            $data['tenant_id'] = $this->tenantId;
            $order = LabOrder::create([
                'tenant_id' => $this->tenantId,
                'patient_id' => $data['patient_id'],
                'doctor_id' => $data['doctor_id'] ?? auth()->user()->doctor?->id,
                'appointment_id' => $data['appointment_id'] ?? null,
                'status' => LabOrderStatusEnum::PENDING,
                'priority' => $data['priority'] ?? LabPriorityEnum::ROUTINE,
                'sample_type' => $data['sample_type'] ?? null,
                'notes' => $data['notes'] ?? null,
                'clinical_history' => $data['clinical_history'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $totalPrice = 0;
            foreach ($data['tests'] as $testData) {
                $test = LabTest::where('tenant_id', $this->tenantId)->findOrFail($testData['test_id']);
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

            $this->createInvoice($order, $totalPrice);
            $this->sendOrderCreatedNotifications($order);

            return $order->fresh(['patient', 'doctor', 'orderTests', 'orderTests.labTest']);
        });
    }

    public function getOrder(int $id): LabOrder
    {
        return LabOrder::where('tenant_id', $this->tenantId)
            ->with([
                'patient.user',
                'doctor.user',
                'doctor.specialty',
                'appointment',
                'orderTests.labTest',
                'orderTests.labTest.category',
                'results.labTest',
                'results.files',
                'invoice',
                'labTechnician',
            ])
            ->findOrFail($id);
    }

    public function getOrders(array $filters = [], int $perPage = 15)
    {
        $query = LabOrder::where('tenant_id', $this->tenantId)
            ->with(['patient.user', 'doctor.user', 'orderTests.labTest']);

        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
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

        if (isset($filters['search'])) {
            $query->where('order_number', 'LIKE', "%{$filters['search']}%")
                ->orWhereHas('patient', function ($q) use ($filters) {
                    $q->where('national_code', 'LIKE', "%{$filters['search']}%");
                })
                ->orWhereHas('patient.user', function ($q) use ($filters) {
                    $q->where('name', 'LIKE', "%{$filters['search']}%");
                });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getPatientOrders(int $patientId, array $filters = [], int $perPage = 15)
    {
        $filters['patient_id'] = $patientId;
        return $this->getOrders($filters, $perPage);
    }

    public function getDoctorOrders(int $doctorId, array $filters = [], int $perPage = 15)
    {
        $filters['doctor_id'] = $doctorId;
        return $this->getOrders($filters, $perPage);
    }

    public function updateOrderStatus(int $orderId, string $status, ?string $reason = null): LabOrder
    {
        $order = LabOrder::where('tenant_id', $this->tenantId)->findOrFail($orderId);
        $statusEnum = LabOrderStatusEnum::from($status);

        $order->changeStatus($statusEnum, $reason);
        $this->sendStatusUpdateNotifications($order, $statusEnum);

        return $order->fresh();
    }

    public function addResult(array $data): LabResult
    {
        return DB::transaction(function () use ($data) {
            $order = LabOrder::where('tenant_id', $this->tenantId)->findOrFail($data['lab_order_id']);
            $test = LabTest::where('tenant_id', $this->tenantId)->findOrFail($data['lab_test_id']);

            $isAbnormal = false;
            $isCritical = false;
            $status = LabResultStatusEnum::PENDING;

            if (isset($data['value']) && $data['value'] !== null) {
                $isAbnormal = $test->isAbnormal($data['value']);
                $isCritical = $test->isCritical($data['value']);

                if ($isCritical) {
                    $status = LabResultStatusEnum::CRITICAL;
                } elseif ($isAbnormal) {
                    $status = LabResultStatusEnum::ABNORMAL;
                } else {
                    $status = LabResultStatusEnum::COMPLETED;
                }
            }

            $data['tenant_id'] = $this->tenantId;
            $result = LabResult::create([
                'tenant_id' => $this->tenantId,
                'lab_order_id' => $data['lab_order_id'],
                'lab_order_test_id' => $data['lab_order_test_id'] ?? null,
                'lab_test_id' => $data['lab_test_id'],
                'value' => $data['value'] ?? null,
                'range_low' => $data['range_low'] ?? $test->min_range,
                'range_high' => $data['range_high'] ?? $test->max_range,
                'unit' => $data['unit'] ?? $test->unit,
                'status' => $status,
                'is_abnormal' => $isAbnormal,
                'is_critical' => $isCritical,
                'comment' => $data['comment'] ?? null,
                'interpretation' => $data['interpretation'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ]);

            if ($isCritical) {
                $this->sendCriticalResultNotification($order, $result);
            }

            $this->updateOrderStatusAfterResult($order);

            return $result->fresh(['labTest', 'files']);
        });
    }

    public function addResults(array $results): array
    {
        $added = [];
        foreach ($results as $resultData) {
            $added[] = $this->addResult($resultData);
        }
        return $added;
    }

    public function verifyResult(int $resultId): LabResult
    {
        $result = LabResult::where('tenant_id', $this->tenantId)->findOrFail($resultId);
        $result->verify();
        return $result->fresh();
    }

    public function deleteResult(int $resultId): void
    {
        $result = LabResult::where('tenant_id', $this->tenantId)->findOrFail($resultId);
        $result->delete();

        $order = $result->labOrder;
        $this->updateOrderStatusAfterResult($order);
    }

    public function createInvoice(LabOrder $order, float $totalPrice): Invoice
    {
        return Invoice::create([
            'tenant_id' => $this->tenantId,
            'patient_id' => $order->patient_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $totalPrice,
            'tax' => $totalPrice * 0.09,
            'discount' => 0,
            'total_amount' => $totalPrice * 1.09,
            'description' => "فاکتور آزمایشگاه - سفارش {$order->order_number}",
            'status' => InvoiceStatusEnum::ISSUED,
            'due_date' => now()->addDays(7),
            'invoicable_type' => LabOrder::class,
            'invoicable_id' => $order->id,
            'items' => $this->generateInvoiceItems($order),
        ]);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'LBR-INV';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
    }

    private function generateInvoiceItems(LabOrder $order): array
    {
        $items = [];
        foreach ($order->orderTests as $test) {
            $items[] = [
                'description' => $test->labTest->name,
                'quantity' => $test->quantity,
                'unit_price' => $test->unit_price,
                'total' => $test->total_price,
            ];
        }
        return $items;
    }

    protected function sendOrderCreatedNotifications(LabOrder $order): void
    {
        if ($order->doctor && $order->doctor->user) {
            $this->notificationService->sendToUser(
                $order->doctor->user_id,
                'سفارش آزمایش جدید',
                "سفارش آزمایش با شماره {$order->order_number} برای بیمار {$order->patient->full_name} ثبت شد.",
                ['order_id' => $order->id, 'order_number' => $order->order_number],
                'lab'
            );
        }

        if ($order->patient && $order->patient->user) {
            $this->notificationService->sendToUser(
                $order->patient->user_id,
                'سفارش آزمایش ثبت شد',
                "سفارش آزمایش شما با شماره {$order->order_number} ثبت شد. لطفاً برای پرداخت و نوبت‌دهی اقدام کنید.",
                ['order_id' => $order->id, 'order_number' => $order->order_number],
                'lab'
            );
        }
    }

    protected function sendStatusUpdateNotifications(LabOrder $order, LabOrderStatusEnum $status): void
    {
        $patient = $order->patient;

        if (!$patient || !$patient->user) {
            return;
        }

        $message = match ($status) {
            LabOrderStatusEnum::PAID => "پرداخت سفارش آزمایش {$order->order_number} با موفقیت انجام شد.",
            LabOrderStatusEnum::SCHEDULED => "نوبت نمونه‌گیری برای سفارش {$order->order_number} تعیین شد.",
            LabOrderStatusEnum::SAMPLE_COLLECTED => "نمونه سفارش {$order->order_number} دریافت شد.",
            LabOrderStatusEnum::PROCESSING => "نمونه سفارش {$order->order_number} در حال پردازش است.",
            LabOrderStatusEnum::COMPLETED => "نتایج سفارش آزمایش {$order->order_number} آماده مشاهده است.",
            LabOrderStatusEnum::CANCELLED => "سفارش آزمایش {$order->order_number} لغو شد.",
            LabOrderStatusEnum::REJECTED => "سفارش آزمایش {$order->order_number} رد شد.",
            default => null,
        };

        if ($message) {
            $this->notificationService->sendToUser(
                $patient->user_id,
                "وضعیت سفارش آزمایش {$order->order_number}",
                $message,
                ['order_id' => $order->id, 'order_number' => $order->order_number],
                'lab'
            );
        }
    }

    protected function sendCriticalResultNotification(LabOrder $order, LabResult $result): void
    {
        if ($order->doctor && $order->doctor->user) {
            $this->notificationService->sendToUser(
                $order->doctor->user_id,
                '⚠️ نتیجه بحرانی آزمایش',
                "نتیجه بحرانی برای تست {$result->labTest->name} در سفارش {$order->order_number} بیمار {$order->patient->full_name} ثبت شده است.",
                [
                    'order_id' => $order->id,
                    'result_id' => $result->id,
                    'test_name' => $result->labTest->name,
                    'value' => $result->value,
                ],
                'critical',
                'urgent'
            );
        }

        if ($order->patient && $order->patient->user) {
            $this->notificationService->sendToUser(
                $order->patient->user_id,
                '⚠️ نتیجه آزمایش بحرانی',
                "نتیجه بحرانی برای یکی از تست‌های سفارش {$order->order_number} ثبت شده است. لطفاً با پزشک خود تماس بگیرید.",
                ['order_id' => $order->id],
                'critical',
                'urgent'
            );
        }
    }

    protected function updateOrderStatusAfterResult(LabOrder $order): void
    {
        $totalTests = $order->orderTests()->count();
        $completedResults = $order->results()
            ->whereIn('status', [
                LabResultStatusEnum::COMPLETED,
                LabResultStatusEnum::ABNORMAL,
                LabResultStatusEnum::CRITICAL,
            ])
            ->count();

        if ($totalTests == 0) {
            return;
        }

        $newStatus = LabOrderStatusEnum::PROCESSING;

        if ($completedResults == $totalTests) {
            $newStatus = LabOrderStatusEnum::COMPLETED;
        } elseif ($completedResults > 0) {
            $newStatus = LabOrderStatusEnum::PARTIAL;
        }

        if ($order->status !== $newStatus) {
            $order->update(['status' => $newStatus]);
            $this->sendStatusUpdateNotifications($order, $newStatus);
        }
    }

    public function getStats(array $filters = []): array
    {
        $query = LabOrder::where('tenant_id', $this->tenantId);

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        return [
            'total_orders' => $query->count(),
            'active_orders' => (clone $query)->active()->count(),
            'completed_orders' => (clone $query)->completed()->count(),
            'pending_orders' => (clone $query)->pending()->count(),
            'cancelled_orders' => (clone $query)->where('status', LabOrderStatusEnum::CANCELLED)->count(),
            'rejected_orders' => (clone $query)->where('status', LabOrderStatusEnum::REJECTED)->count(),
            'critical_results' => LabResult::where('tenant_id', $this->tenantId)->critical()->count(),
            'abnormal_results' => LabResult::where('tenant_id', $this->tenantId)->abnormal()->count(),
            'total_revenue' => (clone $query)->completed()->sum('total_price'),
        ];
    }

    public function getCategories(array $filters = [], int $perPage = 20)
    {
        $query = LabCategory::where('tenant_id', $this->tenantId)
            ->withCount('tests');

        if (isset($filters['search'])) {
            $query->where('name', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('order')->orderBy('name')->paginate($perPage);
    }

    public function getTests(array $filters = [], int $perPage = 20)
    {
        $query = LabTest::where('tenant_id', $this->tenantId)
            ->with(['category']);

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getActiveTests()
    {
        return LabTest::where('tenant_id', $this->tenantId)
            ->active()
            ->with(['category'])
            ->orderBy('name')
            ->get();
    }

    public function createCategory(array $data): LabCategory
    {
        $data['tenant_id'] = $this->tenantId;
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
}
