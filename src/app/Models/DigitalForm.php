<?php

namespace App\Models;

use App\Enums\FormStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'description',
        'status',
        'category',
        'fields',
        'settings',
        'is_active',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'status' => FormStatusEnum::class,
        'fields' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // ========== Relationships ==========
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses()
    {
        return $this->hasMany(FormResponse::class);
    }

    public function signatures()
    {
        return $this->hasMany(DigitalSignature::class);
    }

    // ========== Scopes ==========
    public function scopePublished($query)
    {
        return $query->where('status', FormStatusEnum::PUBLISHED);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
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

    public function getFieldCountAttribute(): int
    {
        return count($this->fields ?? []);
    }

    public function getResponseCountAttribute(): int
    {
        return $this->responses()->count();
    }

    public function getCompletionRateAttribute(): float
    {
        $total = $this->responses()->count();
        if ($total == 0) return 0;
        $completed = $this->responses()->completed()->count();
        return round(($completed / $total) * 100, 1);
    }

    public function getUrlAttribute(): string
    {
        return route('forms.public', $this->slug);
    }

    // ========== Methods ==========
    public function generateSlug(): string
    {
        $slug = \Illuminate\Support\Str::slug($this->title);
        $count = static::where('slug', 'LIKE', "{$slug}%")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function publish(): void
    {
        $this->update([
            'status' => FormStatusEnum::PUBLISHED,
            'is_active' => true,
        ]);
    }

    public function archive(): void
    {
        $this->update(['status' => FormStatusEnum::ARCHIVED]);
    }

    public function duplicate(): self
    {
        $newForm = $this->replicate();
        $newForm->title = $this->title . ' (کپی)';
        $newForm->slug = $this->generateSlug();
        $newForm->status = FormStatusEnum::DRAFT;
        $newForm->is_active = false;
        $newForm->save();
        return $newForm;
    }

    public function getField($fieldId): ?array
    {
        foreach ($this->fields as $field) {
            if ($field['id'] == $fieldId) {
                return $field;
            }
        }
        return null;
    }

    public function validateResponse(array $data): array
    {
        $errors = [];
        $validated = [];

        foreach ($this->fields as $field) {
            $fieldId = $field['id'];
            $value = $data[$fieldId] ?? null;

            // بررسی اجباری بودن
            if (($field['required'] ?? false) && empty($value)) {
                $errors[] = "فیلد {$field['label']} الزامی است";
                continue;
            }

            // اعتبارسنجی بر اساس نوع
            if (!empty($value)) {
                $valid = $this->validateFieldValue($field, $value);
                if (!$valid) {
                    $errors[] = "مقدار فیلد {$field['label']} نامعتبر است";
                }
            }

            $validated[$fieldId] = $value;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validated,
        ];
    }

    private function validateFieldValue(array $field, $value): bool
    {
        $type = $field['type'];

        return match ($type) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'phone' => preg_match('/^09[0-9]{9}$/', $value) === 1,
            'number' => is_numeric($value),
            'date' => strtotime($value) !== false,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            default => true,
        };
    }

    // ========== Boot ==========
    protected static function booted()
    {
        static::creating(function ($form) {
            if (empty($form->slug)) {
                $form->slug = $form->generateSlug();
            }
        });
    }
}
