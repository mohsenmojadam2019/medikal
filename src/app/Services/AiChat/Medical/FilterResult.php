<?php

namespace App\Services\AiChat\Medical;

use App\Enums\AiChat\MedicalCategory;
use App\Enums\AiChat\SeverityLevel;

class FilterResult
{
    public function __construct(
        public bool $isMedical,
        public bool $isEmergency,
        public MedicalCategory $category,
        public SeverityLevel $severity,
        public array $detectedSymptoms = [],
        public array $suggestedActions = [],
        public float $confidence = 0.0,
        public string $message = ''
    ) {}

    public function toArray(): array
    {
        return [
            'is_medical' => $this->isMedical,
            'is_emergency' => $this->isEmergency,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'severity' => $this->severity->value,
            'severity_label' => $this->severity->label(),
            'detected_symptoms' => $this->detectedSymptoms,
            'suggested_actions' => $this->suggestedActions,
            'confidence' => $this->confidence,
            'message' => $this->message,
        ];
    }

    public function isNormal(): bool
    {
        return $this->severity === SeverityLevel::NORMAL;
    }

    public function isUrgent(): bool
    {
        return $this->severity === SeverityLevel::URGENT;
    }

    public function isSevere(): bool
    {
        return $this->severity === SeverityLevel::EMERGENCY || $this->severity === SeverityLevel::URGENT;
    }
}
