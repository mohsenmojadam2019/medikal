<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PrescriptionStatusEnum;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'drug_name' => 'sometimes|string|max:255',
            'dosage' => 'sometimes|string|max:100',
            'frequency' => 'sometimes|integer|min:1|max:4',
            'duration' => 'sometimes|integer|min:1|max:365',
            'start_date' => 'sometimes|date',
            'instructions' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,active,completed,cancelled,expired',
        ];
    }
}
