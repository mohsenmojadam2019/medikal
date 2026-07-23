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
use Illuminate\Support\Facades\Validator;

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
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'paid',
                    'payment_gateway' => $gateway,
                    'payment_authority' => $transactionId,
                    'paid_at' => now(),
                ]);

                \Log::info('✅ Order updated to paid', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'new_status' => 'paid'
                ]);

                $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
                return redirect()->away($frontendUrl . '/fa/pharmacy/payment/callback?success=true&order_number=' . $orderNumber);
            } else {
                \Log::info('❌ Payment failed for order', ['order_id' => $order->id]);

                $order->update([
                    'payment_status' => 'failed',
                    'status' => 'failed',
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
     * ✅ جستجوی داروها (با فیلتر داروخانه)
     */
    public function search(Request $request)
    {
        $query = $request->query('q');
        $pharmacyId = $request->query('pharmacy_id');

        if (empty($query)) {
            return $this->success([], 'عبارت جستجو را وارد کنید');
        }

        $drugs = Drug::where('is_active', true);

        // ✅ فیلتر بر اساس داروخانه
        if ($pharmacyId) {
            $drugs->where('pharmacy_id', $pharmacyId);
        }

        $drugs = $drugs->where(function ($q) use ($query) {
            $q->where('generic_name', 'like', "%{$query}%")
                ->orWhere('name', 'like', "%{$query}%")
                ->orWhere('code', 'like', "%{$query}%")
                ->orWhere('category', 'like', "%{$query}%")
                ->orWhere('manufacturer', 'like', "%{$query}%");
        })
            ->orderBy('generic_name')
            ->paginate($request->per_page ?? 20);

        return $this->success($drugs, 'نتایج جستجو');
    }

    /**
     * ✅ لیست محصولات یک داروخانه خاص
     */
    public function products(Request $request, $pharmacyId = null)
    {
        $query = Drug::where('is_active', true);

        // ✅ فیلتر بر اساس داروخانه
        if ($pharmacyId) {
            $query->where('pharmacy_id', $pharmacyId)
                ->where('stock', '>', 0);
        }

        // فیلتر بر اساس دسته‌بندی
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // فیلتر بر اساس نیاز به نسخه
        if ($request->has('requires_prescription')) {
            $query->where('requires_prescription', $request->requires_prescription === 'true' || $request->requires_prescription === '1');
        }

        // جستجو در نام داروها
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%");
            });
        }

        $drugs = $query->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return $this->success($drugs, 'لیست محصولات');
    }

    /**
     * ✅ دریافت دسته‌بندی‌های داروهای یک داروخانه
     */
    public function categories(Request $request)
    {
        $pharmacyId = $request->query('pharmacy_id');

        $query = Drug::whereNotNull('category')
            ->where('is_active', true);

        // ✅ فیلتر بر اساس داروخانه
        if ($pharmacyId) {
            $query->where('pharmacy_id', $pharmacyId);
        }

        $categories = $query->distinct()
            ->pluck('category')
            ->toArray();

        return $this->success($categories);
    }

    /**
     * ✅ لیست داروخانه‌ها
     */
    public function index(Request $request)
    {
        $pharmacies = Pharmacy::active()
            ->online()
            ->when($request->has('search'), function ($query) use ($request) {
                return $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('address', 'like', "%{$request->search}%");
            })
            ->paginate($request->per_page ?? 20);

        return $this->success($pharmacies, 'لیست داروخانه‌ها');
    }

    /**
     * ✅ نمایش یک داروخانه با داروهایش
     */
    public function showPharmacy($id)
    {
        try {
            $pharmacy = Pharmacy::with(['province', 'city', 'clinic'])
                ->findOrFail($id);

            // دریافت داروهای موجود
            $drugs = Drug::where('pharmacy_id', $id)
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->limit(10)
                ->get();

            return $this->success([
                'pharmacy' => $pharmacy,
                'drugs' => $drugs,
                'drugs_count' => Drug::where('pharmacy_id', $id)->where('is_active', true)->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('داروخانه یافت نشد', 404);
        }
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

            // تنظیم pharmacy_id پیش‌فرض
            if (!isset($data['pharmacy_id']) || empty($data['pharmacy_id'])) {
                $pharmacy = Pharmacy::active()->online()->first();
                $data['pharmacy_id'] = $pharmacy?->id ?? 1;
            }

            // ✅ بررسی موجودی داروها قبل از ثبت سفارش
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $drug = Drug::find($item['drug_id']);
                    if (!$drug) {
                        return $this->error("دارو با شناسه {$item['drug_id']} یافت نشد", 404);
                    }

                    // ✅ بررسی اینکه دارو متعلق به داروخانه انتخابی است
                    if ($drug->pharmacy_id != $data['pharmacy_id']) {
                        return $this->error("دارو {$drug->name} در داروخانه انتخابی موجود نیست", 400);
                    }

                    // ✅ بررسی موجودی کافی
                    if ($drug->stock < $item['quantity']) {
                        return $this->error("موجودی دارو {$drug->name} کافی نیست. موجودی: {$drug->stock}", 400);
                    }
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
            $data['recipient_name'] = $request->recipient_name ?? $user->name;
            $data['recipient_phone'] = $request->recipient_phone ?? $user->mobile;
            $data['delivery_address'] = $request->delivery_address;
            $data['delivery_notes'] = $request->delivery_notes ?? null;
            $data['status'] = 'payment_pending';
            $data['payment_status'] = 'pending';

            Log::info('📦 Creating order', [
                'patient_id' => $patient->id,
                'pharmacy_id' => $data['pharmacy_id'],
                'recipient_name' => $data['recipient_name'],
                'total_amount' => $data['total_amount']
            ]);

            $order = $this->orderService->createOrder($data);

            // ============================================================
            // ✅ اگر روش پرداخت gateway بود، لینک پرداخت رو هم برگردون
            // ============================================================
            $paymentLink = null;
            if ($request->payment_method === 'gateway' && !empty($request->gateway)) {
                $gateway = $request->gateway ?? 'local';
                $result = $this->orderService->initiatePayment($order, $gateway);

                if ($result['success']) {
                    $paymentLink = $result['redirect_url'] ?? $result['payment_link'] ?? null;
                }

                Log::info('💳 Payment link generated', [
                    'order_id' => $order->id,
                    'gateway' => $gateway,
                    'payment_link' => $paymentLink
                ]);
            }

            $orderData = $this->orderService->getOrderStatus($order);
            $orderData['recipient_name'] = $order->recipient_name;
            $orderData['recipient_phone'] = $order->recipient_phone;
            $orderData['delivery_address'] = $order->delivery_address;
            $orderData['delivery_notes'] = $order->delivery_notes;
            $orderData['payment_link'] = $paymentLink;

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

            if ($order->is_paid || $order->payment_status === 'paid') {
                return $this->error('سفارش پرداخت شده قابل لغو نیست', 400);
            }

            if ($order->status === 'delivered') {
                return $this->error('سفارش تحویل شده قابل لغو نیست', 400);
            }

            if (!in_array($order->status, ['pending', 'payment_pending'])) {
                return $this->error('وضعیت فعلی سفارش اجازه لغو را نمی‌دهد', 400);
            }

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

    // ============================================
    // ✅ آپلود نسخه پزشکی (کاربر)
    // ============================================
    public function uploadPrescription(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return $this->error('بیمار یافت نشد', 404);
            }

            $order = PharmacyOrder::where('id', $id)
                ->where('patient_id', $patient->id)
                ->first();

            if (!$order) {
                return $this->error('سفارش یافت نشد', 404);
            }

            if ($order->status !== 'payment_pending') {
                return $this->error('این سفارش قابل آپلود نسخه نیست', 400);
            }

            $validator = Validator::make($request->all(), [
                'prescription' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

            if ($validator->fails()) {
                return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
            }

            $file = $request->file('prescription');
            $path = $file->store('prescriptions/' . $order->id, 'public');

            $order->update([
                'prescription_file' => $path,
                'prescription_status' => 'pending',
            ]);

            $this->sendPrescriptionNotification($order, 'uploaded');

            Log::info('📄 Prescription uploaded', [
                'order_id' => $order->id,
                'file_path' => $path,
                'patient_id' => $patient->id,
            ]);

            return $this->success([
                'order_id' => $order->id,
                'prescription_file' => $path,
                'prescription_status' => 'pending',
                'message' => 'نسخه با موفقیت آپلود شد. در انتظار تایید ادمین...',
            ], 'نسخه با موفقیت آپلود شد');

        } catch (\Exception $e) {
            Log::error('❌ Upload prescription error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // ✅ تایید نسخه (ادمین)
    // ============================================
    public function approvePrescription(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if (!$user->isAdmin()) {
                return $this->error('شما دسترسی به این بخش را ندارید', 403);
            }

            $order = PharmacyOrder::findOrFail($id);

            if ($order->prescription_status !== 'pending') {
                return $this->error('این سفارش در وضعیت تایید نسخه نیست', 400);
            }

            $order->update([
                'prescription_status' => 'approved',
                'prescription_approved_at' => now(),
                'prescription_approved_by' => $user->id,
                'status' => 'payment_pending',
            ]);

            $this->sendPrescriptionSms($order, 'approved');
            $this->sendPrescriptionNotification($order, 'approved');

            Log::info('✅ Prescription approved', [
                'order_id' => $order->id,
                'admin_id' => $user->id,
            ]);

            return $this->success([
                'order_id' => $order->id,
                'prescription_status' => 'approved',
                'message' => 'نسخه با موفقیت تایید شد. کاربر می‌تواند پرداخت کند.',
            ], 'نسخه تایید شد');

        } catch (\Exception $e) {
            Log::error('❌ Approve prescription error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // ✅ رد نسخه (ادمین)
    // ============================================
    public function rejectPrescription(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if (!$user->isAdmin()) {
                return $this->error('شما دسترسی به این بخش را ندارید', 403);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
            }

            $order = PharmacyOrder::findOrFail($id);

            if ($order->prescription_status !== 'pending') {
                return $this->error('این سفارش در وضعیت تایید نسخه نیست', 400);
            }

            $order->update([
                'prescription_status' => 'rejected',
                'prescription_reject_reason' => $request->reason,
                'prescription_rejected_at' => now(),
                'status' => 'cancelled',
            ]);

            $this->sendPrescriptionSms($order, 'rejected', $request->reason);
            $this->sendPrescriptionNotification($order, 'rejected', $request->reason);

            Log::info('❌ Prescription rejected', [
                'order_id' => $order->id,
                'admin_id' => $user->id,
                'reason' => $request->reason,
            ]);

            return $this->success([
                'order_id' => $order->id,
                'prescription_status' => 'rejected',
                'message' => 'نسخه با موفقیت رد شد. پیامک به کاربر ارسال شد.',
            ], 'نسخه رد شد');

        } catch (\Exception $e) {
            Log::error('❌ Reject prescription error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // ✅ دریافت وضعیت نسخه
    // ============================================
    public function getPrescriptionStatus($id)
    {
        try {
            $order = PharmacyOrder::findOrFail($id);

            $user = auth()->user();
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$user->isAdmin() && (!$patient || $order->patient_id != $patient->id)) {
                return $this->error('شما دسترسی به این سفارش ندارید', 403);
            }

            return $this->success([
                'order_id' => $order->id,
                'prescription_status' => $order->prescription_status,
                'prescription_status_label' => $order->prescription_status_label,
                'prescription_file' => $order->prescription_file_url,
                'prescription_reject_reason' => $order->prescription_reject_reason,
                'prescription_approved_at' => $order->prescription_approved_at,
                'prescription_rejected_at' => $order->prescription_rejected_at,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Get prescription status error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 404);
        }
    }

    // ============================================
    // ✅ ارسال پیامک نسخه
    // ============================================
    private function sendPrescriptionSms(PharmacyOrder $order, string $status, ?string $reason = null): void
    {
        try {
            $patient = $order->patient;
            $phone = $patient->phone ?? $patient->user->mobile ?? null;

            if (!$phone) {
                Log::warning('No phone number found for patient', ['patient_id' => $patient->id]);
                return;
            }

            $messages = [
                'approved' => "✅ نسخه پزشکی سفارش {$order->order_number} تایید شد. لطفاً برای تکمیل سفارش، پرداخت را انجام دهید.\nکلینیک‌یار",
                'rejected' => "❌ نسخه پزشکی سفارش {$order->order_number} رد شد.\nدلیل: {$reason}\nلطفاً با پشتیبانی تماس بگیرید.\nکلینیک‌یار",
            ];

            $message = $messages[$status] ?? 'وضعیت نسخه شما تغییر کرد.';
            // app(SmsManager::class)->send($phone, $message);

            Log::info('📱 Prescription SMS sent', [
                'order_id' => $order->id,
                'phone' => $phone,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Send prescription SMS error: ' . $e->getMessage());
        }
    }

    // ============================================
    // ✅ ارسال نوتیفیکیشن نسخه
    // ============================================
    private function sendPrescriptionNotification(PharmacyOrder $order, string $action, ?string $reason = null): void
    {
        try {
            $titles = [
                'uploaded' => '📄 نسخه جدید آپلود شد',
                'approved' => '✅ نسخه تایید شد',
                'rejected' => '❌ نسخه رد شد',
            ];

            $messages = [
                'uploaded' => "نسخه پزشکی سفارش {$order->order_number} توسط بیمار آپلود شد. لطفاً بررسی کنید.",
                'approved' => "نسخه پزشکی سفارش {$order->order_number} تایید شد. کاربر می‌تواند پرداخت کند.",
                'rejected' => "نسخه پزشکی سفارش {$order->order_number} رد شد.\nدلیل: {$reason}",
            ];

            if ($action === 'uploaded') {
                $admins = \App\Models\User::role('admin')->get();
                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'prescription',
                        'title' => $titles[$action] ?? 'نسخه پزشکی',
                        'body' => $messages[$action] ?? '',
                        'data' => ['order_id' => $order->id],
                        'priority' => 'high',
                        'sent_at' => now(),
                    ]);
                }
            }

            if ($action === 'approved' || $action === 'rejected') {
                \App\Models\PharmacyNotification::create([
                    'tenant_id' => session('tenant_id'),
                    'patient_id' => $order->patient_id,
                    'order_id' => $order->id,
                    'type' => 'panel',
                    'title' => $titles[$action] ?? 'وضعیت نسخه',
                    'message' => $messages[$action] ?? '',
                    'data' => ['order_id' => $order->id],
                    'is_read' => false,
                    'sent_at' => now(),
                ]);
            }

            Log::info('📨 Prescription notification sent', [
                'order_id' => $order->id,
                'action' => $action,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Send prescription notification error: ' . $e->getMessage());
        }
    }
}
