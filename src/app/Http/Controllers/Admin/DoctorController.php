<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Doctor\DoctorService;
use App\Http\Requests\Admin\StoreDoctorRequest;
use App\Http\Requests\Admin\UpdateDoctorRequest;
use App\Traits\ApiResponse;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    use ApiResponse;

    protected DoctorService $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }

    /**
     * لیست پزشکان
     */
    public function index(Request $request)
    {
        $doctors = $this->doctorService->list($request->all(), $request->get('per_page', 15));
        return $this->success($doctors);
    }

    /**
     * ایجاد پزشک جدید
     */
    public function store(StoreDoctorRequest $request)
    {
        try {
            $doctor = $this->doctorService->create($request->validated());
            return $this->success($doctor, 'پزشک با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش پزشک
     */
    public function show($id)
    {
        try {
            $doctor = $this->doctorService->show($id);
            return $this->success($doctor);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }

    /**
     * به‌روزرسانی پزشک
     */
    public function update(UpdateDoctorRequest $request, $id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor = $this->doctorService->update($doctor, $request->validated());
            return $this->success($doctor, 'پزشک با موفقیت به‌روزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * حذف پزشک
     */
    public function destroy($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $this->doctorService->delete($doctor);
            return $this->success(null, 'پزشک با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تغییر وضعیت پزشک
     */
    public function toggleAvailability($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor = $this->doctorService->toggleAvailability($doctor);
            return $this->success($doctor, 'وضعیت پزشک با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * تایید پزشک
     */
    public function verify($id)
    {
        try {
            $doctor = Doctor::findOrFail($id);
            $doctor = $this->doctorService->verify($doctor);
            return $this->success($doctor, 'پزشک با موفقیت تایید شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * لیست پزشکان عمومی (بدون احراز هویت)
     */
    public function publicList(Request $request)
    {
        $doctors = $this->doctorService->publicList($request->all(), $request->get('per_page', 15));
        return $this->success($doctors);
    }

    /**
     * نمایش عمومی پزشک
     */
    public function publicShow($id)
    {
        try {
            $doctor = Doctor::with(['user', 'specialty', 'primaryAddress', 'schedules'])
                ->where('is_available', true)
                ->where('is_verified', true)
                ->findOrFail($id);
            return $this->success($doctor);
        } catch (\Exception $e) {
            return $this->error('پزشک یافت نشد', 404);
        }
    }
}
