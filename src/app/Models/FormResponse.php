<?php

namespace App\Models;

use App\Enums\FormResponseStatusEnum;
use Illuminate\Database\Eloquent\Model;

class FormResponse extends Model
{
    protected $fillable = [
        'tenant_id',
        'digital_form_id',
        'patient_id',
        'appointment_id',
        'user_id',
        'response_data',
        'status',
        'submitted_at',
        'completed_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'response_data' => 'array',
        'status' => FormResponseStatusEnum::class,
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function digitalForm()
    {
        return $this->belongsTo(DigitalForm::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function signatures()
    {
        return $this->hasMany(DigitalSignature::class);
    }

    // ========== Scopes ==========
    public function scopeSubmitted($query)
    {
        return $query->where('status', FormResponseStatusEnum::SUBMITTED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', FormResponseStatusEnum::COMPLETED);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByForm($query, $formId)
    {
        return $query->where('digital_form_id', $formId);
    }

    // ========== Accessors ==========
    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? 'نامشخص';
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status?->color() ?? 'secondary';
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === FormResponseStatusEnum::COMPLETED;
    }

    public function getResponseSummaryAttribute(): array
    {
        $summary = [];
        $form = $this->digitalForm;

        if ($form && $form->fields) {
            foreach ($form->fields as $field) {
                $fieldId = $field['id'];
                $value = $this->response_data[$fieldId] ?? null;
                $summary[] = [
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'value' => $value,
                ];
            }
        }

        return $summary;
    }

    // ========== Methods ==========
    public function submit(): void
    {
        $this->update([
            'status' => FormResponseStatusEnum::SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => FormResponseStatusEnum::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function getValue(string $fieldId)
    {
        return $this->response_data[$fieldId] ?? null;
    }

    public function hasSignature(): bool
    {
        return $this->signatures()->exists();
    }
}
