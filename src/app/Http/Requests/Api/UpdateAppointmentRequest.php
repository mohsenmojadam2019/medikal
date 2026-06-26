<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'nullable|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i',
            'type' => 'nullable|in:in_person,online,home_visit',
            'notes' => 'nullable|string|max:500',
            'status' => 'nullable|in:pending,confirmed,arrived,in_progress,completed,cancelled,no_show',
        ];
    }
}
