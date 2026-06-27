<?php

namespace App\Services\Form;

use App\Models\DigitalForm;
use App\Models\FormResponse;
use App\Models\DigitalSignature;
use App\Enums\FormStatusEnum;
use App\Enums\FormResponseStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormService
{
    // ============================================================
    // FORM MANAGEMENT
    // ============================================================

    public function getForms(array $filters = [], int $perPage = 20)
    {
        $query = DigitalForm::with(['creator']);

        if (isset($filters['search'])) {
            $query->where('title', 'LIKE', "%{$filters['search']}%")
                ->orWhere('slug', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getPublishedForms(array $filters = [], int $perPage = 20)
    {
        $filters['status'] = FormStatusEnum::PUBLISHED;
        return $this->getForms($filters, $perPage);
    }

    public function getForm(int $id): DigitalForm
    {
        return DigitalForm::with(['creator', 'responses'])
            ->findOrFail($id);
    }

    public function getFormBySlug(string $slug): DigitalForm
    {
        return DigitalForm::where('slug', $slug)
            ->where('status', FormStatusEnum::PUBLISHED)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function createForm(array $data): DigitalForm
    {
        return DB::transaction(function () use ($data) {
            $fields = $this->processFields($data['fields'] ?? []);
            $settings = $this->processSettings($data['settings'] ?? []);

            $form = DigitalForm::create([
                'title' => $data['title'],
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? FormStatusEnum::DRAFT,
                'category' => $data['category'] ?? null,
                'fields' => $fields,
                'settings' => $settings,
                'is_active' => $data['is_active'] ?? false,
                'created_by' => $data['created_by'] ?? auth()->id(),
                'metadata' => $data['metadata'] ?? null,
            ]);

            return $form->fresh();
        });
    }

    public function updateForm(DigitalForm $form, array $data): DigitalForm
    {
        return DB::transaction(function () use ($form, $data) {
            if (isset($data['fields'])) {
                $data['fields'] = $this->processFields($data['fields']);
            }

            if (isset($data['settings'])) {
                $data['settings'] = $this->processSettings($data['settings']);
            }

            $form->update($data);
            return $form->fresh();
        });
    }

    public function deleteForm(DigitalForm $form): void
    {
        $form->delete();
    }

    public function publishForm(DigitalForm $form): DigitalForm
    {
        $form->publish();
        return $form->fresh();
    }

    public function archiveForm(DigitalForm $form): DigitalForm
    {
        $form->archive();
        return $form->fresh();
    }

    public function duplicateForm(DigitalForm $form): DigitalForm
    {
        return $form->duplicate();
    }

    private function processFields(array $fields): array
    {
        foreach ($fields as &$field) {
            // اطمینان از وجود id برای هر فیلد
            if (empty($field['id'])) {
                $field['id'] = 'field_' . Str::random(8);
            }

            // اطمینان از وجود type
            if (empty($field['type'])) {
                $field['type'] = 'text';
            }

            // پردازش options برای فیلدهای انتخابی
            if (in_array($field['type'], ['select', 'multi_select', 'radio', 'checkbox'])) {
                if (isset($field['options']) && is_string($field['options'])) {
                    $field['options'] = explode(',', $field['options']);
                }
                if (!isset($field['options']) || !is_array($field['options'])) {
                    $field['options'] = [];
                }
            }
        }

        return $fields;
    }

    private function processSettings(array $settings): array
    {
        $defaults = [
            'allow_anonymous' => false,
            'require_login' => false,
            'show_progress' => true,
            'confirmation_message' => 'با تشکر، فرم با موفقیت ثبت شد',
            'redirect_url' => null,
            'notify_admin' => true,
            'notify_patient' => false,
        ];

        return array_merge($defaults, $settings);
    }

    // ============================================================
    // RESPONSE MANAGEMENT
    // ============================================================

    public function getResponses(array $filters = [], int $perPage = 20)
    {
        $query = FormResponse::with(['digitalForm', 'patient.user', 'user']);

        if (isset($filters['form_id'])) {
            $query->byForm($filters['form_id']);
        }

        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('submitted_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('submitted_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getFormResponses(int $formId, array $filters = [], int $perPage = 20)
    {
        $filters['form_id'] = $formId;
        return $this->getResponses($filters, $perPage);
    }

    public function getPatientResponses(int $patientId, array $filters = [], int $perPage = 20)
    {
        $filters['patient_id'] = $patientId;
        return $this->getResponses($filters, $perPage);
    }

    public function submitResponse(array $data): FormResponse
    {
        return DB::transaction(function () use ($data) {
            $form = DigitalForm::findOrFail($data['digital_form_id']);

            // اعتبارسنجی پاسخ
            $validation = $form->validateResponse($data['response_data'] ?? []);
            if (!$validation['valid']) {
                throw new \Exception(implode("\n", $validation['errors']));
            }

            // ذخیره پاسخ
            $response = FormResponse::create([
                'digital_form_id' => $data['digital_form_id'],
                'patient_id' => $data['patient_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'response_data' => $validation['data'],
                'status' => FormResponseStatusEnum::SUBMITTED,
                'submitted_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $data['metadata'] ?? null,
            ]);

            // اگر امضا نیاز باشد
            if (isset($data['signature'])) {
                $this->addSignature($response->id, $data['signature']);
            }

            // تکمیل خودکار (اگر همه فیلدها پر شده باشد)
            $this->autoCompleteResponse($response);

            return $response->fresh(['digitalForm', 'patient', 'user']);
        });
    }

    public function updateResponse(FormResponse $response, array $data): FormResponse
    {
        return DB::transaction(function () use ($response, $data) {
            if (isset($data['response_data'])) {
                $form = $response->digitalForm;
                $validation = $form->validateResponse($data['response_data']);
                if (!$validation['valid']) {
                    throw new \Exception(implode("\n", $validation['errors']));
                }
                $data['response_data'] = $validation['data'];
            }

            $response->update($data);

            // تکمیل خودکار
            $this->autoCompleteResponse($response);

            return $response->fresh();
        });
    }

    public function deleteResponse(FormResponse $response): void
    {
        // حذف امضاهای مرتبط
        $response->signatures()->delete();
        $response->delete();
    }

    public function completeResponse(FormResponse $response): FormResponse
    {
        $response->complete();
        return $response->fresh();
    }

    private function autoCompleteResponse(FormResponse $response): void
    {
        $form = $response->digitalForm;
        $requiredFields = array_filter($form->fields, function ($field) {
            return $field['required'] ?? false;
        });

        $allFilled = true;
        foreach ($requiredFields as $field) {
            $fieldId = $field['id'];
            if (empty($response->response_data[$fieldId])) {
                $allFilled = false;
                break;
            }
        }

        if ($allFilled && $response->status === FormResponseStatusEnum::SUBMITTED) {
            $response->complete();
        }
    }

    // ============================================================
    // SIGNATURE MANAGEMENT
    // ============================================================

    public function addSignature(int $responseId, array $signatureData): DigitalSignature
    {
        $response = FormResponse::findOrFail($responseId);

        return DB::transaction(function () use ($response, $signatureData) {
            $base64 = $signatureData['signature_image'] ?? null;

            if (!$base64) {
                throw new \Exception('تصویر امضا الزامی است');
            }

            $signature = DigitalSignature::createFromBase64($base64, [
                'digital_form_id' => $response->digital_form_id,
                'form_response_id' => $response->id,
                'patient_id' => $response->patient_id,
                'user_id' => $response->user_id ?? auth()->id(),
                'signature_data' => $signatureData['signature_data'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $signatureData['metadata'] ?? null,
            ]);

            return $signature;
        });
    }

    public function getSignatures(int $formId, array $filters = [], int $perPage = 20)
    {
        $query = DigitalSignature::with(['patient.user', 'user']);

        if (isset($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('signed_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('signed_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('signed_at', 'desc')->paginate($perPage);
    }

    public function deleteSignature(DigitalSignature $signature): void
    {
        if ($signature->signature_image) {
            Storage::disk('public')->delete($signature->signature_image);
        }
        $signature->delete();
    }

    // ============================================================
    // STATISTICS
    // ============================================================

    public function getStats(array $filters = []): array
    {
        $query = DigitalForm::query();

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return [
            'total_forms' => $query->count(),
            'published_forms' => (clone $query)->where('status', FormStatusEnum::PUBLISHED)->count(),
            'draft_forms' => (clone $query)->where('status', FormStatusEnum::DRAFT)->count(),
            'total_responses' => FormResponse::count(),
            'completed_responses' => FormResponse::completed()->count(),
            'submitted_responses' => FormResponse::submitted()->count(),
            'total_signatures' => DigitalSignature::count(),
            'by_category' => $this->getStatsByCategory($filters),
            'responses_today' => FormResponse::whereDate('submitted_at', today())->count(),
        ];
    }

    private function getStatsByCategory(array $filters): array
    {
        $query = DigitalForm::selectRaw('category, count(*) as total')
            ->groupBy('category');

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category ?? 'بدون دسته‌بندی',
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    // ============================================================
    // CATEGORIES (Helper)
    // ============================================================

    public function getCategories(): array
    {
        return [
            'consent' => 'رضایت‌نامه',
            'medical_history' => 'تاریخچه پزشکی',
            'registration' => 'ثبت‌نام',
            'survey' => 'نظرسنجی',
            'feedback' => 'بازخورد',
            'appointment' => 'نوبت‌دهی',
            'prescription' => 'نسخه‌نویسی',
            'other' => 'سایر',
        ];
    }
}
