<?php
// app/Http/Requests/Admin/StoreDoctorRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // اطلاعات کاربر
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'mobile' => 'required|regex:/^09[0-9]{9}$/|unique:users,mobile',
            'password' => 'nullable|string|min:6',

            // ✅ اطلاعات پزشک (فقط کلینیک_id به جای اطلاعات کلینیک)
            'clinic_id' => 'nullable|exists:clinics,id',
            'specialty_id' => 'nullable|exists:specialties,id',
            'license_number' => 'required|string|unique:doctors,license_number',

            // ❌ حذف شدند - اینها از کلینیک گرفته می‌شوند
            // 'clinic_name', 'clinic_address', 'clinic_phone', 'clinic_email'

            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'bio' => 'nullable|string',
            'biography' => 'nullable|string',
            'education' => 'nullable|array',
            'certificates' => 'nullable|array',
            'social_links' => 'nullable|array',
            'working_hours' => 'nullable|array',
            'experience_years' => 'nullable|integer|min:0',

            // هزینه‌ها
            'consultation_fee' => 'nullable|numeric|min:0',
            'appointment_fee_type' => 'nullable|in:free,paid',
            'appointment_fee_amount' => 'nullable|numeric|min:0',
            'visit_duration' => 'nullable|integer|min:15|max:120',

            // وضعیت
            'is_available' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',

            // آدرس (برای موقعیت مکانی)
            'address' => 'nullable|array',
            'address.address_line_1' => 'required_with:address|string|max:500',
            'address.address_line_2' => 'nullable|string|max:500',
            'address.neighborhood' => 'nullable|string|max:100',
            'address.province_id' => 'required_with:address|exists:provinces,id',
            'address.city_id' => 'required_with:address|exists:cities,id',
            'address.postal_code' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام پزشک الزامی است',
            'mobile.required' => 'شماره موبایل الزامی است',
            'mobile.regex' => 'شماره موبایل نامعتبر است',
            'license_number.required' => 'شماره نظام پزشکی الزامی است',
            'license_number.unique' => 'این شماره نظام پزشکی قبلاً ثبت شده است',
        ];
    }
}
