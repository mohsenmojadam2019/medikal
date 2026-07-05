<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Appointment;
use App\Enums\PaymentStatusEnum;
use Illuminate\Http\Request;

class PaymentService
{
    protected PaymentManager $paymentManager;

    protected array $activeGateways = [
        'local',
        'zarinpal',
    ];

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function getAvailableGateways(): array
    {
        $allGateways = $this->paymentManager->getAvailableGateways();
        return array_filter($allGateways, function ($gateway) {
            return in_array($gateway, $this->activeGateways);
        });
    }

    public function getDefaultGateway(): string
    {
        $default = $this->paymentManager->getDefaultGateway();
        if (in_array($default, $this->activeGateways)) {
            return $default;
        }
        $activeGateways = $this->getAvailableGateways();
        return !empty($activeGateways) ? $activeGateways[0] : 'local';
    }

    public function initiatePayment(Invoice $invoice, ?string $gateway = null): array
    {
        $gateway = $gateway ?? $this->getDefaultGateway();

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
        $result = $this->paymentManager->verify($gateway, $request);

        // ✅ اگر پرداخت موفق بود، وضعیت نوبت را آپدیت کن
        if ($result['success'] && isset($result['invoice'])) {
            $invoice = $result['invoice'];
            $appointment = Appointment::where('id', $invoice->appointment_id)->first();
            
            if ($appointment && $appointment->status === 'pending') {
                $appointment->status = 'confirmed';
                $appointment->save();
                
                \Log::info('✅ Appointment confirmed after payment', [
                    'appointment_id' => $appointment->id,
                    'invoice_id' => $invoice->id,
                ]);
            }
        }

        return $result;
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
