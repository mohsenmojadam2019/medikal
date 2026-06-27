<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LabOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'priority' => 'nullable|in:routine,urgent,stat',
            'sample_type' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'clinical_history' => 'nullable|string|max:2000',
            'tests' => 'required|array|min:1',
            'tests.*.test_id' => 'required|exists:lab_tests,id',
            'tests.*.unit_price' => 'nullable|numeric|min:0',
            'tests.*.quantity' => 'nullable|integer|min:1',
            'tests.*.discount' => 'nullable|numeric|min:0',
            'tests.*.notes' => 'nullable|string|max:500',
            'tests.*.is_urgent' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'شناسه بیمار الزامی است',
            'tests.required' => 'حداقل یک تست باید انتخاب شود',
            'tests.*.test_id.required' => 'شناسه تست الزامی است',
        ];
    }
}
