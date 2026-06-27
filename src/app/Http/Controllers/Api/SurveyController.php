<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Survey\SurveyService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    use ApiResponse;

    protected SurveyService $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        $this->surveyService = $surveyService;
    }

    // ============================================================
    // SURVEYS
    // ============================================================

    public function index(Request $request)
    {
        $surveys = $this->surveyService->getSurveys(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($surveys);
    }

    public function available()
    {
        $surveys = $this->surveyService->getAvailableSurveys();
        return $this->success($surveys);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:appointment,general,doctor',
            'questions' => 'nullable|array',
            'settings' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_attempts' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $survey = $this->surveyService->createSurvey($request->all());
            return $this->success($survey, 'نظرسنجی با موفقیت ایجاد شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function show($id)
    {
        try {
            $survey = Survey::findOrFail($id);
            return $this->success($survey);
        } catch (\Exception $e) {
            return $this->error('نظرسنجی یافت نشد', 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $survey = Survey::findOrFail($id);
        } catch (\Exception $e) {
            return $this->error('نظرسنجی یافت نشد', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:appointment,general,doctor',
            'questions' => 'nullable|array',
            'settings' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_attempts' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $survey = $this->surveyService->updateSurvey($survey, $request->all());
            return $this->success($survey, 'نظرسنجی با موفقیت بروزرسانی شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function destroy($id)
    {
        try {
            $survey = Survey::findOrFail($id);
            $this->surveyService->deleteSurvey($survey);
            return $this->success(null, 'نظرسنجی با موفقیت حذف شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $survey = Survey::findOrFail($id);
            $survey = $this->surveyService->toggleSurveyStatus($survey);
            return $this->success($survey, 'وضعیت نظرسنجی با موفقیت تغییر کرد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // RESPONSES
    // ============================================================

    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'survey_id' => 'required|exists:surveys,id',
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'answers' => 'nullable|array',
            'feedback' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $response = $this->surveyService->submitResponse($request->all());
            return $this->success($response, 'نظرسنجی با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function surveyResponses(Request $request, $surveyId)
    {
        $responses = $this->surveyService->getSurveyResponses(
            $surveyId,
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($responses);
    }

    public function patientResponses(Request $request, $patientId)
    {
        $responses = $this->surveyService->getPatientResponses(
            $patientId,
            $request->get('per_page', 20)
        );
        return $this->success($responses);
    }

    // ============================================================
    // FEEDBACK
    // ============================================================

    public function submitFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'category' => 'required|in:general,doctor,facility,staff',
            'rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'suggestion' => 'nullable|string|max:1000',
            'is_anonymous' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $feedback = $this->surveyService->createFeedback($request->all());
            return $this->success($feedback, 'بازخورد با موفقیت ثبت شد', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function feedbacks(Request $request)
    {
        $feedbacks = $this->surveyService->getFeedbacks(
            $request->all(),
            $request->get('per_page', 20)
        );
        return $this->success($feedbacks);
    }

    public function patientFeedbacks(Request $request, $patientId)
    {
        $feedbacks = $this->surveyService->getPatientFeedbacks(
            $patientId,
            $request->get('per_page', 20)
        );
        return $this->success($feedbacks);
    }

    public function doctorFeedbacks(Request $request, $doctorId)
    {
        $feedbacks = $this->surveyService->getDoctorFeedbacks(
            $doctorId,
            $request->get('per_page', 20)
        );
        return $this->success($feedbacks);
    }

    public function replyFeedback(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error('خطا در اعتبارسنجی', 422, $validator->errors());
        }

        try {
            $feedback = $this->surveyService->replyToFeedback($id, $request->reply);
            return $this->success($feedback, 'پاسخ با موفقیت ثبت شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    public function resolveFeedback($id)
    {
        try {
            $feedback = $this->surveyService->resolveFeedback($id);
            return $this->success($feedback, 'بازخورد با موفقیت حل شد');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    // ============================================================
    // REPORTS
    // ============================================================

    public function stats(Request $request)
    {
        $stats = $this->surveyService->getStats($request->all());
        return $this->success($stats);
    }
}
