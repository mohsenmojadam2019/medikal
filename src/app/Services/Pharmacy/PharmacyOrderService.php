<?php

namespace App\Services\Pharmacy;

use App\Models\PharmacyOrder;
use App\Models\PharmacyOrderItem;
use App\Models\PharmacyNotification;
use App\Models\Drug;
use App\Models\Pharmacy;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PharmacyOrderService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    /**
     * تولید شماره سفارش منحصر به فرد
     */
    public function generateOrderNumber(): string
    {
        $prefix = 'PH';
        $date = now()->format('ymd');
        $random = rand(1000, 9999);

        $orderNumber = $prefix . '-' . $date . '-' . $random;

        // اطمینان از منحصر به فرد بودن
        while (PharmacyOrder::where('order_number', $orderNumber)->exists()) {
            $random = rand(1000, 9999);
            $orderNumber = $prefix . '-' . $date . '-' . $random;
        }

        return $orderNumber;
    }

    /**
     * ایجاد سفارش جدید
     */
    public function createOrder(array $data)
    {
        try {
            DB::beginTransaction();

            // تنظیم pharmacy_id اگر وجود نداشت
            if (!isset($data['pharmacy_id']) || empty($data['pharmacy_id'])) {
                $pharmacy = Pharmacy::active()->online()->first();
                if ($pharmacy) {
                    $data['pharmacy_id'] = $pharmacy->id;
                } else {
                    $data['pharmacy_id'] = 1;
                }
            }

            // تولید شماره سفارش
            $data['order_number'] = $this->generateOrderNumber();

            // تنظیم status پیش‌فرض
            $data['status'] = 'payment_pending';
            $data['payment_status'] = 'pending';

            // اطمینان از وجود اطلاعات تحویل
            if (!isset($data['recipient_name']) || empty($data['recipient_name'])) {
                $data['recipient_name'] = auth()->user()->name ?? 'نامشخص';
            }
            if (!isset($data['recipient_phone']) || empty($data['recipient_phone'])) {
                $data['recipient_phone'] = auth()->user()->mobile ?? 'نامشخص';
            }

            // محاسبه قیمت‌ها
            $subtotal = 0;
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as &$item) {
                    if (!isset($item['price']) || $item['price'] == 0) {
                        $drug = Drug::find($item['drug_id']);
                        if ($drug) {
                            $item['price'] = $drug->price;
                            $item['name'] = $drug->generic_name ?? $drug->name;
                        }
                    }
                    $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                }
            }

            $data['subtotal'] = $subtotal;
            $data['total_amount'] = $subtotal + ($data['delivery_fee'] ?? 0) + ($data['tax'] ?? 0);

            Log::info('📦 Creating order', [
                'patient_id' => $data['patient_id'],
                'pharmacy_id' => $data['pharmacy_id'],
                'order_number' => $data['order_number'],
                'total_amount' => $data['total_amount']
            ]);

            // ایجاد سفارش
            $order = PharmacyOrder::create($data);

            // ایجاد آیتم‌های سفارش
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $orderItem = $order->items()->create([
                        'order_id' => $order->id,
                        'drug_id' => $item['drug_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'] ?? 0,
                        'total_price' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                        'is_available' => true,
                    ]);

                    // کاهش موجودی دارو
                    $drug = Drug::find($item['drug_id']);
                    if ($drug) {
                        $drug->decrement('stock', $item['quantity']);
                    }

                    Log::info('✅ Order item created', [
                        'order_id' => $order->id,
                        'drug_id' => $item['drug_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'] ?? 0
                    ]);
                }
            }

            DB::commit();

            // ارسال نوتیفیکیشن
            $this->sendOrderNotification($order);

            Log::info('✅ Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'patient_id' => $order->patient_id,
                'total_amount' => $order->total_amount
            ]);

            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Order creation failed: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * شروع پرداخت
     */
    public function initiatePayment(PharmacyOrder $order, string $gateway = 'local'): array
    {
        try {
            if ($order->is_paid) {
                throw new \Exception('این سفارش قبلاً پرداخت شده است');
            }

            if ($order->total_amount <= 0) {
                throw new \Exception('مبلغ سفارش صفر است');
            }

            // ============================================================
            // ✅ تولید لینک پرداخت تست (local)
            // ============================================================
            $transactionId = 'LOCAL_' . $order->id . '_' . time();

            // ✅ ساخت لینک پرداخت با پارامترهای درست
            $baseUrl = config('app.url', 'http://localhost:8210');
            $callbackUrl = $baseUrl . '/api/pharmacy/payment/callback';

            $paymentLink = $callbackUrl . '?' . http_build_query([
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'gateway' => 'local',
                    'transactionId' => $transactionId,
                    'success' => 'true',
                ]);

            Log::info('💳 Payment link created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_link' => $paymentLink
            ]);

            // ✅ ذخیره لینک در دیتابیس
            $order->update([
                'payment_gateway' => 'local',
                'payment_authority' => $transactionId,
                'payment_link' => $paymentLink,
            ]);

            return [
                'success' => true,
                'redirect_url' => $paymentLink,
                'payment_link' => $paymentLink,
                'order_number' => $order->order_number,
                'amount' => $order->total_amount,
                'gateway' => 'local',
                'transactionId' => $transactionId,
                'message' => 'لینک پرداخت ساخته شد',
            ];

        } catch (\Exception $e) {
            Log::error('❌ Payment initiation failed: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
                'gateway' => $gateway
            ]);
            throw $e;
        }
    }
    /**
     * تایید پرداخت
     */
    public function verifyPayment(string $gateway, array $data): array
    {
        try {
            $transactionId = $data['transactionId'] ?? $data['transaction_id'] ?? null;
            $orderNumber = $data['order_number'] ?? null;

            if (!$transactionId) {
                return [
                    'success' => false,
                    'message' => 'شناسه تراکنش یافت نشد',
                ];
            }

            $order = PharmacyOrder::where('tenant_id', $this->tenantId)
                ->where(function ($query) use ($transactionId, $orderNumber) {
                    $query->where('payment_authority', $transactionId)
                        ->orWhere('order_number', $orderNumber);
                })
                ->first();

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد',
                    'transactionId' => $transactionId,
                ];
            }

            if ($order->is_paid) {
                return [
                    'success' => false,
                    'message' => 'این سفارش قبلاً پرداخت شده است',
                ];
            }

            // ✅ آپدیت کامل
            $order->update([
                'payment_status' => 'paid',
                'status' => 'paid',  // ✅ این مهمه!
                'paid_at' => now(),
            ]);

            // ارسال نوتیفیکیشن پرداخت
            $this->sendPaymentNotification($order, $transactionId);

            return [
                'success' => true,
                'reference_id' => $transactionId,
                'order' => $order,
                'message' => 'پرداخت با موفقیت انجام شد',
            ];

        } catch (\Exception $e) {
            Log::error('❌ Payment verification failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * دریافت وضعیت سفارش
     */
    public function getOrderStatus(PharmacyOrder $order): array
    {
        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'status_color' => $order->status_color,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $order->payment_status_label,
            'total_amount' => $order->total_amount,
            'is_paid' => $order->is_paid,
            'available_items' => $order->available_items,
            'unavailable_items' => $order->unavailable_items,
            'payment_link' => $order->payment_link,
            'created_at' => $order->created_at,
            'recipient_name' => $order->recipient_name,
            'recipient_phone' => $order->recipient_phone,
            'delivery_address' => $order->delivery_address,
            'delivery_notes' => $order->delivery_notes,
        ];
    }

    /**
     * دریافت سفارشات بیمار
     */
    public function getPatientOrders(int $patientId, int $perPage = 15)
    {
        return PharmacyOrder::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['items', 'items.drug'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * دریافت سفارشات داروخانه
     */
    public function getPharmacyOrders(int $pharmacyId, int $perPage = 15)
    {
        return PharmacyOrder::where('tenant_id', $this->tenantId)
            ->where('pharmacy_id', $pharmacyId)
            ->with(['items', 'items.drug', 'patient'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * دریافت سفارش با جزئیات کامل
     */
    public function getOrderWithDetails($orderId)
    {
        return PharmacyOrder::with([
            'items',
            'items.drug',
            'pharmacy',
            'patient'
        ])->findOrFail($orderId);
    }

    /**
     * ارسال نوتیفیکیشن سفارش
     */
    private function sendOrderNotification(PharmacyOrder $order): void
    {
        try {
            PharmacyNotification::create([
                'tenant_id' => $this->tenantId,
                'patient_id' => $order->patient_id,
                'order_id' => $order->id,
                'type' => 'panel',
                'title' => 'ثبت سفارش جدید',
                'message' => "سفارش {$order->order_number} با موفقیت ثبت شد. در انتظار پرداخت.",
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                ],
                'is_read' => false,
                'sent_at' => now(),
            ]);

            Log::info('📨 Order notification sent', [
                'tenant_id' => $this->tenantId,
                'order_id' => $order->id,
                'patient_id' => $order->patient_id,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to send order notification', [
                'tenant_id' => $this->tenantId,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ارسال نوتیفیکیشن پرداخت
     */
    private function sendPaymentNotification(PharmacyOrder $order, string $transactionId): void
    {
        try {
            PharmacyNotification::create([
                'tenant_id' => $this->tenantId,
                'patient_id' => $order->patient_id,
                'order_id' => $order->id,
                'type' => 'panel',
                'title' => 'پرداخت با موفقیت انجام شد',
                'message' => "سفارش {$order->order_number} با موفقیت پرداخت شد. داروها در حال آماده‌سازی هستند.",
                'data' => [
                    'order_id' => $order->id,
                    'transactionId' => $transactionId,
                ],
                'is_read' => false,
                'sent_at' => now(),
            ]);

            Log::info('📨 Payment notification sent', [
                'tenant_id' => $this->tenantId,
                'order_id' => $order->id,
                'transactionId' => $transactionId,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to send payment notification', [
                'tenant_id' => $this->tenantId,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * لغو سفارش
     */
    public function cancelOrder(PharmacyOrder $order, string $reason = null): bool
    {
        try {
            if ($order->is_paid) {
                throw new \Exception('سفارش پرداخت شده قابل لغو نیست');
            }

            if ($order->status === 'delivered') {
                throw new \Exception('سفارش تحویل شده قابل لغو نیست');
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'notes' => $reason ?? $order->notes,
            ]);

            Log::info('✅ Order cancelled', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Order cancellation failed: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null
            ]);
            throw $e;
        }
    }

    /**
     * به‌روزرسانی وضعیت سفارش
     */
    public function updateOrderStatus(PharmacyOrder $order, string $status, string $notes = null): bool
    {
        try {
            $validStatuses = ['pending', 'payment_pending', 'paid', 'processing', 'preparing', 'ready', 'shipped', 'delivered', 'cancelled'];

            if (!in_array($status, $validStatuses)) {
                throw new \Exception("وضعیت {$status} معتبر نیست");
            }

            $order->update([
                'status' => $status,
                'notes' => $notes ?? $order->notes,
            ]);

            Log::info('✅ Order status updated', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'new_status' => $status
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Order status update failed: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
                'status' => $status
            ]);
            throw $e;
        }
    }

    /**
     * دریافت آمار سفارشات بیمار
     */
    public function getPatientOrderStats(int $patientId): array
    {
        return [
            'total' => PharmacyOrder::where('patient_id', $patientId)->count(),
            'pending' => PharmacyOrder::where('patient_id', $patientId)->where('status', 'pending')->count(),
            'payment_pending' => PharmacyOrder::where('patient_id', $patientId)->where('status', 'payment_pending')->count(),
            'processing' => PharmacyOrder::where('patient_id', $patientId)->where('status', 'processing')->count(),
            'delivered' => PharmacyOrder::where('patient_id', $patientId)->where('status', 'delivered')->count(),
            'cancelled' => PharmacyOrder::where('patient_id', $patientId)->where('status', 'cancelled')->count(),
            'total_spent' => PharmacyOrder::where('patient_id', $patientId)->where('payment_status', 'paid')->sum('total_amount'),
        ];
    }
}
