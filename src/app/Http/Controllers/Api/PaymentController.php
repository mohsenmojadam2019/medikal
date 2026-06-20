<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * شروع فرآیند پرداخت - با قفل و اعتبارسنجی
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'gateway' => 'required|string',
        ]);

        $lock = Cache::lock('payment_initiate_' . $request->order_id, 30);

        try {
            if (!$lock->get()) {
                return response()->json([
                    'success' => false,
                    'message' => 'در حال پردازش درخواست قبلی، لطفاً مجدد تلاش کنید'
                ], 429);
            }

            DB::beginTransaction();

            $order = Order::where('id', $request->order_id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'سفارش یافت نشد'
                ], 404);
            }

            if ($order->payment_status === 'paid') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'این سفارش قبلاً پرداخت شده است'
                ], 400);
            }

            if ($order->total <= 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'مبلغ سفارش معتبر نیست'
                ], 400);
            }

            $paymentSetting = PaymentSetting::where('gateway', $request->gateway)
                ->where('is_active', true)
                ->first();

            if (!$paymentSetting) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'درگاه پرداخت مورد نظر فعال نیست'
                ], 400);
            }

            $this->applyGatewayConfig($request->gateway, $paymentSetting->config ?? []);
            $this->mergeGatewayConfig($request->gateway, $paymentSetting->config ?? []);

            $order->load(['user', 'items']);

            $result = $this->paymentManager->initiate($request->gateway, $order, $request->all());

            if (isset($result['success']) && $result['success'] === false) {
                DB::rollBack();
                return response()->json($result, 400);
            }

            DB::commit();

            if (isset($result['redirect_url'])) {
                return response()->json($result);
            }

            if (isset($result['form'])) {
                return response()->json($result);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initiate error: ' . $e->getMessage(), [
                'order_id' => $request->order_id,
                'gateway' => $request->gateway
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطا در شروع فرآیند پرداخت: ' . $e->getMessage()
            ], 500);
        } finally {
            $lock->release();
        }
    }

    /**
     * برگشت از درگاه پرداخت (Callback)
     */
    /**
     * برگشت از درگاه پرداخت (Callback)
     */
    public function callback(Request $request, string $gateway)
    {
        Log::info('Payment callback received', [
            'gateway' => $gateway,
            'all_params' => $request->all(),
            'method' => $request->method()
        ]);

        try {
            $paymentSetting = PaymentSetting::where('gateway', $gateway)
                ->where('is_active', true)
                ->first();

            if ($paymentSetting) {
                $this->applyGatewayConfig($gateway, $paymentSetting->config ?? []);
                $this->mergeGatewayConfig($gateway, $paymentSetting->config ?? []);
            }

            $result = $this->paymentManager->verify($gateway, $request);

            Log::info('Payment callback result', [
                'gateway' => $gateway,
                'success' => $result['success'] ?? false,
                'message' => $result['message'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Payment callback exception: ' . $e->getMessage(), [
                'gateway' => $gateway,
                'trace' => $e->getTraceAsString()
            ]);

            $result = [
                'success' => false,
                'message' => 'خطا در پردازش بازگشت از درگاه: ' . $e->getMessage()
            ];
        }

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        if ($result['success'] ?? false) {
            $params = [
                'success' => 'true',
                'ref_id' => $result['reference_id'] ?? '',
                'message' => $result['message'] ?? 'پرداخت با موفقیت انجام شد',
                'order_id' => $result['order']->id ?? '',
                'order_number' => $result['order']->order_number ?? '',
            ];
        } else {
            $params = [
                'success' => 'false',
                'message' => $result['message'] ?? 'پرداخت ناموفق بود',
                'order_id' => $result['order']->id ?? '',
            ];
        }

        $redirectUrl = $frontendUrl . '/payment/result?' . http_build_query($params);

        Log::info('Redirecting to frontend', ['url' => $redirectUrl]);

        return redirect()->away($redirectUrl);
    }

    /**
     * دریافت لیست درگاه‌های فعال
     */
    public function getActiveGateways()
    {
        try {
            $gateways = PaymentSetting::where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get(['gateway', 'name', 'is_default']);

            return response()->json([
                'success' => true,
                'data' => $gateways
            ]);
        } catch (\Exception $e) {
            Log::error('Get active gateways error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست درگاه‌ها',
                'data' => []
            ], 500);
        }
    }

    /**
     * اعمال تنظیمات درگاه از دیتابیس
     */
    private function applyGatewayConfig(string $gateway, array $config): void
    {
        if (empty($config)) {
            return;
        }

        // درگاه تست
        if ($gateway === 'local') {
            if (isset($config['callbackUrl'])) config(["payment.drivers.local.callbackUrl" => $config['callbackUrl']]);
            if (isset($config['title'])) config(["payment.drivers.local.title" => $config['title']]);
            if (isset($config['description'])) config(["payment.drivers.local.description" => $config['description']]);
            if (isset($config['orderLabel'])) config(["payment.drivers.local.orderLabel" => $config['orderLabel']]);
            if (isset($config['amountLabel'])) config(["payment.drivers.local.amountLabel" => $config['amountLabel']]);
            if (isset($config['payButton'])) config(["payment.drivers.local.payButton" => $config['payButton']]);
            if (isset($config['cancelButton'])) config(["payment.drivers.local.cancelButton" => $config['cancelButton']]);
        }

        // زرین‌پال
        if ($gateway === 'zarinpal') {
            if (isset($config['merchantId'])) config(["payment.drivers.zarinpal.merchantId" => $config['merchantId']]);
            if (isset($config['mode'])) config(["payment.drivers.zarinpal.mode" => $config['mode']]);
            if (isset($config['callbackUrl'])) config(["payment.drivers.zarinpal.callbackUrl" => $config['callbackUrl']]);
        }

        // آسان پرداخت
        if ($gateway === 'asanpardakht') {
            if (isset($config['merchantConfigID'])) config(["payment.drivers.asanpardakht.merchantConfigID" => $config['merchantConfigID']]);
            if (isset($config['username'])) config(["payment.drivers.asanpardakht.username" => $config['username']]);
            if (isset($config['password'])) config(["payment.drivers.asanpardakht.password" => $config['password']]);
            if (isset($config['encryption_key'])) config(["payment.drivers.asanpardakht.encryption_key" => $config['encryption_key']]);
            if (isset($config['encryption_iv'])) config(["payment.drivers.asanpardakht.encryption_iv" => $config['encryption_iv']]);
            if (isset($config['callbackUrl'])) config(["payment.drivers.asanpardakht.callbackUrl" => $config['callbackUrl']]);
        }

        // به پرداخت ملت
        if ($gateway === 'behpardakht') {
            if (isset($config['terminalId'])) config(["payment.drivers.behpardakht.terminalId" => $config['terminalId']]);
            if (isset($config['username'])) config(["payment.drivers.behpardakht.username" => $config['username']]);
            if (isset($config['password'])) config(["payment.drivers.behpardakht.password" => $config['password']]);
            if (isset($config['callbackUrl'])) config(["payment.drivers.behpardakht.callbackUrl" => $config['callbackUrl']]);
        }

        Log::info("Gateway config applied for: {$gateway}", ['config_keys' => array_keys($config)]);
    }

    /**
     * ادغام تنظیمات دیتابیس با فایل کانفیگ
     */
    private function mergeGatewayConfig(string $gateway, array $config): void
    {
        $currentConfig = config("payment.drivers.{$gateway}", []);
        $mergedConfig = array_merge($currentConfig, $config);

        // حذف مقادیر null و خالی
        $mergedConfig = array_filter($mergedConfig, function ($value) {
            return $value !== null && $value !== '';
        });

        config(["payment.drivers.{$gateway}" => $mergedConfig]);
    }
}
