<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PharmacyOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prescription_id' => 'required|exists:prescriptions,id',
            'pharmacy_id' => 'required|exists:pharmacies,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'prescription_id.required' => 'شناسه نسخه الزامی است',
            'prescription_id.exists' => 'نسخه یافت نشد',
            'pharmacy_id.required' => 'شناسه داروخانه الزامی است',
            'pharmacy_id.exists' => 'داروخانه یافت نشد',
        ];
    }
}
