<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'تاریخ جدید الزامی است',
            'date.after_or_equal' => 'تاریخ باید از امروز یا بعد از آن باشد',
            'start_time.required' => 'ساعت جدید الزامی است',
        ];
    }
}
