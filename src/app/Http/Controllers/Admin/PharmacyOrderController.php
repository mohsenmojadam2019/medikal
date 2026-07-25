<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PharmacyOrder;
use App\Models\PharmacyNotification;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PharmacyOrderController extends Controller
{
    use ApiResponse;

    /**
     * لیست تمام سفارشات داروخانه (ادمین)
     */
    public function index(Request $request)
    {
        try {
            $query = PharmacyOrder::with([
                'patient',
                'patient.user',
                'pharmacy',
                'items',
                'items.drug'
            ]);

            // فیلتر بر اساس وضعیت
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // فیلتر بر اساس وضعیت نسخه
            if ($request->has('prescription_status') && $request->prescription_status !== 'all') {
                $query->where('prescription_status', $request->prescription_status);
            }

            // فیلتر بر اساس بیمار
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            // فیلتر بر اساس داروخانه
            if ($request->has('pharmacy_id')) {
                $query->where('pharmacy_id', $request->pharmacy_id);
            }

            // جستجو
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($q2) use ($search) {
                            $q2->where('full_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('patient.user', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%");
                        });
                });
            }

            // فیلتر تاریخ
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            // اضافه کردن اطلاعات تکمیلی
            $orders->getCollection()->transform(function ($order) {
                $order->prescription_file_url = $order->prescription_file ? Storage::url($order->prescription_file) : null;
                $order->items_count = $order->items->count();
                return $order;
            });

            return $this->success($orders);

        } catch (\Exception $e) {
            Log::error('❌ Admin orders list error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * نمایش جزئیات یک سفارش (ادمین)
     */
    public function show($id)
    {
        try {
            $order = PharmacyOrder::with([
                'patient',
                'patient.user',
                'pharmacy',
                'items',
                'items.drug',
                'notifications'
            ])->findOrFail($id);

            $order->prescription_file_url = $order->prescription_file ? Storage::url($order->prescription_file) : null;

            return $this->success($order);

        } catch (\Exception $e) {
            Log::error('❌ Admin order show error: ' . $e->getMessage());
            return $this->error('سفارش یافت نشد', 404);
        }
    }

    /**
     * بروزرسانی وضعیت سفارش (ادمین)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:payment_pending,paid,processing,preparing,ready,shipped,delivered,cancelled',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
            }

            $order = PharmacyOrder::findOrFail($id);
            $oldStatus = $order->status;
            $newStatus = $request->status;

            // اعتبارسنجی تغییر وضعیت
            $allowedTransitions = [
                'payment_pending' => ['paid', 'cancelled'],
                'paid' => ['processing', 'cancelled'],
                'processing' => ['preparing', 'shipped', 'cancelled'],
                'preparing' => ['ready', 'shipped', 'cancelled'],
                'ready' => ['shipped', 'cancelled'],
                'shipped' => ['delivered', 'cancelled'],
                'delivered' => [],
                'cancelled' => [],
            ];

            if (!in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
                return $this->error("تغییر وضعیت از {$oldStatus} به {$newStatus} مجاز نیست", 400);
            }

            // آپدیت وضعیت
            $order->update([
                'status' => $newStatus,
                'notes' => $request->notes ?? $order->notes,
                'confirmed_at' => $newStatus === 'paid' ? now() : $order->confirmed_at,
                'ready_at' => $newStatus === 'ready' ? now() : $order->ready_at,
                'delivered_at' => $newStatus === 'delivered' ? now() : $order->delivered_at,
            ]);

            // ارسال نوتیفیکیشن به بیمار
            $this->sendStatusNotification($order, $oldStatus, $newStatus);

            Log::info('✅ Admin order status updated', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'admin_id' => auth()->id(),
            ]);

            return $this->success([
                'order' => $order->fresh(),
                'message' => "وضعیت سفارش با موفقیت به {$order->status_label} تغییر کرد",
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Admin update status error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید نسخه پزشکی (ادمین)
     */
    public function approvePrescription($id)
    {
        try {
            $order = PharmacyOrder::findOrFail($id);

            if ($order->prescription_status !== 'pending') {
                return $this->error('این سفارش در وضعیت تایید نسخه نیست', 400);
            }

            $order->update([
                'prescription_status' => 'approved',
                'prescription_approved_at' => now(),
                'prescription_approved_by' => auth()->id(),
                'status' => 'payment_pending',
            ]);

            $this->sendPrescriptionSms($order, 'approved');
            $this->sendPrescriptionNotification($order, 'approved');

            Log::info('✅ Admin prescription approved', [
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
            ]);

            return $this->success([
                'order_id' => $order->id,
                'prescription_status' => 'approved',
                'message' => 'نسخه با موفقیت تایید شد. کاربر می‌تواند پرداخت کند.',
            ], 'نسخه تایید شد');

        } catch (\Exception $e) {
            Log::error('❌ Admin approve prescription error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * رد نسخه پزشکی (ادمین)
     */
    public function rejectPrescription(Request $request, $id)
    {
        try {
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

            Log::info('❌ Admin prescription rejected', [
                'order_id' => $order->id,
                'admin_id' => auth()->id(),
                'reason' => $request->reason,
            ]);

            return $this->success([
                'order_id' => $order->id,
                'prescription_status' => 'rejected',
                'message' => 'نسخه با موفقیت رد شد. پیامک به کاربر ارسال شد.',
            ], 'نسخه رد شد');

        } catch (\Exception $e) {
            Log::error('❌ Admin reject prescription error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * ارسال پیام به کاربر (ادمین)
     */
    public function sendMessage(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:500',
                'send_sms' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
            }

            $order = PharmacyOrder::findOrFail($id);
            $patient = $order->patient;

            $notification = PharmacyNotification::create([
                'tenant_id' => session('tenant_id'),
                'patient_id' => $patient->id,
                'order_id' => $order->id,
                'type' => 'admin_message',
                'title' => '📩 پیام از ادمین',
                'message' => $request->message,
                'data' => ['order_id' => $order->id],
                'is_read' => false,
                'sent_at' => now(),
            ]);

            if ($request->send_sms) {
                $this->sendAdminSms($order, $request->message);
            }

            Log::info('📩 Admin message sent to patient', [
                'order_id' => $order->id,
                'patient_id' => $patient->id,
                'admin_id' => auth()->id(),
            ]);

            return $this->success([
                'notification' => $notification,
                'message' => 'پیام با موفقیت ارسال شد',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Admin send message error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت سفارشات رها شده (سبد رها شده)
     */
    public function abandonedCarts(Request $request)
    {
        try {
            $orders = PharmacyOrder::where('status', 'payment_pending')
                ->where('created_at', '<', now()->subHours(24))
                ->with(['patient', 'patient.user', 'items', 'items.drug'])
                ->orderBy('created_at', 'asc')
                ->paginate($request->get('per_page', 20));

            $orders->getCollection()->transform(function ($order) {
                $order->hours_pending = now()->diffInHours($order->created_at);
                return $order;
            });

            return $this->success([
                'orders' => $orders,
                'total' => PharmacyOrder::where('status', 'payment_pending')
                    ->where('created_at', '<', now()->subHours(24))
                    ->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Admin abandoned carts error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * آمار سفارشات (ادمین)
     */
    public function stats()
    {
        try {
            $stats = [
                'total_orders' => PharmacyOrder::count(),
                'pending_payment' => PharmacyOrder::where('status', 'payment_pending')->count(),
                'paid' => PharmacyOrder::where('status', 'paid')->count(),
                'processing' => PharmacyOrder::where('status', 'processing')->count(),
                'shipped' => PharmacyOrder::where('status', 'shipped')->count(),
                'delivered' => PharmacyOrder::where('status', 'delivered')->count(),
                'cancelled' => PharmacyOrder::where('status', 'cancelled')->count(),
                'prescription_pending' => PharmacyOrder::where('prescription_status', 'pending')->count(),
                'prescription_approved' => PharmacyOrder::where('prescription_status', 'approved')->count(),
                'prescription_rejected' => PharmacyOrder::where('prescription_status', 'rejected')->count(),
                'total_revenue' => PharmacyOrder::where('payment_status', 'paid')->sum('total_amount'),
                'abandoned_carts' => PharmacyOrder::where('status', 'payment_pending')
                    ->where('created_at', '<', now()->subHours(24))
                    ->count(),
                'today_orders' => PharmacyOrder::whereDate('created_at', today())->count(),
                'today_revenue' => PharmacyOrder::whereDate('paid_at', today())
                    ->where('payment_status', 'paid')
                    ->sum('total_amount'),
            ];

            return $this->success($stats);

        } catch (\Exception $e) {
            Log::error('❌ Admin stats error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * حذف سفارش (ادمین)
     */
    public function destroy($id)
    {
        try {
            $order = PharmacyOrder::findOrFail($id);

            if (!in_array($order->status, ['cancelled', 'delivered'])) {
                return $this->error('فقط سفارشات لغو شده یا تحویل شده قابل حذف هستند', 400);
            }

            $order->delete();

            Log::info('🗑️ Admin order deleted', [
                'order_id' => $id,
                'admin_id' => auth()->id(),
            ]);

            return $this->success(null, 'سفارش با موفقیت حذف شد');

        } catch (\Exception $e) {
            Log::error('❌ Admin delete order error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================
    // ✅ متدهای کمکی خصوصی
    // ============================================

    private function sendStatusNotification(PharmacyOrder $order, string $oldStatus, string $newStatus): void
    {
        try {
            $statusMessages = [
                'paid' => 'سفارش شما پرداخت شد و در حال آماده‌سازی است',
                'processing' => 'سفارش شما در حال پردازش است',
                'preparing' => 'سفارش شما در حال آماده‌سازی است',
                'ready' => 'سفارش شما آماده ارسال است',
                'shipped' => 'سفارش شما ارسال شد',
                'delivered' => 'سفارش شما با موفقیت تحویل داده شد',
                'cancelled' => 'سفارش شما لغو شد',
            ];

            $message = $statusMessages[$newStatus] ?? "وضعیت سفارش شما به {$order->status_label} تغییر کرد";

            PharmacyNotification::create([
                'tenant_id' => session('tenant_id'),
                'patient_id' => $order->patient_id,
                'order_id' => $order->id,
                'type' => 'status_update',
                'title' => '🔄 تغییر وضعیت سفارش',
                'message' => "سفارش {$order->order_number}: {$message}",
                'data' => [
                    'order_id' => $order->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
                'is_read' => false,
                'sent_at' => now(),
            ]);

            $this->sendStatusSms($order, $message);

        } catch (\Exception $e) {
            Log::error('❌ Send status notification error: ' . $e->getMessage());
        }
    }

    private function sendStatusSms(PharmacyOrder $order, string $message): void
    {
        try {
            $patient = $order->patient;
            $phone = $patient->phone ?? $patient->user->mobile ?? null;

            if (!$phone) {
                return;
            }

            $smsMessage = "🔄 سفارش {$order->order_number}\n{$message}\nکلینیک‌یار";
            // app(SmsManager::class)->send($phone, $smsMessage);

            Log::info('📱 Status SMS sent', [
                'order_id' => $order->id,
                'phone' => $phone,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Send status SMS error: ' . $e->getMessage());
        }
    }

    private function sendPrescriptionSms(PharmacyOrder $order, string $status, ?string $reason = null): void
    {
        try {
            $patient = $order->patient;
            $phone = $patient->phone ?? $patient->user->mobile ?? null;

            if (!$phone) {
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

    private function sendPrescriptionNotification(PharmacyOrder $order, string $action, ?string $reason = null): void
    {
        try {
            $titles = [
                'approved' => '✅ نسخه تایید شد',
                'rejected' => '❌ نسخه رد شد',
            ];

            $messages = [
                'approved' => "نسخه پزشکی سفارش {$order->order_number} تایید شد. کاربر می‌تواند پرداخت کند.",
                'rejected' => "نسخه پزشکی سفارش {$order->order_number} رد شد.\nدلیل: {$reason}",
            ];

            PharmacyNotification::create([
                'tenant_id' => session('tenant_id'),
                'patient_id' => $order->patient_id,
                'order_id' => $order->id,
                'type' => 'prescription',
                'title' => $titles[$action] ?? 'وضعیت نسخه',
                'message' => $messages[$action] ?? '',
                'data' => ['order_id' => $order->id],
                'is_read' => false,
                'sent_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Send prescription notification error: ' . $e->getMessage());
        }
    }

    private function sendAdminSms(PharmacyOrder $order, string $message): void
    {
        try {
            $patient = $order->patient;
            $phone = $patient->phone ?? $patient->user->mobile ?? null;

            if (!$phone) {
                return;
            }

            $smsMessage = "📩 پیام از ادمین برای سفارش {$order->order_number}:\n{$message}\nکلینیک‌یار";
            // app(SmsManager::class)->send($phone, $smsMessage);

            Log::info('📱 Admin SMS sent', [
                'order_id' => $order->id,
                'phone' => $phone,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Send admin SMS error: ' . $e->getMessage());
        }
    }
}
