<?php

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
            'phone' => 'nullable|string|max:15',
            'emergency_contact' => 'nullable|string|max:15',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'is_active' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
            'doctor_id' => 'nullable|exists:doctors,id',

            // آدرس
            'address' => 'nullable|array',
            'address.address_line_1' => 'required_with:address|string|max:500',
            'address.address_line_2' => 'nullable|string|max:500',
            'address.neighborhood' => 'nullable|string|max:100',
            'address.province_id' => 'required_with:address|exists:provinces,id',
            'address.city_id' => 'required_with:address|exists:cities,id',
            'address.postal_code' => 'nullable|string|max:20',

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
