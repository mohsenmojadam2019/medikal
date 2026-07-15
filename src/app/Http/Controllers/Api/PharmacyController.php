<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Pharmacy\PharmacyOrderService;
use App\Models\PharmacyOrder;
use App\Models\Pharmacy;
use App\Models\Patient;
use App\Models\Drug;
use App\Http\Requests\Api\PharmacyOrderRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PharmacyController extends Controller
{
    use ApiResponse;

    protected PharmacyOrderService $orderService;

    public function __construct(PharmacyOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    // ============================================
    // Public Methods (بدون احراز هویت)
    // ============================================

    /**
     * پرداخت callback برای داروخانه
     */
    /**
     * پرداخت callback برای داروخانه
     */
    public function paymentCallback(Request $request)
    {
        try {
            $orderNumber = $request->query('order_number');
            $gateway = $request->query('gateway');
            $transactionId = $request->query('transactionId');
            $success = $request->query('success');
            $amount = $request->query('amount');

            \Log::info('📞 Pharmacy payment callback received', [
                'order_number' => $orderNumber,
                'gateway' => $gateway,
                'transactionId' => $transactionId,
                'success' => $success,
                'amount' => $amount,
                'all_params' => $request->all()
            ]);

            // پیدا کردن سفارش
            $order = PharmacyOrder::where('order_number', $orderNumber)->first();

            if (!$order) {
                \Log::error('❌ Order not found in callback', ['order_number' => $orderNumber]);
                return response()->json([
                    'success' => false,
                    'message' => 'سفارش یافت نشد',
                    'errors' => null
                ], 404);
            }

            // اگر پرداخت موفق بود، وضعیت رو آپدیت کن
            if ($success === 'true' || $success === '1') {
                // ✅ آپدیت کامل - هم payment_status و هم status
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'paid',  // ✅ این مهمه!
                    'payment_gateway' => $gateway,
                    'payment_authority' => $transactionId,
                    'paid_at' => now(),
                ]);

                \Log::info('✅ Order updated to paid', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'new_status' => 'paid'
                ]);

                // هدایت به صفحه موفقیت
                $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
                return redirect()->away($frontendUrl . '/fa/pharmacy/payment/callback?success=true&order_number=' . $orderNumber);
            } else {
                \Log::info('❌ Payment failed for order', ['order_id' => $order->id]);

                $order->update([
                    'payment_status' => 'failed',
                    'status' => 'failed',  // ✅ این رو هم آپدیت کن
                ]);

                $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
                return redirect()->away($frontendUrl . '/fa/pharmacy/payment/callback?success=false&order_number=' . $orderNumber);
            }

        } catch (\Exception $e) {
            \Log::error('❌ Payment callback error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => null
            ], 500);
        }
    }

    // ============================================
    // Protected Methods (نیاز به احراز هویت)
    // ============================================

    /**
     * لیست داروخانه‌های نزدیک
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $radius = $request->radius ?? 10;
        $perPage = $request->per_page ?? 15;

        $pharmacies = Pharmacy::nearby($request->lat, $request->lng, $radius)
            ->active()
            ->online()
            ->paginate($perPage);

        // اضافه کردن فاصله به هر آیتم
        $pharmacies->getCollection()->transform(function ($pharmacy) {
            $pharmacy->distance_text = $pharmacy->distance ?
                ($pharmacy->distance < 1 ?
                    round($pharmacy->distance * 1000) . ' متر' :
                    number_format($pharmacy->distance, 1) . ' کیلومتر') :
                null;
            return $pharmacy;
        });

        return $this->success($pharmacies, 'لیست داروخانه‌های نزدیک');
    }

    /**
     * لیست داروخانه‌های طرف قرارداد
     */
    public function contracted(Request $request)
    {
        $pharmacies = Pharmacy::whereHas('contracts', function ($query) {
            $query->where('is_active', true);
        })->active()->online()->get();

        return $this->success($pharmacies, 'لیست داروخانه‌های طرف قرارداد');
    }

    /**
     * جستجوی داروخانه‌ها
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        $query = Pharmacy::active()->online();

        $searchTerm = $request->q;
        $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('address', 'like', "%{$searchTerm}%")
                ->orWhere('license_number', 'like', "%{$searchTerm}%");
        });

        if ($request->has('lat') && $request->has('lng')) {
            $radius = $request->radius ?? 10;
            $query->nearby($request->lat, $request->lng, $radius);
        }

        $pharmacies = $query->paginate($request->per_page ?? 15);

        return $this->success($pharmacies, 'نتایج جستجو');
    }

    /**
     * لیست محصولات داروخانه
     */
    public function products(Request $request, $pharmacyId = null)
    {
        $query = Drug::where('is_active', true);

        if ($pharmacyId) {
            $query->whereHas('pharmacyStock', function ($q) use ($pharmacyId) {
                $q->where('pharmacy_id', $pharmacyId)
                    ->where('stock', '>', 0);
            });
        }

        $drugs = $query->paginate($request->per_page ?? 20);

        return $this->success($drugs, 'لیست محصولات');
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

            // ✅ تنظیم pharmacy_id پیش‌فرض
            // اگر در درخواست نیومده، از اولین داروخانه فعال استفاده کن
            if (!isset($data['pharmacy_id']) || empty($data['pharmacy_id'])) {
                $pharmacy = Pharmacy::active()->online()->first();
                if ($pharmacy) {
                    $data['pharmacy_id'] = $pharmacy->id;
                } else {
                    // اگر هیچ داروخانه‌ای وجود نداره، از ۱ استفاده کن
                    $data['pharmacy_id'] = 1;
                }
            }

            // محاسبه total_amount
            $subtotal = 0;
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as &$item) {
                    if (!isset($item['price']) || $item['price'] == 0) {
                        $drug = Drug::find($item['drug_id']);
                        if ($drug) {
                            $item['price'] = $drug->price;
                            $item['name'] = $drug->generic_name ?? $drug->name;
                        }
                    }
                    $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
                }
            }

            $data['subtotal'] = $subtotal;
            $data['total_amount'] = $subtotal + ($data['delivery_fee'] ?? 0) + ($data['tax'] ?? 0);

            // ذخیره اطلاعات تحویل
            $data['recipient_name'] = $request->recipient_name ?? $user->name;
            $data['recipient_phone'] = $request->recipient_phone ?? $user->mobile;
            $data['delivery_address'] = $request->delivery_address;
            $data['delivery_notes'] = $request->delivery_notes ?? null;

            // تنظیم status پیش‌فرض
            $data['status'] = 'payment_pending';
            $data['payment_status'] = 'pending';

            Log::info('📦 Creating order', [
                'patient_id' => $patient->id,
                'pharmacy_id' => $data['pharmacy_id'],
                'recipient_name' => $data['recipient_name'],
                'total_amount' => $data['total_amount']
            ]);

            $order = $this->orderService->createOrder($data);

            $orderData = $this->orderService->getOrderStatus($order);

            $orderData['recipient_name'] = $order->recipient_name;
            $orderData['recipient_phone'] = $order->recipient_phone;
            $orderData['delivery_address'] = $order->delivery_address;
            $orderData['delivery_notes'] = $order->delivery_notes;

            return $this->success($orderData, 'سفارش با موفقیت ثبت شد', 201);

        } catch (\Exception $e) {
            Log::error('❌ Order creation error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش سفارش
     */
    public function show($id)
    {
        try {
            $order = PharmacyOrder::with([
                'items',
                'items.drug',
                'pharmacy',
                'patient'
            ])->findOrFail($id);

            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$user->isAdmin() && (!$patient || $order->patient_id != $patient->id)) {
                return $this->error('شما دسترسی به این سفارش ندارید', 403);
            }

            $orderData = $this->orderService->getOrderStatus($order);

            // اضافه کردن اطلاعات تحویل
            $orderData['recipient_name'] = $order->recipient_name;
            $orderData['recipient_phone'] = $order->recipient_phone;
            $orderData['delivery_address'] = $order->delivery_address;
            $orderData['delivery_notes'] = $order->delivery_notes;
            $orderData['patient_name'] = $order->patient->full_name ?? null;

            return $this->success($orderData);

        } catch (\Exception $e) {
            Log::error('❌ Order show error: ' . $e->getMessage());
            return $this->error('سفارش یافت نشد', 404);
        }
    }

    /**
     * دریافت وضعیت سفارش
     */
    public function status($id)
    {
        try {
            $order = PharmacyOrder::findOrFail($id);

            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$user->isAdmin() && (!$patient || $order->patient_id != $patient->id)) {
                return $this->error('شما دسترسی به این سفارش ندارید', 403);
            }

            return $this->success([
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'status_color' => $order->status_color,
                'payment_status' => $order->payment_status,
                'payment_status_label' => $order->payment_status_label,
                'is_paid' => $order->is_paid,
                'total_amount' => $order->total_amount,
                'created_at' => $order->created_at,
                'paid_at' => $order->paid_at,
                'delivered_at' => $order->delivered_at,
            ]);

        } catch (\Exception $e) {
            return $this->error('سفارش یافت نشد', 404);
        }
    }

    /**
     * لیست سفارشات من (بیمار)
     */
    public function myOrders(Request $request)
    {
        try {
            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return $this->error('بیمار یافت نشد', 404);
            }

            $orders = $this->orderService->getPatientOrders($patient->id, $request->get('per_page', 15));

            // اضافه کردن اطلاعات تحویل به هر سفارش
            $orders->getCollection()->transform(function ($order) {
                $orderData = $order->toArray();
                $orderData['recipient_name'] = $order->recipient_name;
                $orderData['recipient_phone'] = $order->recipient_phone;
                $orderData['delivery_address'] = $order->delivery_address;
                $orderData['items_count'] = $order->items->count() ?? 0;
                return $orderData;
            });

            return $this->success($orders);

        } catch (\Exception $e) {
            Log::error('❌ My orders error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لیست سفارشات داروخانه (ادمین/داروخانه)
     */
    public function pharmacyOrders(Request $request)
    {
        try {
            $user = auth()->user();
            $pharmacy = Pharmacy::where('user_id', $user->id)->first();

            if (!$user->isAdmin() && !$pharmacy) {
                return $this->error('شما دسترسی به این بخش را ندارید', 403);
            }

            $pharmacyId = $pharmacy?->id ?? $request->pharmacy_id;
            $orders = $this->orderService->getPharmacyOrders($pharmacyId, $request->get('per_page', 15));

            return $this->success($orders);

        } catch (\Exception $e) {
            Log::error('❌ Pharmacy orders error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * شروع پرداخت سفارش
     */
    public function pay(Request $request, $identifier)
    {
        $request->validate([
            'gateway' => 'nullable|in:zarinpal,asanpardakht,local',
        ]);

        try {
            // ✅ پیدا کردن سفارش با ID یا order_number
            $order = PharmacyOrder::where('id', $identifier)
                ->orWhere('order_number', $identifier)
                ->first();

            if (!$order) {
                return $this->error('سفارش یافت نشد', 404);
            }

            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$user->isAdmin() && (!$patient || $order->patient_id != $patient->id)) {
                return $this->error('شما دسترسی به این سفارش ندارید', 403);
            }

            if ($order->is_paid) {
                return $this->error('این سفارش قبلاً پرداخت شده است', 400);
            }

            $gateway = $request->gateway ?? config('payment.default_gateway', 'local');
            $result = $this->orderService->initiatePayment($order, $gateway);

            if ($result['success']) {
                return $this->success($result, 'در حال انتقال به درگاه پرداخت...');
            }

            return $this->error($result['message'] ?? 'خطا در شروع پرداخت', 400);

        } catch (\Exception $e) {
            Log::error('❌ Payment initiation error: ' . $e->getMessage());
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

            // ✅ بررسی پرداخت شده بودن
            if ($order->is_paid || $order->payment_status === 'paid') {
                return $this->error('سفارش پرداخت شده قابل لغو نیست', 400);
            }

            // ✅ بررسی تحویل شده بودن
            if ($order->status === 'delivered') {
                return $this->error('سفارش تحویل شده قابل لغو نیست', 400);
            }

            // ✅ فقط سفارشات در انتظار پرداخت قابل لغو هستن
            if (!in_array($order->status, ['pending', 'payment_pending'])) {
                return $this->error('وضعیت فعلی سفارش اجازه لغو را نمی‌دهد', 400);
            }

            // ✅ آپدیت کامل
            $order->status = 'cancelled';
            $order->cancelled_at = now();
            $order->save();

            Log::info('✅ Order cancelled successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'old_status' => 'payment_pending',
                'new_status' => 'cancelled'
            ]);

            return $this->success(null, 'سفارش با موفقیت لغو شد');

        } catch (\Exception $e) {
            Log::error('❌ Order cancellation error: ' . $e->getMessage(), [
                'order_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * به‌روزرسانی سفارش
     */
    public function update(Request $request, $id)
    {
        try {
            $order = PharmacyOrder::findOrFail($id);

            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$user->isAdmin() && (!$patient || $order->patient_id != $patient->id)) {
                return $this->error('شما دسترسی به این سفارش ندارید', 403);
            }

            if ($order->is_paid || $order->status === 'delivered' || $order->status === 'cancelled') {
                return $this->error('سفارش قابل ویرایش نیست', 400);
            }

            $validated = $request->validate([
                'delivery_address' => 'nullable|string|max:500',
                'delivery_notes' => 'nullable|string|max:500',
                'recipient_name' => 'nullable|string|max:255',
                'recipient_phone' => 'nullable|string|max:20',
            ]);

            $order->update($validated);

            Log::info('✅ Order updated', ['order_id' => $order->id]);

            return $this->success($order, 'سفارش با موفقیت به‌روزرسانی شد');

        } catch (\Exception $e) {
            Log::error('❌ Order update error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نوتیفیکیشن‌های من
     */
    public function notifications(Request $request)
    {
        try {
            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return $this->error('بیمار یافت نشد', 404);
            }

            $notifications = \App\Models\PharmacyNotification::where('patient_id', $patient->id)
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return $this->success($notifications);

        } catch (\Exception $e) {
            Log::error('❌ Notifications error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
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
            Log::error('❌ Mark notification error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * دریافت آمار سفارشات
     */
    public function stats()
    {
        try {
            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return $this->error('بیمار یافت نشد', 404);
            }

            $stats = [
                'total' => PharmacyOrder::where('patient_id', $patient->id)->count(),
                'pending' => PharmacyOrder::where('patient_id', $patient->id)
                    ->where('status', 'pending')
                    ->count(),
                'processing' => PharmacyOrder::where('patient_id', $patient->id)
                    ->where('status', 'processing')
                    ->count(),
                'delivered' => PharmacyOrder::where('patient_id', $patient->id)
                    ->where('status', 'delivered')
                    ->count(),
                'cancelled' => PharmacyOrder::where('patient_id', $patient->id)
                    ->where('status', 'cancelled')
                    ->count(),
                'total_spent' => PharmacyOrder::where('patient_id', $patient->id)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount'),
            ];

            return $this->success($stats);

        } catch (\Exception $e) {
            Log::error('❌ Stats error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }
}
