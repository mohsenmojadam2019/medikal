<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LabTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:lab_categories,id',
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'sample_type' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:20',
            'min_range' => 'nullable|numeric',
            'max_range' => 'nullable|numeric',
            'critical_low' => 'nullable|numeric',
            'critical_high' => 'nullable|numeric',
            'price' => 'nullable|numeric|min:0',
            'turnaround_time' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'requires_fasting' => 'nullable|boolean',
            'fasting_hours' => 'nullable|integer|min:1',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام تست الزامی است',
        ];
    }
}
