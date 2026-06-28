<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DigitalSignature extends Model
{
    protected $fillable = [
        'tenant_id',
        'digital_form_id',
        'form_response_id',
        'patient_id',
        'user_id',
        'signature_data',
        'signature_image',
        'ip_address',
        'user_agent',
        'signed_at',
        'metadata',
    ];

    protected $casts = [
        'signature_data' => 'array',
        'signed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function digitalForm()
    {
        return $this->belongsTo(DigitalForm::class);
    }

    public function formResponse()
    {
        return $this->belongsTo(FormResponse::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========== Accessors ==========
    public function getSignatureUrlAttribute(): string
    {
        if ($this->signature_image) {
            return asset('storage/' . $this->signature_image);
        }
        return '';
    }

    public function getIsValidAttribute(): bool
    {
        // بررسی اعتبار امضا (می‌توان با تاریخ انقضا یا شرایط دیگر ترکیب کرد)
        return $this->signed_at && $this->signed_at->diffInDays(now()) < 365;
    }

    // ========== Methods ==========
    public static function createFromBase64(string $base64, array $data): self
    {
        // ذخیره امضای دیجیتال از base64
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));
        $fileName = 'signatures/' . uniqid() . '.png';
        \Storage::disk('public')->put($fileName, $imageData);

        $data['signature_image'] = $fileName;
        $data['signed_at'] = now();

        return self::create($data);
    }

    public function verify(string $signatureData): bool
    {
        // بررسی تطابق امضا (می‌توان با الگوریتم‌های هش یا رمزنگاری پیشرفته‌تر انجام داد)
        return hash_equals($this->signature_data ?? '', $signatureData);
    }
}
