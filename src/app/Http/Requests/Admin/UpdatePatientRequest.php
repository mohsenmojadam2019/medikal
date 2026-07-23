<?php
// app/Http/Requests/Admin/UpdatePatientRequest.php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $patientId = $this->route('patient');

        return [
            // اطلاعات پایه
            'name' => 'sometimes|string|max:255',
            'mobile' => 'sometimes|regex:/^09[0-9]{9}$/|unique:users,mobile,' . $this->user?->id,
            'email' => 'nullable|email|unique:users,email,' . $this->user?->id,

            // اطلاعات بیمار
            'national_code' => 'nullable|string|size:10|unique:patients,national_code,' . $patientId,
            'full_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',

            // ✅ موقعیت مکانی
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',

            // بیمه
            'insurance_type' => 'nullable|string|max:50',
            'insurance_number' => 'nullable|string|max:50',
            'emergency_contact' => 'nullable|string|max:15',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',

            // وضعیت
            'is_active' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
            'doctor_id' => 'nullable|exists:doctors,id',

            // آدرس (جزئیات)
            'address_detail' => 'nullable|array',
            'address_detail.address_line_1' => 'required_with:address_detail|string|max:500',
            'address_detail.address_line_2' => 'nullable|string|max:500',
            'address_detail.neighborhood' => 'nullable|string|max:100',
            'address_detail.postal_code' => 'nullable|string|max:20',

            // متادیتا
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'شماره موبایل نامعتبر است',
            'mobile.unique' => 'این شماره موبایل قبلاً ثبت شده است',
            'national_code.size' => 'کدملی باید ۱۰ رقم باشد',
            'national_code.unique' => 'این کدملی قبلاً ثبت شده است',
            'blood_type.in' => 'گروه خونی نامعتبر است',
        ];
    }
}
