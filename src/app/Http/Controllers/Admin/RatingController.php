<?php
// app/Http/Controllers/Admin/RatingController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    use ApiResponse;

    /**
     * لیست نظرات و امتیازات
     */
    public function index(Request $request)
    {
        $tenantId = session('tenant_id', 1);

        $query = Rating::where('tenant_id', $tenantId)
            ->with([
                'patient',
                'patient.user',
                'doctor',
                'doctor.user',
                'appointment'
            ]);

        // فیلتر بر اساس امتیاز (score)
        if ($request->has('rating')) {
            $query->where('score', $request->rating);
        }

        // فیلتر بر اساس پزشک
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // فیلتر بر اساس بیمار
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // فیلتر بر اساس پاسخ داده شده
        if ($request->has('has_reply')) {
            if ($request->has_reply) {
                $query->whereNotNull('reply');
            } else {
                $query->whereNull('reply');
            }
        }

        // جستجو
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('doctor', function ($q2) use ($search) {
                        $q2->where('full_name', 'like', "%{$search}%");
                    });
            });
        }

        $ratings = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($ratings);
    }

    /**
     * نمایش نظر
     */
    public function show($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $rating = Rating::where('tenant_id', $tenantId)
                ->with([
                    'patient',
                    'patient.user',
                    'doctor',
                    'doctor.user',
                    'appointment'
                ])
                ->findOrFail($id);
            return $this->success($rating);
        } catch (\Exception $e) {
            return $this->error('نظر یافت نشد', 404);
        }
    }

    /**
     * تایید نظر
     * (اگر در مدل شما فیلد is_approved وجود نداره، این متد رو حذف کن)
     */
    public function approve($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $rating = Rating::where('tenant_id', $tenantId)->findOrFail($id);

            // اگر فیلد is_approved در مدل وجود نداره، از این متد استفاده نکن
            // یا فیلد رو به مدل اضافه کن
            if (in_array('is_approved', (new Rating())->getFillable())) {
                $rating->update(['is_approved' => true]);
            }

            return $this->success($rating->fresh(), 'نظر با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * رد نظر
     */
    public function reject($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $rating = Rating::where('tenant_id', $tenantId)->findOrFail($id);

            if (in_array('is_approved', (new Rating())->getFillable())) {
                $rating->update(['is_approved' => false]);
            }

            return $this->success($rating->fresh(), 'نظر با موفقیت رد شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف نظر
     */
    public function destroy($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $rating = Rating::where('tenant_id', $tenantId)->findOrFail($id);
            $rating->delete();
            return $this->success(null, 'نظر با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * پاسخ به نظر
     */
    public function reply(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $tenantId = session('tenant_id', 1);
            $rating = Rating::where('tenant_id', $tenantId)->findOrFail($id);
            $rating->update([
                'reply' => $request->reply,
                'replied_at' => now(),
            ]);
            return $this->success($rating->fresh(), 'پاسخ با موفقیت ثبت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف پاسخ
     */
    public function deleteReply($id)
    {
        try {
            $tenantId = session('tenant_id', 1);
            $rating = Rating::where('tenant_id', $tenantId)->findOrFail($id);
            $rating->update([
                'reply' => null,
                'replied_at' => null,
            ]);
            return $this->success($rating->fresh(), 'پاسخ با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * آمار نظرات
     */
    public function stats()
    {
        try {
            $tenantId = session('tenant_id', 1);

            $total = Rating::where('tenant_id', $tenantId)->count();
            $withReply = Rating::where('tenant_id', $tenantId)->whereNotNull('reply')->count();
            $withoutReply = Rating::where('tenant_id', $tenantId)->whereNull('reply')->count();
            $average = Rating::where('tenant_id', $tenantId)->avg('score');

            // توزیع امتیازات (بر اساس score)
            $distribution = [];
            for ($i = 1; $i <= 5; $i++) {
                $distribution[$i] = Rating::where('tenant_id', $tenantId)
                    ->where('score', $i)
                    ->count();
            }

            return $this->success([
                'total' => $total,
                'with_reply' => $withReply,
                'without_reply' => $withoutReply,
                'average_rating' => round($average ?? 0, 1),
                'satisfaction_rate' => $total > 0 ? round((($distribution[4] + $distribution[5]) / $total) * 100) : 0,
                'distribution' => $distribution,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
