<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use App\Traits\ApiResponse;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    use ApiResponse;

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function gateways()
    {
        $availableGateways = $this->paymentService->getAvailableGateways();
        
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

    public function initiate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'gateway' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
            'discount_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $invoice = Invoice::with(['patient'])->find($request->invoice_id);
            
            if (!$invoice) {
                return $this->error('فاکتور یافت نشد', 404);
            }

            $user = auth()->user();
            if (!$user) {
                return $this->error('لطفاً وارد شوید', 401);
            }

            if (!$user->isAdmin() && $invoice->patient->user_id != $user->id) {
                return $this->error('شما دسترسی به این فاکتور ندارید', 403);
            }

            if ($invoice->is_paid) {
                return $this->error('این فاکتور قبلاً پرداخت شده است', 400);
            }

            $gateway = $request->gateway ?? $this->paymentService->getDefaultGateway();
            
            $availableGateways = $this->paymentService->getAvailableGateways();
            if (!in_array($gateway, $availableGateways)) {
                return $this->error("درگاه {$gateway} در دسترس نیست", 400);
            }

            $result = $this->paymentService->initiatePayment($invoice, $gateway);

            if ($result['success']) {
                return $this->success($result, 'در حال انتقال به درگاه پرداخت...');
            }

            return $this->error($result['message'] ?? 'خطا در شروع پرداخت', 400);

        } catch (\Exception $e) {
            \Log::error('Payment initiation error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return $this->error('خطا در شروع پرداخت: ' . $e->getMessage(), 400);
        }
    }

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

            if ($result['success'] && isset($result['transaction_id'])) {
                $params['transaction_id'] = $result['transaction_id'];
            }

            return redirect($frontendUrl . '/payment/result?' . http_build_query($params));

        } catch (\Exception $e) {
            \Log::error('Payment callback error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

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

    public function history(Request $request)
    {
        $user = auth()->user();
        $patient = \App\Models\Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $payments = Payment::where('patient_id', $patient->id)
            ->with(['invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));
            
        return $this->success($payments);
    }

    public function refund($paymentId)
    {
        try {
            $payment = Payment::findOrFail($paymentId);

            $user = auth()->user();
            if (!$user->isAdmin() && $payment->patient_id != $user->id) {
                return $this->error('شما دسترسی به این پرداخت ندارید', 403);
            }

            if ($payment->status !== Payment::STATUS_SUCCESS) {
                return $this->error('فقط پرداخت‌های موفق قابل عودت هستند', 400);
            }

            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'message' => 'عودت وجه انجام شد',
            ]);

            return $this->success($payment, 'عودت وجه با موفقیت انجام شد');

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

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
