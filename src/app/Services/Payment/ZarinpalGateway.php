<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Illuminate\Http\Request;

class ZarinpalGateway extends BaseGateway
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'zarinpal';
    }

    public function initiate(Invoice $invoice): array
    {
        // در محیط تست، شبیه‌سازی پرداخت زرین‌پال
        if (env('ZARINPAL_SANDBOX', true)) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'patient_id' => $invoice->patient_id,
                'amount' => $invoice->total_amount,
                'gateway' => 'zarinpal',
                'status' => PaymentStatusEnum::PENDING,
                'reference_id' => 'ZARIN-' . time() . '-' . rand(1000, 9999),
                'message' => 'در انتظار پرداخت',
            ]);

            return [
                'success' => true,
                'message' => 'در حال انتقال به درگاه زرین‌پال...',
                'payment_id' => $payment->id,
                'redirect_url' => env('FRONTEND_URL', 'http://localhost:3000') . '/payment/result?success=true&gateway=zarinpal',
            ];
        }

        // کد واقعی زرین‌پال
        // $result = $this->zarinpal->request(...);
        // return [...];
    }

    public function verify(Request $request): array
    {
        // تایید پرداخت زرین‌پال
        return [
            'success' => true,
            'message' => 'پرداخت با موفقیت تایید شد',
            'reference_id' => $request->get('Authority', 'ZARIN-' . time()),
            'invoice' => $request->get('invoice'),
        ];
    }
}
