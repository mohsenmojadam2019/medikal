<?php


namespace App\Http\Controllers\Admin\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantController extends Controller
{
    use ApiResponse;

    // ===== لیست کلینیک‌ها =====
    public function index(Request $request)
    {
        $tenants = Tenant::with(['creator', 'users'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return $this->success($tenants);
    }

    // ===== ایجاد کلینیک جدید =====
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'plan_id' => 'required|exists:subscription_plans,id',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_mobile' => 'required|regex:/^09[0-9]{9}$/|unique:users,mobile',
            'admin_password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            \DB::transaction(function () use ($request) {
                // ۱. ایجاد کلینیک
                $tenant = Tenant::create([
                    'name' => $request->name,
                    'slug' => \Illuminate\Support\Str::slug($request->name),
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'created_by' => auth()->id(),
                    'is_active' => true,
                    'is_verified' => false,
                ]);

                // ۲. ایجاد کاربر ادمین کلینیک
                $user = User::create([
                    'name' => $request->admin_name,
                    'email' => $request->admin_email,
                    'mobile' => $request->admin_mobile,
                    'password' => bcrypt($request->admin_password),
                    'is_active' => true,
                ]);

                // ۳. ارتباط کاربر با کلینیک
                $tenant->users()->attach($user->id, [
                    'role' => 'clinic_admin',
                    'is_active' => true,
                    'is_primary' => true,
                    'joined_at' => now(),
                ]);

                // ۴. ایجاد اشتراک
                $plan = SubscriptionPlan::find($request->plan_id);
                $subscription = $tenant->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'created_by' => auth()->id(),
                    'status' => 'active',
                    'start_date' => now(),
                    'end_date' => now()->addMonth(),
                    'amount_paid' => $plan->price_monthly,
                ]);

                // ۵. تنظیم محدودیت‌های کلینیک
                $tenant->update([
                    'max_doctors' => $plan->max_doctors,
                    'max_patients' => $plan->max_patients,
                    'max_appointments_per_day' => $plan->max_appointments_per_day,
                    'max_prescriptions_per_month' => $plan->max_prescriptions_per_month,
                    'features' => $plan->features,
                    'subscription_status' => 'active',
                    'subscription_expires_at' => now()->addMonth(),
                ]);

                // ۶. لاگ
                \App\Models\AuditLog::create([
                    'tenant_id' => $tenant->id,
                    'user_id' => auth()->id(),
                    'action' => 'tenant_created',
                    'model_type' => 'Tenant',
                    'model_id' => $tenant->id,
                    'new_values' => $tenant->toArray(),
                    'ip_address' => $request->ip(),
                ]);
            });

            return $this->success(null, 'کلینیک با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ===== نمایش کلینیک =====
    public function show($id)
    {
        $tenant = Tenant::with(['users', 'subscriptions.plan', 'creator'])
            ->findOrFail($id);

        return $this->success($tenant);
    }

    // ===== بروزرسانی کلینیک =====
    public function update(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $tenant->update($request->all());

            \App\Models\AuditLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => auth()->id(),
                'action' => 'tenant_updated',
                'model_type' => 'Tenant',
                'model_id' => $tenant->id,
                'old_values' => $tenant->getOriginal(),
                'new_values' => $tenant->toArray(),
                'ip_address' => $request->ip(),
            ]);

            return $this->success($tenant->fresh(), 'کلینیک با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ===== تغییر وضعیت کلینیک =====
    public function toggleStatus($id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['is_active' => !$tenant->is_active]);

        return $this->success($tenant, 'وضعیت کلینیک با موفقیت تغییر کرد');
    }

    // ===== آمار کلی پلتفرم =====
    public function stats()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('is_active', true)->count(),
            'total_users' => \App\Models\User::count(),
            'total_doctors' => \App\Models\Doctor::count(),
            'total_patients' => \App\Models\Patient::count(),
            'total_appointments' => \App\Models\Appointment::count(),
            'total_revenue' => \App\Models\PaymentTransaction::where('status', 'success')->sum('amount'),
            'subscriptions' => [
                'active' => \App\Models\Subscription::active()->count(),
                'expired' => \App\Models\Subscription::expired()->count(),
            ],
            'by_plan' => \App\Models\Subscription::selectRaw('plan_id, count(*) as total')
                ->groupBy('plan_id')
                ->with('plan')
                ->get()
                ->map(function ($item) {
                    return [
                        'plan_name' => $item->plan->name ?? 'نامشخص',
                        'total' => $item->total,
                    ];
                }),
        ];

        return $this->success($stats);
    }

    // ===== دریافت پلن‌های موجود =====
    public function plans()
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->get();

        return $this->success($plans);
    }
}
