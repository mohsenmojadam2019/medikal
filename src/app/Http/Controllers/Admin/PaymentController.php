<?php
// app/Http/Controllers/Admin/PaymentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use App\Services\Payment\PaymentService;
use App\Traits\ApiResponse;
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

    /**
     * لیست پرداخت‌ها
     */
    public function index(Request $request)
    {
        try {
            $tenantId = session('tenant_id', 1);

            $query = Payment::where('tenant_id', $tenantId)
                ->with(['patient', 'invoice']);

            // فیلتر بر اساس وضعیت
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // فیلتر بر اساس درگاه
            if ($request->has('gateway')) {
                $query->where('gateway', $request->gateway);
            }

            // فیلتر بر اساس بیمار
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            // فیلتر بر اساس تاریخ
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // جستجو
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('reference_code', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($q2) use ($search) {
                            $q2->where('full_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('invoice', function ($q2) use ($search) {
                            $q2->where('invoice_number', 'like', "%{$search}%");
                        });
                });
            }

            $payments = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->success($payments);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * نمایش پرداخت
     */
    public function show($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $payment = Payment::where('tenant_id', $tenantId)
                ->with(['patient', 'invoice'])
                ->findOrFail($id);

            return $this->success($payment);
        } catch (\Exception $e) {
            return $this->error('پرداخت یافت نشد', 404);
        }
    }

    /**
     * بازگشت وجه
     */
    public function refund($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $payment = Payment::where('tenant_id', $tenantId)->findOrFail($id);

            $result = $this->paymentService->refundPayment($payment);

            return $this->success($result, 'بازگشت وجه با موفقیت انجام شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار پرداخت‌ها
     */
    public function stats()
    {
        try {
            $tenantId = session('tenant_id', 1);

            $total = Payment::where('tenant_id', $tenantId)->count();
            $pending = Payment::where('tenant_id', $tenantId)->where('status', 'pending')->count();
            $success = Payment::where('tenant_id', $tenantId)->where('status', 'success')->count();
            $failed = Payment::where('tenant_id', $tenantId)->where('status', 'failed')->count();
            $refunded = Payment::where('tenant_id', $tenantId)->where('status', 'refunded')->count();
            $totalAmount = Payment::where('tenant_id', $tenantId)->where('status', 'success')->sum('amount');

            return $this->success([
                'total_payments' => $total,
                'pending_count' => $pending,
                'success_count' => $success,
                'failed_count' => $failed,
                'refunded_count' => $refunded,
                'total_amount' => $totalAmount,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * دریافت درگاه‌های پرداخت
     */
    public function gateways()
    {
        try {
            $gateways = $this->paymentService->getAvailableGateways();

            $gatewayList = [];
            foreach ($gateways as $gateway) {
                $gatewayList[] = [
                    'value' => $gateway,
                    'label' => $this->getGatewayLabel($gateway),
                ];
            }

            return $this->success([
                'gateways' => $gatewayList,
                'default' => $this->paymentService->getDefaultGateway(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * دریافت نام درگاه
     */
    private function getGatewayLabel($gateway)
    {
        $labels = [
            'zarinpal' => 'زرین‌پال',
            'asanpardakht' => 'آسان پرداخت',
            'paypal' => 'پی‌پال',
            'stripe' => 'استرایپ',
            'local' => 'محلی (تست)',
        ];
        return $labels[$gateway] ?? $gateway;
    }
}
