<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PharmacyManagementController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
    }

    /**
     * لیست داروخانه‌ها
     */
    public function index(Request $request)
    {
        $query = Pharmacy::query();

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%")
                ->orWhere('license_number', 'LIKE', "%{$request->search}%")
                ->orWhere('phone', 'LIKE', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('is_online')) {
            $query->where('is_online', $request->is_online);
        }

        $pharmacies = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($pharmacies);
    }

    /**
     * ایجاد داروخانه جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'license_number' => 'required|string|unique:pharmacies,license_number',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'working_hours' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_online' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $pharmacy = Pharmacy::create($request->all());
            return $this->success($pharmacy, 'داروخانه با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش داروخانه
     */
    public function show($id)
    {
        try {
            $pharmacy = Pharmacy::with(['contracts'])->findOrFail($id);
            return $this->success($pharmacy);
        } catch (\Exception $e) {
            return $this->error('داروخانه یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی داروخانه
     */
    public function update(Request $request, $id)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('داروخانه یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'license_number' => 'sometimes|string|unique:pharmacies,license_number,' . $id,
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'working_hours' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'is_online' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $pharmacy->update($request->all());
            return $this->success($pharmacy->fresh(), 'داروخانه با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف داروخانه
     */
    public function destroy($id)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($id);
            $pharmacy->delete();
            return $this->success(null, 'داروخانه با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت داروخانه
     */
    public function toggleStatus($id)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($id);
            $pharmacy->update(['is_active' => !$pharmacy->is_active]);
            return $this->success($pharmacy->fresh(), 'وضعیت داروخانه با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت فروش آنلاین
     */
    public function toggleOnline($id)
    {
        try {
            $pharmacy = Pharmacy::findOrFail($id);
            $pharmacy->update(['is_online' => !$pharmacy->is_online]);
            return $this->success($pharmacy->fresh(), 'وضعیت فروش آنلاین با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
