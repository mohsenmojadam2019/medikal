<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shetabit\Payment\Facade\Payment;
use Shetabit\Multipay\Invoice as ShetabitInvoice;

class PaymentManager
{
    protected array $gateways = [];

    public function __construct()
    {
        $this->registerGateways();
    }

    protected function registerGateways(): void
    {
        $drivers = config('payment.drivers', []);
        $this->gateways = array_keys($drivers);

        if (!in_array('local', $this->gateways)) {
            $this->gateways[] = 'local';
        }
    }

    public function getGateway(string $name): string
    {
        if (!in_array($name, $this->gateways)) {
            throw new \Exception("درگاه {$name} پشتیبانی نمی‌شود");
        }
        return $name;
    }

    public function getAvailableGateways(): array
    {
        return $this->gateways;
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

            // برای درگاه local، فرم پرداخت رو خودمون می‌سازیم
            if ($gatewayName === 'local') {
                $transactionId = 'LOCAL_' . $invoice->id . '_' . time();

                // ذخیره پرداخت
                $payment = \App\Models\Payment::create([
                    'invoice_id' => $invoice->id,
                    'patient_id' => $invoice->patient_id,
                    'transaction_id' => $transactionId,
                    'amount' => $invoice->total_amount,
                    'gateway' => 'local',
                    'status' => \App\Enums\PaymentStatusEnum::PENDING,
                ]);

                $callbackUrl = route('payment.callback', ['gateway' => 'local']);

                DB::commit();

                return [
                    'success' => true,
                    'gateway' => 'local',
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->total_amount,
                    'form' => [
                        'action' => $callbackUrl,
                        'method' => 'POST',
                        'inputs' => [
                            'transactionId' => $transactionId,
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'title' => 'درگاه پرداخت تست',
                            'description' => 'این درگاه *صرفا* برای تست صحت روند پرداخت',
                            'amount' => $invoice->total_amount,
                            'payButton' => 'پرداخت موفق',
                            'cancelButton' => 'پرداخت ناموفق',
                        ],
                    ],
                    'message' => 'در حال انتقال به درگاه تست...',
                ];
            }

            // برای سایر درگاه‌ها از شتابیت استفاده کن
            $shetabitInvoice = new ShetabitInvoice();
            $shetabitInvoice->amount((int) $invoice->total_amount);

            if ($invoice->patient?->user) {
                if ($invoice->patient->user->email) {
                    $shetabitInvoice->detail('email', $invoice->patient->user->email);
                }
                if ($invoice->patient->user->mobile) {
                    $shetabitInvoice->detail('mobile', $invoice->patient->user->mobile);
                }
            }

            $shetabitInvoice->detail('metadata', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);

            $callbackUrl = $this->getCallbackUrl($gatewayName);

            try {
                $payment = Payment::via($gatewayName)
                    ->callbackUrl($callbackUrl)
                    ->purchase($shetabitInvoice, function ($driver, $transactionId) use ($invoice, $gatewayName) {
                        \App\Models\Payment::create([
                            'invoice_id' => $invoice->id,
                            'patient_id' => $invoice->patient_id,
                            'transaction_id' => $transactionId,
                            'amount' => $invoice->total_amount,
                            'gateway' => $gatewayName,
                            'status' => \App\Enums\PaymentStatusEnum::PENDING,
                            'raw_data' => ['authority' => $transactionId],
                        ]);
                    });

                $result = $payment->pay();

                $redirectUrl = null;
                if (method_exists($result, 'getActionUrl')) {
                    $redirectUrl = $result->getActionUrl();
                } elseif (method_exists($result, 'getAction')) {
                    $redirectUrl = $result->getAction();
                } elseif (is_string($result)) {
                    $redirectUrl = $result;
                } elseif (is_array($result) && isset($result['url'])) {
                    $redirectUrl = $result['url'];
                }

                if (!$redirectUrl) {
                    throw new \Exception('آدرس درگاه پرداخت یافت نشد');
                }

                DB::commit();

                return [
                    'success' => true,
                    'redirect_url' => $redirectUrl,
                    'gateway' => $gatewayName,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->total_amount,
                    'message' => 'در حال انتقال به درگاه پرداخت...',
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Payment initiation error: ' . $e->getMessage(), [
                    'invoice_id' => $invoice->id,
                    'gateway' => $gatewayName,
                ]);

                return [
                    'success' => false,
                    'message' => 'خطا در اتصال به درگاه پرداخت: ' . $e->getMessage(),
                    'gateway' => $gatewayName,
                ];
            }

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
            // برای درگاه local
            if ($gatewayName === 'local') {
                $transactionId = $request->input('transactionId');
                $invoiceId = $request->input('invoice_id');
                $cancel = $request->input('cancel');

                if ($cancel) {
                    return [
                        'success' => false,
                        'message' => 'پرداخت لغو شد',
                        'cancelled' => true,
                        'gateway' => 'local',
                    ];
                }

                if (!$transactionId) {
                    return [
                        'success' => false,
                        'message' => 'شناسه تراکنش یافت نشد',
                        'gateway' => 'local',
                    ];
                }

                $payment = \App\Models\Payment::where('transaction_id', $transactionId)
                    ->where('gateway', 'local')
                    ->first();

                if (!$payment) {
                    return [
                        'success' => false,
                        'message' => 'تراکنش یافت نشد',
                        'gateway' => 'local',
                    ];
                }

                $invoice = $payment->invoice;

                if (!$invoice) {
                    return [
                        'success' => false,
                        'message' => 'فاکتور یافت نشد',
                        'gateway' => 'local',
                    ];
                }

                $referenceId = 'LOCAL_REF_' . $transactionId . '_' . time();

                $payment->update([
                    'status' => \App\Enums\PaymentStatusEnum::SUCCESS,
                    'reference_code' => $referenceId,
                    'message' => 'پرداخت تست با موفقیت انجام شد',
                    'payment_date' => now(),
                ]);

                $invoice->markAsPaid();

                return [
                    'success' => true,
                    'reference_id' => $referenceId,
                    'invoice' => $invoice,
                    'payment' => $payment,
                    'message' => 'پرداخت با موفقیت انجام شد',
                    'gateway' => 'local',
                ];
            }

            // برای سایر درگاه‌ها
            $authority = $request->input('Authority') ?? $request->input('authority');
            $refId = $request->input('RefId') ?? $request->input('refid');

            $payment = \App\Models\Payment::where(function ($query) use ($authority, $refId) {
                if ($authority) {
                    $query->orWhere('transaction_id', $authority);
                }
                if ($refId) {
                    $query->orWhere('transaction_id', $refId);
                }
            })
            ->where('gateway', $gatewayName)
            ->where('status', \App\Enums\PaymentStatusEnum::PENDING)
            ->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'تراکنش یافت نشد',
                ];
            }

            $invoice = $payment->invoice;

            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => 'فاکتور یافت نشد',
                ];
            }

            try {
                $receipt = Payment::via($gatewayName)
                    ->amount((int) $invoice->total_amount)
                    ->transactionId($payment->transaction_id)
                    ->verify();

                $referenceId = $receipt->getReferenceId();

                $payment->update([
                    'status' => \App\Enums\PaymentStatusEnum::SUCCESS,
                    'reference_code' => $referenceId,
                    'message' => 'پرداخت با موفقیت انجام شد',
                    'payment_date' => now(),
                ]);

                $invoice->markAsPaid();

                return [
                    'success' => true,
                    'reference_id' => $referenceId,
                    'invoice' => $invoice,
                    'payment' => $payment,
                    'message' => 'پرداخت با موفقیت انجام شد',
                    'gateway' => $gatewayName,
                ];

            } catch (\Exception $e) {
                $payment->update([
                    'status' => \App\Enums\PaymentStatusEnum::FAILED,
                    'message' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'invoice' => $invoice,
                    'payment' => $payment,
                    'gateway' => $gatewayName,
                ];
            }

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

    protected function getCallbackUrl(string $gateway): string
    {
        try {
            return route('payment.callback', ['gateway' => $gateway]);
        } catch (\Exception $e) {
            return config('app.url') . '/api/payment/callback/' . $gateway;
        }
    }
}
