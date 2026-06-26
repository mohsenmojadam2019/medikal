<?php

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
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'mobile' => 'required|regex:/^09[0-9]{9}$/|unique:users,mobile',
            'password' => 'nullable|string|min:6',
            'specialty_id' => 'nullable|exists:specialties,id',
            'license_number' => 'required|string|unique:doctors,license_number',
            'clinic_name' => 'nullable|string|max:255',
            'clinic_address' => 'nullable|string',
            'clinic_phone' => 'nullable|string|max:20',
            'clinic_email' => 'nullable|email',
            'biography' => 'nullable|string',
            'education' => 'nullable|array',
            'experience_years' => 'nullable|integer|min:0',
            'consultation_fee' => 'nullable|numeric|min:0',
            'visit_duration' => 'nullable|integer|min:15|max:120',
            'is_available' => 'nullable|boolean',
            'is_verified' => 'nullable|boolean',
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
