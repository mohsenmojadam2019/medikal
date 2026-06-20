<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PhoneLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.regex' => 'فرمت شماره موبایل نامعتبر است.',
        ];
    }
}
