<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Illuminate\Http\Request;

class PaymentService
{
    protected PaymentManager $paymentManager;

    // لیست درگاه‌های فعال (دستی)
    protected array $activeGateways = [
        'local',
        'zarinpal',
        // 'asanpardakht',
        // 'behpardakht',
        // 'paypal',
        // 'idpay',
        // 'payir',
        // 'zibal',
        // 'nextpay',
        // 'sadad',
        // 'parsian',
        // 'pasargad',
        // 'saman',
        // 'payping',
        // 'vandar',
    ];

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * دریافت لیست درگاه‌های موجود (فقط درگاه‌های فعال)
     */
    public function getAvailableGateways(): array
    {
        $allGateways = $this->paymentManager->getAvailableGateways();
        
        // فیلتر کردن بر اساس لیست فعال
        return array_filter($allGateways, function ($gateway) {
            return in_array($gateway, $this->activeGateways);
        });
    }

    /**
     * دریافت درگاه پیش‌فرض
     */
    public function getDefaultGateway(): string
    {
        // اگر درگاه پیش‌فرض در لیست فعال نیست، اولین درگاه فعال را برگردان
        $default = $this->paymentManager->getDefaultGateway();
        
        if (in_array($default, $this->activeGateways)) {
            return $default;
        }
        
        // برگرداندن اولین درگاه فعال
        $activeGateways = $this->getAvailableGateways();
        return !empty($activeGateways) ? $activeGateways[0] : 'local';
    }

    /**
     * تنظیم درگاه‌های فعال (برای استفاده در صورت نیاز)
     */
    public function setActiveGateways(array $gateways): void
    {
        $this->activeGateways = $gateways;
    }

    /**
     * اضافه کردن درگاه به لیست فعال
     */
    public function addActiveGateway(string $gateway): void
    {
        if (!in_array($gateway, $this->activeGateways)) {
            $this->activeGateways[] = $gateway;
        }
    }

    /**
     * حذف درگاه از لیست فعال
     */
    public function removeActiveGateway(string $gateway): void
    {
        $this->activeGateways = array_filter($this->activeGateways, function ($g) use ($gateway) {
            return $g !== $gateway;
        });
    }

    public function initiatePayment(Invoice $invoice, ?string $gateway = null): array
    {
        $gateway = $gateway ?? $this->getDefaultGateway();

        // بررسی اینکه درگاه در لیست فعال باشد
        if (!in_array($gateway, $this->activeGateways)) {
            throw new \Exception("درگاه {$gateway} فعال نیست");
        }

        if (!$this->paymentManager->isGatewayAvailable($gateway)) {
            throw new \Exception("درگاه {$gateway} در دسترس نیست");
        }

        if ($invoice->is_paid) {
            throw new \Exception('این فاکتور قبلاً پرداخت شده است');
        }

        return $this->paymentManager->initiate($gateway, $invoice);
    }

    public function verifyPayment(string $gateway, Request $request): array
    {
        return $this->paymentManager->verify($gateway, $request);
    }

    public function getPaymentStatus(Invoice $invoice): array
    {
        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'total_amount' => $invoice->total_amount,
            'paid_amount' => $invoice->total_paid,
            'remaining_amount' => $invoice->remaining_amount,
            'is_paid' => $invoice->is_paid,
            'payments' => $invoice->payments()->success()->get(),
        ];
    }

    public function refundPayment(Payment $payment): array
    {
        if (!$payment->is_successful) {
            throw new \Exception('فقط پرداخت‌های موفق قابل عودت هستند');
        }

        $payment->update([
            'status' => PaymentStatusEnum::REFUNDED,
            'message' => 'عودت وجه انجام شد',
        ]);

        return [
            'success' => true,
            'message' => 'عودت وجه با موفقیت انجام شد',
            'payment' => $payment,
        ];
    }

    public function getPaymentHistory(int $patientId, int $perPage = 15)
    {
        return Payment::where('patient_id', $patientId)
            ->with(['invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
