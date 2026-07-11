<?php

namespace App\Services\Pharmacy;

use App\Models\PharmacyOrder;
use App\Models\PharmacyOrderItem;
use App\Models\PharmacyNotification;
use App\Enums\PharmacyOrderStatusEnum;
use App\Enums\PharmacyOrderPaymentStatusEnum;
use App\Models\Drug;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PharmacyOrderService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function createOrder(array $data): PharmacyOrder
    {
        return DB::transaction(function () use ($data) {
            $data['tenant_id'] = $this->tenantId;
            
            // ✅ اگر pharmacy_id وجود ندارد، از اولین داروخانه استفاده کن
            $pharmacyId = $data['pharmacy_id'] ?? 1;
            
            // ✅ اگر prescription_id وجود ندارد، null بگذار
            $prescriptionId = $data['prescription_id'] ?? null;
            
            $order = PharmacyOrder::create([
                'tenant_id' => $this->tenantId,
                'patient_id' => $data['patient_id'],
                'pharmacy_id' => $pharmacyId,
                'prescription_id' => $prescriptionId,
                'status' => PharmacyOrderStatusEnum::PENDING,
                'payment_status' => PharmacyOrderPaymentStatusEnum::PENDING,
                'notes' => $data['delivery_notes'] ?? $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            $availableItems = [];
            $unavailableItems = [];

            foreach ($data['items'] as $item) {
                $drug = Drug::where('tenant_id', $this->tenantId)->find($item['drug_id']);
                
                if (!$drug) {
                    continue;
                }
                
                $isAvailable = $drug->stock >= $item['quantity'];
                $price = $drug->price;

                if ($isAvailable) {
                    $totalPrice = $price * $item['quantity'];

                    PharmacyOrderItem::create([
                        'tenant_id' => $this->tenantId,
                        'order_id' => $order->id,
                        'drug_id' => $item['drug_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $price,
                        'total_price' => $totalPrice,
                        'is_available' => true,
                    ]);

                    // کاهش موجودی
                    $drug->decreaseStock($item['quantity']);

                    $subtotal += $totalPrice;
                    $availableItems[] = [
                        'drug_name' => $drug->name,
                        'quantity' => $item['quantity'],
                        'price' => $price,
                        'total' => $totalPrice,
                    ];
                } else {
                    PharmacyOrderItem::create([
                        'tenant_id' => $this->tenantId,
                        'order_id' => $order->id,
                        'drug_id' => $item['drug_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => 0,
                        'total_price' => 0,
                        'is_available' => false,
                        'unavailable_reason' => 'موجود نیست',
                    ]);

                    $unavailableItems[] = [
                        'drug_name' => $drug->name,
                        'quantity' => $item['quantity'],
                    ];
                }
            }

            $order->subtotal = $subtotal;
            $order->total_amount = $subtotal;
            $order->available_items = $availableItems;
            $order->unavailable_items = $unavailableItems;

            if (count($unavailableItems) == 0 && count($availableItems) > 0) {
                $order->status = PharmacyOrderStatusEnum::ALL_AVAILABLE;
            } elseif (count($availableItems) == 0) {
                $order->status = PharmacyOrderStatusEnum::CANCELLED;
                $order->cancelled_at = now();
                $order->notes = 'هیچ دارویی موجود نیست';
            } else {
                $order->status = PharmacyOrderStatusEnum::PARTIAL_AVAILABLE;
            }

            if ($order->status === PharmacyOrderStatusEnum::CANCELLED) {
                $order->save();
                return $order;
            }

            $order->save();

            if ($order->total_amount > 0) {
                $order->status = PharmacyOrderStatusEnum::PAYMENT_PENDING;
                $order->save();

                $paymentLink = route('pharmacy.payment.callback', ['gateway' => 'local']) . '?' . http_build_query([
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'gateway' => 'local',
                    'transactionId' => 'LOCAL_' . $order->id . '_' . time(),
                ]);
                $order->payment_link = $paymentLink;
                $order->save();
            }

            $this->sendOrderNotification($order);

            return $order->fresh(['items', 'items.drug']);
        });
    }

    public function initiatePayment(PharmacyOrder $order, string $gateway = 'local'): array
    {
        if ($order->is_paid) {
            throw new \Exception('این سفارش قبلاً پرداخت شده است');
        }

        if ($order->total_amount <= 0) {
            throw new \Exception('مبلغ سفارش صفر است');
        }

        if ($gateway === 'local') {
            $transactionId = 'LOCAL_' . $order->id . '_' . time();

            $order->update([
                'payment_gateway' => 'local',
                'payment_authority' => $transactionId,
                'payment_link' => route('pharmacy.payment.callback', ['gateway' => 'local']) . '?' . http_build_query([
                    'order_number' => $order->order_number,
                    'amount' => $order->total_amount,
                    'gateway' => 'local',
                    'transactionId' => $transactionId,
                    'success' => 'true',
                ]),
            ]);

            return [
                'success' => true,
                'redirect_url' => $order->payment_link,
                'order_number' => $order->order_number,
                'amount' => $order->total_amount,
                'gateway' => 'local',
                'transactionId' => $transactionId,
                'message' => 'لینک پرداخت تست ساخته شد. برای تکمیل پرداخت روی لینک کلیک کنید.',
            ];
        }

        throw new \Exception("درگاه {$gateway} فعلاً پشتیبانی نمی‌شود");
    }

    public function verifyPayment(string $gateway, array $data): array
    {
        $transactionId = $data['transactionId'] ?? $data['transaction_id'] ?? null;
        $orderNumber = $data['order_number'] ?? null;

        if (!$transactionId) {
            return [
                'success' => false,
                'message' => 'شناسه تراکنش یافت نشد',
            ];
        }

        $order = PharmacyOrder::where('tenant_id', $this->tenantId)
            ->where('payment_authority', $transactionId)
            ->orWhere('order_number', $orderNumber)
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

        $order->update([
            'payment_status' => PharmacyOrderPaymentStatusEnum::PAID,
            'status' => PharmacyOrderStatusEnum::PAID,
            'paid_at' => now(),
        ]);

        PharmacyNotification::create([
            'tenant_id' => $this->tenantId,
            'patient_id' => $order->patient_id,
            'order_id' => $order->id,
            'type' => 'panel',
            'title' => 'پرداخت با موفقیت انجام شد',
            'message' => "سفارش {$order->order_number} با موفقیت پرداخت شد. داروها در حال آماده‌سازی هستند.",
            'data' => ['order_id' => $order->id, 'transactionId' => $transactionId],
            'is_read' => false,
            'sent_at' => now(),
        ]);

        return [
            'success' => true,
            'reference_id' => $transactionId,
            'order' => $order,
            'message' => 'پرداخت با موفقیت انجام شد',
        ];
    }

    private function sendOrderNotification(PharmacyOrder $order): void
    {
        try {
            PharmacyNotification::create([
                'tenant_id' => $this->tenantId,
                'patient_id' => $order->patient_id,
                'order_id' => $order->id,
                'type' => 'panel',
                'title' => 'وضعیت سفارش دارو',
                'message' => $this->getNotificationMessage($order),
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status->value,
                    'available_count' => count($order->available_items ?? []),
                    'unavailable_count' => count($order->unavailable_items ?? []),
                    'total_amount' => $order->total_amount,
                ],
                'is_read' => false,
                'sent_at' => now(),
            ]);

            Log::info('Order notification sent', [
                'tenant_id' => $this->tenantId,
                'order_id' => $order->id,
                'patient_id' => $order->patient_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send order notification', [
                'tenant_id' => $this->tenantId,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getNotificationMessage(PharmacyOrder $order): string
    {
        $message = "سفارش شما بررسی شد.\n\n";

        if (count($order->available_items ?? []) > 0) {
            $message .= "🟢 داروهای موجود:\n";
            foreach ($order->available_items as $item) {
                $message .= "✅ {$item['drug_name']} - {$item['quantity']} عدد - {$item['total']} تومان\n";
            }
        }

        if (count($order->unavailable_items ?? []) > 0) {
            $message .= "\n🔴 داروهای ناموجود:\n";
            foreach ($order->unavailable_items as $item) {
                $message .= "❌ {$item['drug_name']} - {$item['quantity']} عدد\n";
            }
            $message .= "\n📌 لطفاً داروهای ناموجود را از جای دیگر تهیه کنید.";
        }

        if ($order->total_amount > 0) {
            $message .= "\n\n💰 مبلغ قابل پرداخت: " . number_format($order->total_amount) . " تومان";
            if ($order->payment_link) {
                $message .= "\n🔗 لینک پرداخت: {$order->payment_link}";
            }
        }

        return $message;
    }

    public function getOrderStatus(PharmacyOrder $order): array
    {
        return [
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'status_label' => $order->status_label,
            'status_color' => $order->status_color,
            'payment_status' => $order->payment_status->value,
            'payment_status_label' => $order->payment_status_label,
            'total_amount' => $order->total_amount,
            'is_paid' => $order->is_paid,
            'available_items' => $order->available_items,
            'unavailable_items' => $order->unavailable_items,
            'payment_link' => $order->payment_link,
            'created_at' => $order->created_at,
        ];
    }

    public function confirmPayment(PharmacyOrder $order, string $referenceCode = null): PharmacyOrder
    {
        $order->update([
            'payment_status' => PharmacyOrderPaymentStatusEnum::PAID,
            'status' => PharmacyOrderStatusEnum::PAID,
            'paid_at' => now(),
        ]);

        return $order->fresh();
    }

    public function getPatientOrders(int $patientId, int $perPage = 15)
    {
        return PharmacyOrder::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['items', 'items.drug'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getPharmacyOrders(int $pharmacyId, int $perPage = 15)
    {
        return PharmacyOrder::where('tenant_id', $this->tenantId)
            ->where('pharmacy_id', $pharmacyId)
            ->with(['patient.user', 'items', 'items.drug'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
