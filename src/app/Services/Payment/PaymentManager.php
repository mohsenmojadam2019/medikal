<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Enums\PaymentStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shetabit\Payment\Facade\Payment as ShetabitPayment;
use Shetabit\Multipay\Invoice as ShetabitInvoice;

class PaymentManager
{
    protected array $gateways = [];
    protected array $gatewayClasses = [];

    public function __construct()
    {
        $this->registerGateways();
    }

    protected function registerGateways(): void
    {
        // دریافت لیست درگاه‌ها از config
        $drivers = config('payment.drivers', []);
        $this->gateways = array_keys($drivers);

        // اگر local در لیست نبود اضافه کن
        if (!in_array('local', $this->gateways)) {
            $this->gateways[] = 'local';
        }

        // ثبت کلاس‌های درگاه‌ها
        $this->gatewayClasses = [
            'local' => LocalGateway::class,
            'zarinpal' => ZarinpalGateway::class,
            'asanpardakht' => AsanpardakhtGateway::class,
            'behpardakht' => BehpardakhtGateway::class,
            'paypal' => PaypalGateway::class,
            'stripe' => StripeGateway::class,
            'idpay' => IdpayGateway::class,
            'payir' => PayirGateway::class,
            'zibal' => ZibalGateway::class,
            'nextpay' => NextpayGateway::class,
            'sadad' => SadadGateway::class,
            'parsian' => ParsianGateway::class,
            'pasargad' => PasargadGateway::class,
            'saman' => SamanGateway::class,
            'payping' => PaypingGateway::class,
            'vandar' => VandarGateway::class,
        ];
    }

    public function getGateway(string $name): GatewayInterface
    {
        if (!isset($this->gatewayClasses[$name])) {
            throw new \Exception("درگاه {$name} پشتیبانی نمی‌شود");
        }

        $class = $this->gatewayClasses[$name];
        return app($class);
    }

    public function getAvailableGateways(): array
    {
        // فقط درگاه‌هایی که کلاس دارند برگردان
        $available = [];
        foreach ($this->gateways as $gateway) {
            if (isset($this->gatewayClasses[$gateway])) {
                $available[] = $gateway;
            }
        }
        return $available;
    }

    public function getDefaultGateway(): string
    {
        return config('payment.default', 'local');
    }

    public function initiate(string $gatewayName, Invoice $invoice, array $options = []): array
    {
        $lockKey = 'payment_init_' . $invoice->id;
        $lock = Cache::lock($lockKey, 30);

        try {
            if (!$lock->get()) {
                return [
                    'success' => false,
                    'message' => 'در حال پردازش درخواست قبلی، لطفاً مجدد تلاش کنید',
                    'gateway' => $gatewayName,
                ];
            }

            DB::beginTransaction();

            $invoice = Invoice::where('id', $invoice->id)->lockForUpdate()->first();

            if (!$invoice) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'فاکتور یافت نشد',
                ];
            }

            if ($invoice->is_paid) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'این فاکتور قبلاً پرداخت شده است',
                ];
            }

            $gateway = $this->getGateway($gatewayName);
            $result = $gateway->initiate($invoice, $options);

            if ($result['success']) {
                DB::commit();
                return $result;
            }

            DB::rollBack();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initiation error: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id ?? null,
                'gateway' => $gatewayName,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در شروع پرداخت: ' . $e->getMessage(),
            ];
        } finally {
            $lock->release();
        }
    }

    public function verify(string $gatewayName, Request $request): array
    {
        try {
            $gateway = $this->getGateway($gatewayName);
            return $gateway->verify($request);
        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage(), [
                'gateway' => $gatewayName,
            ]);

            return [
                'success' => false,
                'message' => 'خطا در تأیید پرداخت: ' . $e->getMessage(),
                'gateway' => $gatewayName,
            ];
        }
    }

    public function isGatewayAvailable(string $gatewayName): bool
    {
        return in_array($gatewayName, $this->getAvailableGateways());
    }
}
