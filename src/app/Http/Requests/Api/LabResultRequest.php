<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LabResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lab_order_id' => 'required|exists:lab_orders,id',
            'lab_order_test_id' => 'nullable|exists:lab_order_tests,id',
            'lab_test_id' => 'required|exists:lab_tests,id',
            'value' => 'nullable|numeric',
            'range_low' => 'nullable|numeric',
            'range_high' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'comment' => 'nullable|string|max:1000',
            'interpretation' => 'nullable|string|max:2000',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'lab_order_id.required' => 'شناسه سفارش الزامی است',
            'lab_test_id.required' => 'شناسه تست الزامی است',
        ];
    }
}
