<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Pharmacy\PharmacyOrderService;
use App\Models\PharmacyOrder;
use App\Models\Pharmacy;
use App\Models\Patient;
use App\Http\Requests\Api\PharmacyOrderRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    use ApiResponse;

    protected PharmacyOrderService $orderService;

    public function __construct(PharmacyOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    // ========== Public Methods (بدون احراز هویت) ==========
    
    /**
     * تایید پرداخت (Callback از درگاه)
     */
    public function paymentCallback(Request $request)
    {
        $orderNumber = $request->order_number;
        $refCode = $request->ref_code;
        $gateway = $request->gateway ?? 'local';

        $order = PharmacyOrder::where('order_number', $orderNumber)->first();

        if (!$order) {
            return $this->error('سفارش یافت نشد', 404);
        }

        if ($order->is_paid) {
            return $this->error('این سفارش قبلاً پرداخت شده است', 400);
        }

        try {
            $result = $this->orderService->verifyPayment($gateway, $request->all());
            
            if ($result['success']) {
                return $this->success($order, 'پرداخت با موفقیت انجام شد');
            }
            
            return $this->error($result['message'], 400);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ========== Protected Methods (نیاز به احراز هویت) ==========

    /**
     * لیست داروخانه‌های نزدیک
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        $radius = $request->radius ?? 10;
        $pharmacies = Pharmacy::nearby($request->lat, $request->lng, $radius)
            ->active()
            ->online()
            ->get();

        return $this->success($pharmacies);
    }

    /**
     * لیست داروخانه‌های طرف قرارداد
     */
    public function contracted(Request $request)
    {
        $pharmacies = Pharmacy::whereHas('contracts', function ($query) {
            $query->where('is_active', true);
        })->active()->online()->get();

        return $this->success($pharmacies);
    }

    /**
     * ثبت سفارش جدید
     */
    public function store(PharmacyOrderRequest $request)
    {
        try {
            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return $this->error('بیمار یافت نشد', 404);
            }

            $data = $request->validated();
            $data['patient_id'] = $patient->id;

            $order = $this->orderService->createOrder($data);

            return $this->success(
                $this->orderService->getOrderStatus($order),
                'سفارش با موفقیت ثبت شد',
                201
            );

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش سفارش
     */
    public function show($id)
    {
        try {
            $order = PharmacyOrder::with(['items', 'items.drug', 'pharmacy'])
                ->findOrFail($id);

            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$user->isAdmin() && (!$patient || $order->patient_id != $patient->id)) {
                return $this->error('شما دسترسی به این سفارش ندارید', 403);
            }

            return $this->success($this->orderService->getOrderStatus($order));

        } catch (\Exception $e) {
            return $this->error('سفارش یافت نشد', 404);
        }
    }

    /**
     * لیست سفارشات من (بیمار)
     */
    public function myOrders(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $orders = $this->orderService->getPatientOrders($patient->id, $request->get('per_page', 15));
        return $this->success($orders);
    }

    /**
     * لیست سفارشات داروخانه (ادمین/داروخانه)
     */
    public function pharmacyOrders(Request $request)
    {
        $user = auth()->user();
        $pharmacy = Pharmacy::where('user_id', $user->id)->first();

        if (!$user->isAdmin() && !$pharmacy) {
            return $this->error('شما دسترسی به این بخش را ندارید', 403);
        }

        $pharmacyId = $pharmacy?->id ?? $request->pharmacy_id;
        $orders = $this->orderService->getPharmacyOrders($pharmacyId, $request->get('per_page', 15));
        return $this->success($orders);
    }

    /**
     * شروع پرداخت سفارش
     */
    public function pay(Request $request, $id)
    {
        $request->validate([
            'gateway' => 'nullable|in:zarinpal,asanpardakht,local',
        ]);

        try {
            $order = PharmacyOrder::findOrFail($id);

            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$user->isAdmin() && (!$patient || $order->patient_id != $patient->id)) {
                return $this->error('شما دسترسی به این سفارش ندارید', 403);
            }

            $gateway = $request->gateway ?? config('payment.default_gateway', 'local');
            $result = $this->orderService->initiatePayment($order, $gateway);

            if ($result['success']) {
                return $this->success($result, 'در حال انتقال به درگاه پرداخت...');
            }

            return $this->error($result['message'], 400);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لغو سفارش
     */
    public function cancel($id)
    {
        try {
            $order = PharmacyOrder::findOrFail($id);

            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$user->isAdmin() && (!$patient || $order->patient_id != $patient->id)) {
                return $this->error('شما دسترسی به این سفارش ندارید', 403);
            }

            if ($order->is_paid) {
                return $this->error('سفارش پرداخت شده قابل لغو نیست', 400);
            }

            $order->update([
                'status' => \App\Enums\PharmacyOrderStatusEnum::CANCELLED,
                'cancelled_at' => now(),
            ]);

            return $this->success(null, 'سفارش با موفقیت لغو شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نوتیفیکیشن‌های من
     */
    public function notifications(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $notifications = \App\Models\PharmacyNotification::where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($notifications);
    }

    /**
     * علامت‌گذاری نوتیفیکیشن به عنوان خوانده شده
     */
    public function markNotificationAsRead($id)
    {
        try {
            $notification = \App\Models\PharmacyNotification::findOrFail($id);
            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient || $notification->patient_id != $patient->id) {
                return $this->error('شما دسترسی به این نوتیفیکیشن ندارید', 403);
            }

            $notification->markAsRead();
            return $this->success(null, 'نوتیفیکیشن به عنوان خوانده شده علامت‌گذاری شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
