<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PrescriptionStatusEnum;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => 'required|exists:appointments,id',
            'drug_name' => 'required|string|max:255',
            'dosage' => 'required|string|max:100',
            'frequency' => 'nullable|integer|min:1|max:4',
            'duration' => 'nullable|integer|min:1|max:365',
            'start_date' => 'nullable|date',
            'instructions' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'appointment_id.required' => 'شناسه نوبت الزامی است',
            'drug_name.required' => 'نام دارو الزامی است',
            'dosage.required' => 'دوز مصرفی الزامی است',
            'frequency.min' => 'تعداد دفعات مصرف باید حداقل ۱ باشد',
            'frequency.max' => 'تعداد دفعات مصرف باید حداکثر ۴ باشد',
            'duration.min' => 'مدت مصرف باید حداقل ۱ روز باشد',
        ];
    }
}
