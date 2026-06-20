<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Transaction;
use Shetabit\Multipay\Invoice;

abstract class BaseGateway implements GatewayInterface
{
    protected string $gatewayName;
    protected Order $order;

    public function __construct()
    {
        $this->gatewayName = $this->getGatewayName();
    }

    abstract protected function getGatewayName(): string;

    protected function createInvoice(Order $order): Invoice
    {
        $invoice = (new Invoice)->amount((int) $order->total);

        if ($order->user) {
            if ($order->user->email) {
                $invoice->detail('email', $order->user->email);
            }
            if ($order->user->phone) {
                $invoice->detail('mobile', $order->user->phone);
            }
        }

        return $invoice;
    }

    protected function storeTransaction(Order $order, string $transactionId, array $extra = []): Transaction
    {
        return Transaction::create([
            'customer_id' => $order->user_id,
            'transaction_id' => $transactionId,
            'amount' => $order->total,
            'order_id' => $order->id,
            'payment' => $this->gatewayName,
            'status' => 'pending',
            'currency' => 'IRT',
            'metadata' => json_encode(array_merge([
                'order_number' => $order->order_number,
            ], $extra)),
        ]);
    }

    /**
     * دریافت آدرس بازگشت از درگاه پرداخت
     */
    public function getCallbackUrl(): string
    {
        $gateway = $this->getGatewayName();

        // استفاده از route helper
        try {
            return route('payment.callback', ['gateway' => $gateway]);
        } catch (\Exception $e) {
            // اگر route helper خطا داد، از آدرس مستقیم استفاده کن
            $apiUrl = config('app.url', 'http://localhost:8000');
            return $apiUrl . '/api/payment/callback/' . $gateway;
        }
    }
}
