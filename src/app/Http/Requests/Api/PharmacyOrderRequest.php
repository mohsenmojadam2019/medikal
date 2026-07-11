<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PharmacyOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // فیلدهای اختیاری
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'pharmacy_id' => 'nullable|exists:pharmacies,id',
            
            // آیتم‌های سفارش
            'items' => 'required|array|min:1',
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1',
            
            // اطلاعات ارسال
            'delivery_address' => 'nullable|string|max:500',
            'delivery_notes' => 'nullable|string|max:500',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_phone' => 'nullable|string|max:20',
            
            // روش پرداخت
            'payment_method' => 'nullable|in:wallet,gateway',
            'gateway' => 'nullable|in:local,zarinpal,asanpardakht,paypal',
            
            // توضیحات اضافی
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'حداقل یک محصول باید انتخاب شود',
            'items.min' => 'حداقل یک محصول باید انتخاب شود',
            'items.*.drug_id.required' => 'شناسه دارو الزامی است',
            'items.*.drug_id.exists' => 'دارو یافت نشد',
            'items.*.quantity.required' => 'تعداد الزامی است',
            'items.*.quantity.min' => 'تعداد باید حداقل 1 باشد',
        ];
    }
}
