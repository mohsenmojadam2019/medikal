<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaymentManager
{
    protected CartService $cartService;
    protected array $gateways = [];

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
        $this->registerGateways();
    }

    /**
     * ثبت درگاه‌های پرداخت - فقط درگاه‌هایی که کلاس دارند
     */
    protected function registerGateways(): void
    {
        $this->gateways = [
            'zarinpal' => app(ZarinpalGateway::class),
            'local' => app(LocalGateway::class),
            // درگاه‌های زیر را کامنت کنید تا خطا ندهد
            // 'asanpardakht' => app(AsanpardakhtGateway::class),
            // 'paypal' => app(PaypalGateway::class),
            // 'stripe' => app(StripeGateway::class),
        ];
    }

    public function getGateway(string $name)
    {
        if (!isset($this->gateways[$name])) {
            throw new \Exception("درگاه {$name} پشتیبانی نمی‌شود");
        }
        return $this->gateways[$name];
    }

    protected function createInvoice(Order $order, string $gateway): \Shetabit\Multipay\Invoice
    {
        $invoice = new \Shetabit\Multipay\Invoice();

        // تبدیل مبلغ به ارز مناسب درگاه
        $amount = $this->convertCurrency($order->total, $gateway);
        $invoice->amount($amount);

        // تنظیم ارز
        $currency = $this->getGatewayCurrency($gateway);
        $invoice->detail('currency', $currency);

        return $invoice;
    }

    protected function convertCurrency(int $rialAmount, string $gateway): int
    {
        $currency = $this->getGatewayCurrency($gateway);

        if ($currency === 'USD') {
            // نرخ دلار آزاد فعلی (از سیستم قیمت)
            try {
                $usdRate = app(\App\Services\Price\PriceManager::class)->getUsdPrice();
            } catch (\Exception $e) {
                $usdRate = 60000; // نرخ پیش‌فرض
            }
            return (int)round($rialAmount / $usdRate);
        }

        if ($currency === 'EUR') {
            $eurRate = $this->getEurRate();
            return (int)round($rialAmount / $eurRate);
        }

        return $rialAmount;
    }

    protected function getGatewayCurrency(string $gateway): string
    {
        $config = PaymentSetting::where('gateway', $gateway)->first();

        if ($config && isset($config->config['currency'])) {
            return $config->config['currency'];
        }

        return match ($gateway) {
            'paypal', 'stripe' => 'USD',
            default => 'IRT',
        };
    }

    protected function getEurRate(): int
    {
        try {
            $usdRate = app(\App\Services\Price\PriceManager::class)->getUsdPrice();
            return (int)round($usdRate * 1.05);
        } catch (\Exception $e) {
            return 63000; // نرخ پیش‌فرض یورو
        }
    }

    /**
     * شروع پرداخت - با قفل و lockForUpdate
     */
    public function initiate(string $gatewayName, Order $order, array $options = []): array
    {
        $lockKey = 'payment_init_' . $order->id;
        $lock = Cache::lock($lockKey, 30);

        try {
            if (!$lock->get()) {
                return [
                    'success' => false,
                    'message' => 'در حال پردازش درخواست قبلی، لطفاً مجدد تلاش کنید'
                ];
            }

            DB::beginTransaction();

            // قفل کردن سفارش
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            if (!$order) {
                DB::rollBack();
                return ['success' => false, 'message' => 'سفارش یافت نشد'];
            }

            // بررسی اینکه آیا قبلاً پرداخت شروع شده
            if ($order->payment_status === 'paid') {
                DB::rollBack();
                return ['success' => false, 'message' => 'این سفارش قبلاً پرداخت شده است'];
            }

            // ✅ فقط لاگ بگیر، وضعیت رو آپدیت نکن (چون 'processing' در دیتابیس وجود ندارد)
            Log::info('Payment initiation started for order', [
                'order_id' => $order->id,
                'gateway' => $gatewayName,
                'current_status' => $order->payment_status
            ]);

            $gateway = $this->getGateway($gatewayName);
            $result = $gateway->initiate($order, $options);

            if ($result['success'] ?? false) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initiation error: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'gateway' => $gatewayName,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در شروع پرداخت: ' . $e->getMessage()
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * تأیید پرداخت - با قفل کامل و lockForUpdate
     */
    public function verify(string $gatewayName, Request $request): array
    {
        $gateway = $this->getGateway($gatewayName);
        $result = $gateway->verify($request);

        // اگر پرداخت ناموفق بود یا سفارش نداشت
        if (!($result['success'] ?? false) || !isset($result['order'])) {
            return $this->buildRedirectResponse($result);
        }

        $orderId = $result['order']->id;
        $lockKey = 'payment_verify_' . $orderId;
        $lock = Cache::lock($lockKey, 30);

        try {
            if (!$lock->get()) {
                Log::warning('Payment verify lock timeout', ['order_id' => $orderId]);
                return [
                    'success' => false,
                    'message' => 'در حال پردازش پرداخت، لطفاً چند لحظه دیگر بررسی کنید'
                ];
            }

            DB::beginTransaction();

            // قفل کردن سفارش
            $order = Order::where('id', $orderId)->lockForUpdate()->first();

            if (!$order) {
                DB::rollBack();
                Log::warning('Order not found in payment verify', ['order_id' => $orderId]);
                return $this->buildRedirectResponse(array_merge($result, [
                    'success' => false,
                    'message' => 'سفارش یافت نشد'
                ]));
            }

            // Double-check: اگر قبلاً پرداخت شده، فقط redirect برگردان
            if ($order->payment_status === 'paid') {
                DB::rollBack();
                Log::info('Order already paid', ['order_id' => $orderId]);
                return $this->buildRedirectResponse($result);
            }

            // ✅ کاهش موجودی و پردازش سفارش
            try {
                $orderService = app(OrderService::class);
                $orderService->processPaymentAndDeductStock($order);

                // ✅ بروزرسانی وضعیت پرداخت به 'paid' (این مقدار در دیتابیس وجود دارد)
                $order->update([
                    'payment_status' => 'paid',
                    'paid_at' => now()
                ]);

                // ✅ بروزرسانی وضعیت سفارش
                $order->update(['status' => 'processing']);

                Log::info('Payment stock deduction completed', [
                    'order_id' => $orderId,
                    'order_number' => $order->order_number
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to process payment stock deduction: ' . $e->getMessage(), [
                    'order_id' => $orderId,
                    'trace' => $e->getTraceAsString()
                ]);

                return $this->buildRedirectResponse(array_merge($result, [
                    'success' => false,
                    'message' => 'خطا در پردازش سفارش: ' . $e->getMessage()
                ]));
            }

            DB::commit();

            // پاک کردن کش سفارش
            Cache::forget('order_' . $order->id);
            Cache::forget('user_orders_' . $order->user_id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment verification error: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);
            $result['success'] = false;
            $result['message'] = 'خطا در تأیید پرداخت: ' . $e->getMessage();
        } finally {
            $lock->release();
        }

        return $this->buildRedirectResponse($result);
    }

    /**
     * ساخت redirect response برای فرانت‌اند
     */
    protected function buildRedirectResponse(array $result): array
    {
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

        $result['redirect_url'] = $frontendUrl . '/payment/result?' . http_build_query($params);

        return $result;
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }
}
