<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DrugController extends Controller
{
    use ApiResponse;

    /**
     * لیست داروها
     */
    public function index(Request $request)
    {
        $query = Drug::query();

        // جستجو
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // فیلتر بر اساس دسته‌بندی
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // فیلتر بر اساس نیاز به نسخه
        if ($request->has('requires_prescription')) {
            if ($request->requires_prescription) {
                $query->requiresPrescription();
            } else {
                $query->overTheCounter();
            }
        }

        // فیلتر بر اساس موجودی
        if ($request->has('in_stock')) {
            if ($request->in_stock) {
                $query->where('stock', '>', 0);
            } else {
                $query->where('stock', '<=', 0);
            }
        }

        // فیلتر بر اساس فعال
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $drugs = $query->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return $this->success($drugs);
    }

    /**
     * ایجاد دارو جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'code' => 'nullable|string|unique:drugs,code',
            'category' => 'nullable|string|max:100',
            'form' => 'nullable|string|max:50',
            'strength' => 'nullable|string|max:50',
            'manufacturer' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'requires_prescription' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            
            // اگر کد وارد نشده، خودکار تولید کن
            if (empty($data['code'])) {
                $drug = new Drug();
                $data['code'] = $drug->generateCode();
            }

            $drug = Drug::create($data);

            return $this->success($drug, 'دارو با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش یک دارو
     */
    public function show($id)
    {
        try {
            $drug = Drug::findOrFail($id);
            return $this->success($drug);
        } catch (\Exception $e) {
            return $this->error('دارو یافت نشد', 404);
        }
    }

    /**
     * بروزرسانی دارو
     */
    public function update(Request $request, $id)
    {
        try {
            $drug = Drug::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('دارو یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'code' => 'sometimes|string|unique:drugs,code,' . $id,
            'category' => 'nullable|string|max:100',
            'form' => 'nullable|string|max:50',
            'strength' => 'nullable|string|max:50',
            'manufacturer' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'requires_prescription' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $drug->update($request->all());
            return $this->success($drug->fresh(), 'دارو با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف دارو
     */
    public function destroy($id)
    {
        try {
            $drug = Drug::findOrFail($id);
            $drug->delete();
            return $this->success(null, 'دارو با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت دارو
     */
    public function toggleStatus($id)
    {
        try {
            $drug = Drug::findOrFail($id);
            $drug->update(['is_active' => !$drug->is_active]);
            return $this->success($drug->fresh(), 'وضعیت دارو با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * افزایش موجودی
     */
    public function increaseStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $drug = Drug::findOrFail($id);
            $drug->increaseStock($request->quantity);
            return $this->success($drug->fresh(), 'موجودی با موفقیت افزایش یافت');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * کاهش موجودی
     */
    public function decreaseStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $drug = Drug::findOrFail($id);
            $result = $drug->decreaseStock($request->quantity);
            
            if (!$result) {
                return $this->error('موجودی کافی نیست', 400);
            }

            return $this->success($drug->fresh(), 'موجودی با موفقیت کاهش یافت');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * جستجوی دارو (عمومی)
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $drugs = Drug::active()
            ->search($request->q)
            ->limit($request->get('limit', 20))
            ->get(['id', 'name', 'generic_name', 'code', 'strength', 'price', 'stock']);

        return $this->success($drugs);
    }

    /**
     * لیست دسته‌بندی‌ها
     */
    public function categories()
    {
        $categories = Drug::distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->toArray();

        return $this->success($categories);
    }

    /**
     * لیست داروهای فعال (عمومی)
     */
    public function activeDrugs(Request $request)
    {
        $drugs = Drug::active()
            ->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return $this->success($drugs);
    }
}
