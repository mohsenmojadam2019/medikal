<?php

namespace App\Http\Controllers\Api\OR;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ORController extends Controller
{
    use ApiResponse;

    /**
     * لیست اتاق‌های عمل
     */
    public function rooms(Request $request)
    {
        return $this->success([
            'rooms' => [
                ['id' => 1, 'name' => 'اتاق عمل ۱', 'type' => 'general', 'floor' => 2, 'is_active' => true],
                ['id' => 2, 'name' => 'اتاق عمل ۲', 'type' => 'general', 'floor' => 2, 'is_active' => true],
                ['id' => 3, 'name' => 'اتاق عمل VIP', 'type' => 'vip', 'floor' => 3, 'is_active' => true],
            ]
        ]);
    }

    /**
     * ایجاد اتاق عمل جدید
     */
    public function storeRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:general,vip,emergency',
            'floor' => 'required|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'equipment' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        return $this->success([
            'id' => rand(1, 100),
            'name' => $request->name,
            'type' => $request->type,
            'floor' => $request->floor,
            'capacity' => $request->capacity ?? 1,
            'equipment' => $request->equipment ?? [],
            'is_active' => $request->is_active ?? true,
        ], 'اتاق عمل با موفقیت ایجاد شد');
    }

    /**
     * نمایش یک اتاق عمل
     */
    public function showRoom($id)
    {
        return $this->success([
            'id' => $id,
            'name' => 'اتاق عمل ' . $id,
            'type' => 'general',
            'floor' => 2,
            'capacity' => 1,
            'equipment' => ['ventilator', 'monitor', 'anesthesia_machine'],
            'is_active' => true,
        ]);
    }

    /**
     * بروزرسانی اتاق عمل
     */
    public function updateRoom(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:general,vip,emergency',
            'floor' => 'nullable|integer|min:0',
            'capacity' => 'nullable|integer|min:1',
            'equipment' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        return $this->success([
            'id' => $id,
            'name' => $request->name ?? 'اتاق عمل ' . $id,
            'type' => $request->type ?? 'general',
            'floor' => $request->floor ?? 2,
            'capacity' => $request->capacity ?? 1,
            'equipment' => $request->equipment ?? ['ventilator', 'monitor'],
            'is_active' => $request->is_active ?? true,
        ], 'اتاق عمل با موفقیت بروزرسانی شد');
    }

    /**
     * حذف اتاق عمل
     */
    public function deleteRoom($id)
    {
        return $this->success(null, 'اتاق عمل با موفقیت حذف شد');
    }

    /**
     * لیست زمان‌بندی‌های جراحی
     */
    public function schedules(Request $request)
    {
        $date = $request->date ?? date('Y-m-d');

        return $this->success([
            'schedules' => [
                [
                    'id' => 1,
                    'room_id' => 1,
                    'patient_id' => 1,
                    'doctor_id' => 1,
                    'surgery_type' => 'appendectomy',
                    'diagnosis' => 'آپاندیسیت حاد',
                    'priority' => 'urgent',
                    'scheduled_date' => $date,
                    'scheduled_time' => '10:00',
                    'estimated_duration' => 60,
                    'status' => 'scheduled',
                ]
            ]
        ]);
    }

    /**
     * ایجاد زمان‌بندی جدید
     */
    public function storeSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'patient_id' => 'required|integer',
            'doctor_id' => 'required|integer',
            'surgeon_id' => 'nullable|integer',
            'anesthesiologist_id' => 'nullable|integer',
            'surgery_type' => 'required|string|max:255',
            'diagnosis' => 'nullable|string',
            'priority' => 'required|string|in:normal,urgent,emergency',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'estimated_duration' => 'required|integer|min:15',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        return $this->success([
            'id' => rand(1, 100),
            'room_id' => $request->room_id,
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'surgeon_id' => $request->surgeon_id,
            'anesthesiologist_id' => $request->anesthesiologist_id,
            'surgery_type' => $request->surgery_type,
            'diagnosis' => $request->diagnosis,
            'priority' => $request->priority,
            'scheduled_date' => $request->scheduled_date,
            'scheduled_time' => $request->scheduled_time,
            'estimated_duration' => $request->estimated_duration,
            'status' => 'scheduled',
        ], 'زمان‌بندی جراحی با موفقیت ایجاد شد');
    }

    /**
     * نمایش یک زمان‌بندی
     */
    public function showSchedule($id)
    {
        return $this->success([
            'id' => $id,
            'room_id' => 1,
            'patient_id' => 1,
            'doctor_id' => 1,
            'surgery_type' => 'appendectomy',
            'diagnosis' => 'آپاندیسیت حاد',
            'priority' => 'urgent',
            'scheduled_date' => date('Y-m-d'),
            'scheduled_time' => '10:00',
            'estimated_duration' => 60,
            'status' => 'scheduled',
        ]);
    }

    /**
     * بروزرسانی زمان‌بندی
     */
    public function updateSchedule(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'scheduled_time' => 'nullable|date_format:H:i',
            'scheduled_date' => 'nullable|date',
            'priority' => 'nullable|string|in:normal,urgent,emergency',
            'status' => 'nullable|string|in:scheduled,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        return $this->success([
            'id' => $id,
            'scheduled_time' => $request->scheduled_time ?? '10:00',
            'scheduled_date' => $request->scheduled_date ?? date('Y-m-d'),
            'priority' => $request->priority ?? 'normal',
            'status' => $request->status ?? 'scheduled',
        ], 'زمان‌بندی با موفقیت بروزرسانی شد');
    }

    /**
     * تغییر وضعیت جراحی
     */
    public function changeStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:scheduled,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->error('اطلاعات وارد شده نامعتبر است', 422, $validator->errors());
        }

        return $this->success([
            'id' => $id,
            'status' => $request->status,
        ], 'وضعیت جراحی با موفقیت تغییر کرد');
    }

    /**
     * حذف زمان‌بندی
     */
    public function deleteSchedule($id)
    {
        return $this->success(null, 'زمان‌بندی با موفقیت حذف شد');
    }

    /**
     * آمار اتاق عمل
     */
    public function stats(Request $request)
    {
        $fromDate = $request->from_date ?? date('Y-m-d', strtotime('-30 days'));
        $toDate = $request->to_date ?? date('Y-m-d');

        return $this->success([
            'total_rooms' => 5,
            'active_rooms' => 4,
            'total_schedules' => 120,
            'completed' => 95,
            'cancelled' => 15,
            'in_progress' => 2,
            'scheduled' => 8,
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ]
        ]);
    }
}
