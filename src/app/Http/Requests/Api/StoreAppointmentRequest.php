<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // بیمار (یکی از اینها باید وجود داشته باشد)
            'patient_id' => 'nullable|exists:patients,id',
            'national_code' => 'nullable|string|size:10',
            'patient_name' => 'nullable|string|max:255',
            'mobile' => 'nullable|regex:/^09[0-9]{9}$/',
            'email' => 'nullable|email',

            // نوبت
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'type' => 'nullable|in:in_person,online,home_visit',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_id.required' => 'پزشک الزامی است',
            'doctor_id.exists' => 'پزشک انتخاب شده معتبر نیست',
            'date.required' => 'تاریخ نوبت الزامی است',
            'date.after_or_equal' => 'تاریخ نوبت باید از امروز یا بعد از آن باشد',
            'start_time.required' => 'ساعت نوبت الزامی است',
            'start_time.date_format' => 'ساعت نوبت نامعتبر است',
        ];
    }
}
