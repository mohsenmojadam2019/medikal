<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $doctorId = $this->route('doctor');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $this->user?->id,
            'mobile' => 'sometimes|regex:/^09[0-9]{9}$/|unique:users,mobile,' . $this->user?->id,
            'specialty_id' => 'nullable|exists:specialties,id',
            'license_number' => 'sometimes|string|unique:doctors,license_number,' . $doctorId,
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
}
