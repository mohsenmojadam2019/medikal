<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PaymentService
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    public function getAvailableGateways(): array
    {
        return $this->paymentManager->getAvailableGateways();
    }

    public function getDefaultGateway(): string
    {
        return $this->paymentManager->getDefaultGateway();
    }

    public function initiatePayment(Invoice $invoice, string $gateway = null): array
    {
        $gateway = $gateway ?? $this->getDefaultGateway();

        if (!in_array($gateway, $this->getAvailableGateways())) {
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
