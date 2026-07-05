<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use App\Traits\ApiResponse;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * دریافت لیست درگاه‌های موجود
     */
    public function gateways()
    {
        $availableGateways = $this->paymentService->getAvailableGateways();
        
        // اطلاعات کامل هر درگاه
        $gateways = [];
        foreach ($availableGateways as $name) {
            $gateways[] = [
                'name' => $name,
                'title' => $this->getGatewayTitle($name),
                'icon' => $this->getGatewayIcon($name),
                'is_default' => $name === $this->paymentService->getDefaultGateway(),
            ];
        }

        return $this->success([
            'available' => $gateways,
            'default' => $this->paymentService->getDefaultGateway(),
        ]);
    }

    /**
     * شروع پرداخت
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'gateway' => 'nullable|string',
        ]);

        try {
            $invoice = Invoice::findOrFail($request->invoice_id);

            $user = auth()->user();
            if (!$user->isAdmin() && $invoice->patient->user_id != $user->id) {
                return $this->error('شما دسترسی به این فاکتور ندارید', 403);
            }

            $gateway = $request->gateway ?? $this->paymentService->getDefaultGateway();
            $result = $this->paymentService->initiatePayment($invoice, $gateway);

            if ($result['success']) {
                return $this->success($result, 'در حال انتقال به درگاه پرداخت...');
            }

            return $this->error($result['message'], 400);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Callback درگاه‌های پرداخت
     */
    public function callback(Request $request, $gateway)
    {
        try {
            $result = $this->paymentService->verifyPayment($gateway, $request);

            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

            $params = [
                'success' => $result['success'] ? 'true' : 'false',
                'message' => $result['message'] ?? '',
                'invoice_id' => $result['invoice']->id ?? '',
                'invoice_number' => $result['invoice']->invoice_number ?? '',
                'gateway' => $gateway,
            ];

            if ($result['success'] && isset($result['reference_id'])) {
                $params['ref_id'] = $result['reference_id'];
            }

            return redirect($frontendUrl . '/payment/result?' . http_build_query($params));

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * وضعیت پرداخت
     */
    public function status($invoiceId)
    {
        try {
            $invoice = Invoice::findOrFail($invoiceId);

            $user = auth()->user();
            if (!$user->isAdmin() && $invoice->patient->user_id != $user->id) {
                return $this->error('شما دسترسی به این فاکتور ندارید', 403);
            }

            $status = $this->paymentService->getPaymentStatus($invoice);
            return $this->success($status);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * تاریخچه پرداخت‌ها
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $patient = \App\Models\Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $payments = $this->paymentService->getPaymentHistory($patient->id, $request->get('per_page', 15));
        return $this->success($payments);
    }

    /**
     * عودت وجه
     */
    public function refund($paymentId)
    {
        try {
            $payment = Payment::findOrFail($paymentId);

            $user = auth()->user();
            if (!$user->isAdmin() && $payment->patient->user_id != $user->id) {
                return $this->error('شما دسترسی به این پرداخت ندارید', 403);
            }

            $result = $this->paymentService->refundPayment($payment);
            return $this->success($result, 'عودت وجه با موفقیت انجام شد');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت عنوان درگاه
     */
    private function getGatewayTitle(string $name): string
    {
        $titles = [
            'local' => 'درگاه تست (آفلاین)',
            'zarinpal' => 'زرین‌پال',
            'asanpardakht' => 'آسان پرداخت',
            'behpardakht' => 'به پرداخت (ملی)',
            'paypal' => 'پی‌پال',
            'stripe' => 'استرایپ',
            'idpay' => 'آیدی پی',
            'payir' => 'پی‌آی‌آر',
            'zibal' => 'زیبال',
            'nextpay' => 'نکست پی',
            'sadad' => 'سداد',
            'parsian' => 'پارسیان',
            'pasargad' => 'پاسارگاد',
            'saman' => 'سامان',
            'payping' => 'پی‌پینگ',
            'vandar' => 'وندر',
        ];

        return $titles[$name] ?? ucfirst($name);
    }

    /**
     * دریافت آیکون درگاه
     */
    private function getGatewayIcon(string $name): string
    {
        $icons = [
            'local' => '🔄',
            'zarinpal' => '🟡',
            'asanpardakht' => '🟣',
            'behpardakht' => '🔵',
            'paypal' => '🔷',
            'stripe' => '⚡',
            'idpay' => '🟠',
            'payir' => '🟢',
            'zibal' => '🔶',
            'nextpay' => '🟣',
            'sadad' => '🔷',
            'parsian' => '🟩',
            'pasargad' => '🟨',
            'saman' => '🟦',
            'payping' => '🟪',
            'vandar' => '🟥',
        ];

        return $icons[$name] ?? '💳';
    }
}
