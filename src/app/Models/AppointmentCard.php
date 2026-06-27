<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentCard extends Model
{
    protected $fillable = [
        'appointment_id',
        'card_number',
        'qr_code',
        'barcode',
        'printed_at',
        'print_count',
        'content',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'content' => 'array',
    ];

    // ========== Relationships ==========
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // ========== Accessors ==========
    public function getIsPrintedAttribute(): bool
    {
        return !is_null($this->printed_at);
    }

    // ========== Methods ==========
    public function generateCardNumber(): string
    {
        $prefix = 'CARD';
        $year = now()->format('y');
        $month = now()->format('m');
        $random = str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}{$month}-{$random}";
    }

    public function markAsPrinted(): void
    {
        $this->update([
            'printed_at' => now(),
            'print_count' => $this->print_count + 1,
        ]);
    }

    public function getContentArray(): array
    {
        $appointment = $this->appointment;
        return [
            'patient_name' => $appointment->patient->full_name,
            'doctor_name' => $appointment->doctor->full_name,
            'date' => $appointment->date->format('Y/m/d'),
            'time' => $appointment->start_time->format('H:i'),
            'clinic_name' => $appointment->doctor->clinic_name ?? 'کلینیک',
            'clinic_address' => $appointment->doctor->clinic_address ?? '',
            'clinic_phone' => $appointment->doctor->clinic_phone ?? '',
            'card_number' => $this->card_number,
            'qr_code' => $this->qr_code,
        ];
    }

    protected static function booted()
    {
        static::creating(function ($card) {
            if (empty($card->card_number)) {
                $card->card_number = $card->generateCardNumber();
            }
        });
    }
}
