<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Form\FormService;
use App\Traits\ApiResponse;
use App\Models\DigitalForm;
use App\Models\FormResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FormController extends Controller
{
    use ApiResponse;

    protected FormService $formService;

    public function __construct(FormService $formService)
    {
        $this->formService = $formService;
        $this->middleware(['auth:sanctum'])->except(['publicShow', 'publicSubmit']);
    }

    // ============================================================
    // PUBLIC ROUTES (بدون احراز هویت)
    // ============================================================

    public function publicShow($slug)
    {
        try {
            $form = $this->formService->getFormBySlug($slug);
            return $this->success([
                'form' => $form,
                'fields' => $form->fields,
                'settings' => $form->settings,
            ]);
        } catch (\Exception $e) {
            return $this->error('فرم یافت نشد', 404);
        }
    }

    public function publicSubmit(Request $request, $slug)
    {
        try {
            $form = $this->formService->getFormBySlug($slug);

            $validator = Validator::make($request->all(), [
                'response_data' => 'required|array',
                'signature' => 'nullable|array',
                'signature.signature_image' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
            }

            $data = [
                'digital_form_id' => $form->id,
                'response_data' => $request->response_data,
                'signature' => $request->signature,
                'ip_address' => $request->ip(),
            ];

            $response = $this->formService->submitResponse($data);
            return $this->success($response, 'فرم با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // FORMS (Admin)
    // ============================================================

    public function forms(Request $request)
    {
        $forms = $this->formService->getForms(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($forms);
    }

    public function publishedForms(Request $request)
    {
        $forms = $this->formService->getPublishedForms(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($forms);
    }

    public function showForm($id)
    {
        try {
            $form = $this->formService->getForm($id);
            return $this->success($form);
        } catch (\Exception $e) {
            return $this->error('فرم یافت نشد', 404);
        }
    }

    public function storeForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:50',
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => 'required|string',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.options' => 'nullable|array',
            'status' => 'nullable|in:draft,published,archived',
            'is_active' => 'nullable|boolean',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['created_by'] = auth()->id();
            $form = $this->formService->createForm($data);
            return $this->success($form, 'فرم با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateForm(Request $request, $id)
    {
        try {
            $form = DigitalForm::findOrFail($id);
            $form = $this->formService->updateForm($form, $request->all());
            return $this->success($form, 'فرم با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteForm($id)
    {
        try {
            $form = DigitalForm::findOrFail($id);
            $this->formService->deleteForm($form);
            return $this->success(null, 'فرم با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function publishForm($id)
    {
        try {
            $form = DigitalForm::findOrFail($id);
            $form = $this->formService->publishForm($form);
            return $this->success($form, 'فرم با موفقیت منتشر شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function archiveForm($id)
    {
        try {
            $form = DigitalForm::findOrFail($id);
            $form = $this->formService->archiveForm($form);
            return $this->success($form, 'فرم با موفقیت بایگانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function duplicateForm($id)
    {
        try {
            $form = DigitalForm::findOrFail($id);
            $newForm = $this->formService->duplicateForm($form);
            return $this->success($newForm, 'فرم با موفقیت کپی شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // RESPONSES
    // ============================================================

    public function responses(Request $request)
    {
        $responses = $this->formService->getResponses(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($responses);
    }

    public function formResponses(Request $request, $formId)
    {
        $responses = $this->formService->getFormResponses(
            $formId,
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($responses);
    }

    public function showResponse($id)
    {
        try {
            $response = FormResponse::with(['digitalForm', 'patient.user', 'user', 'signatures'])
                ->findOrFail($id);
            return $this->success($response);
        } catch (\Exception $e) {
            return $this->error('پاسخ یافت نشد', 404);
        }
    }

    public function submitResponse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'digital_form_id' => 'required|exists:digital_forms,id',
            'response_data' => 'required|array',
            'signature' => 'nullable|array',
            'signature.signature_image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $data = $request->all();
            $data['user_id'] = auth()->id();
            $response = $this->formService->submitResponse($data);
            return $this->success($response, 'فرم با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function updateResponse(Request $request, $id)
    {
        try {
            $response = FormResponse::findOrFail($id);
            $response = $this->formService->updateResponse($response, $request->all());
            return $this->success($response, 'پاسخ با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function deleteResponse($id)
    {
        try {
            $response = FormResponse::findOrFail($id);
            $this->formService->deleteResponse($response);
            return $this->success(null, 'پاسخ با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function completeResponse($id)
    {
        try {
            $response = FormResponse::findOrFail($id);
            $response = $this->formService->completeResponse($response);
            return $this->success($response, 'پاسخ با موفقیت تکمیل شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // MY RESPONSES (Patient)
    // ============================================================

    public function myResponses(Request $request)
    {
        $user = auth()->user();
        $patient = \App\Models\Patient::where('user_id', $user->id)->first();

        if (!$patient) {
            return $this->error('بیمار یافت نشد', 404);
        }

        $responses = $this->formService->getPatientResponses(
            $patient->id,
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($responses);
    }

    // ============================================================
    // SIGNATURES
    // ============================================================

    public function signatures(Request $request)
    {
        $signatures = $this->formService->getSignatures(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($signatures);
    }

    public function deleteSignature($id)
    {
        try {
            $signature = DigitalSignature::findOrFail($id);
            $this->formService->deleteSignature($signature);
            return $this->success(null, 'امضا با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // CATEGORIES
    // ============================================================

    public function categories()
    {
        $categories = $this->formService->getCategories();
        return $this->success($categories);
    }

    // ============================================================
    // STATS
    // ============================================================

    public function stats(Request $request)
    {
        $stats = $this->formService->getStats($request->all());
        return $this->success($stats);
    }
}
