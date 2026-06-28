<?php

namespace App\Services\Survey;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\Feedback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurveyService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function getSurveys(array $filters = [], int $perPage = 20)
    {
        $query = Survey::where('tenant_id', $this->tenantId);

        if (isset($filters['search'])) {
            $query->where('title', 'LIKE', "%{$filters['search']}%");
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getAvailableSurveys()
    {
        return Survey::where('tenant_id', $this->tenantId)
            ->available()
            ->get();
    }

    public function createSurvey(array $data): Survey
    {
        $data['tenant_id'] = $this->tenantId;
        return Survey::create($data);
    }

    public function updateSurvey(Survey $survey, array $data): Survey
    {
        $survey->update($data);
        return $survey->fresh();
    }

    public function deleteSurvey(Survey $survey): void
    {
        $survey->delete();
    }

    public function toggleSurveyStatus(Survey $survey): Survey
    {
        $survey->update(['is_active' => !$survey->is_active]);
        return $survey->fresh();
    }

    public function submitResponse(array $data): SurveyResponse
    {
        return DB::transaction(function () use ($data) {
            $survey = Survey::where('tenant_id', $this->tenantId)->findOrFail($data['survey_id']);
            $patient = \App\Models\Patient::where('tenant_id', $this->tenantId)->findOrFail($data['patient_id']);

            if (!$survey->canPatientRespond($patient->id)) {
                throw new \Exception('شما قبلاً به این نظرسنجی پاسخ داده‌اید');
            }

            $score = $this->calculateScore($data['answers'] ?? []);

            $data['tenant_id'] = $this->tenantId;
            $response = SurveyResponse::create([
                'tenant_id' => $this->tenantId,
                'survey_id' => $data['survey_id'],
                'patient_id' => $data['patient_id'],
                'appointment_id' => $data['appointment_id'] ?? null,
                'doctor_id' => $data['doctor_id'] ?? null,
                'answers' => $data['answers'] ?? null,
                'score' => $score,
                'feedback' => $data['feedback'] ?? null,
                'status' => 'completed',
                'completed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $data['metadata'] ?? null,
            ]);

            if ($score && $score <= 2) {
                $this->createFeedbackFromResponse($response);
            }

            return $response->fresh(['survey', 'patient']);
        });
    }

    public function getSurveyResponses(int $surveyId, array $filters = [], int $perPage = 20)
    {
        $query = SurveyResponse::where('tenant_id', $this->tenantId)
            ->where('survey_id', $surveyId)
            ->with(['patient', 'appointment', 'doctor']);

        if (isset($filters['min_score'])) {
            $query->where('score', '>=', $filters['min_score']);
        }

        if (isset($filters['max_score'])) {
            $query->where('score', '<=', $filters['max_score']);
        }

        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        return $query->orderBy('completed_at', 'desc')->paginate($perPage);
    }

    public function getPatientResponses(int $patientId, int $perPage = 20)
    {
        return SurveyResponse::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['survey'])
            ->completed()
            ->orderBy('completed_at', 'desc')
            ->paginate($perPage);
    }

    public function createFeedback(array $data): Feedback
    {
        $data['tenant_id'] = $this->tenantId;
        return Feedback::create($data);
    }

    public function createFeedbackFromResponse(SurveyResponse $response): Feedback
    {
        return Feedback::create([
            'tenant_id' => $this->tenantId,
            'patient_id' => $response->patient_id,
            'doctor_id' => $response->doctor_id,
            'appointment_id' => $response->appointment_id,
            'survey_response_id' => $response->id,
            'category' => 'general',
            'rating' => $response->score,
            'comment' => $response->feedback,
            'status' => 'pending',
            'is_anonymous' => false,
        ]);
    }

    public function getFeedbacks(array $filters = [], int $perPage = 20)
    {
        $query = Feedback::where('tenant_id', $this->tenantId)
            ->with(['patient', 'doctor']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        if (isset($filters['patient_id'])) {
            $query->byPatient($filters['patient_id']);
        }

        if (isset($filters['doctor_id'])) {
            $query->byDoctor($filters['doctor_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getPatientFeedbacks(int $patientId, int $perPage = 20)
    {
        return Feedback::where('tenant_id', $this->tenantId)
            ->where('patient_id', $patientId)
            ->with(['doctor'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getDoctorFeedbacks(int $doctorId, int $perPage = 20)
    {
        return Feedback::where('tenant_id', $this->tenantId)
            ->where('doctor_id', $doctorId)
            ->with(['patient'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function replyToFeedback(int $feedbackId, string $reply): Feedback
    {
        $feedback = Feedback::where('tenant_id', $this->tenantId)->findOrFail($feedbackId);
        $feedback->reply($reply);
        return $feedback->fresh();
    }

    public function resolveFeedback(int $feedbackId): Feedback
    {
        $feedback = Feedback::where('tenant_id', $this->tenantId)->findOrFail($feedbackId);
        $feedback->markAsResolved();
        return $feedback->fresh();
    }

    public function getStats(array $filters = []): array
    {
        $query = SurveyResponse::where('tenant_id', $this->tenantId);

        if (isset($filters['from_date'])) {
            $query->whereDate('completed_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('completed_at', '<=', $filters['to_date']);
        }

        return [
            'total_responses' => $query->count(),
            'average_score' => round($query->avg('score') ?? 0, 1),
            'high_score' => (clone $query)->highScore()->count(),
            'low_score' => (clone $query)->lowScore()->count(),
            'total_feedbacks' => Feedback::where('tenant_id', $this->tenantId)->count(),
            'pending_feedbacks' => Feedback::where('tenant_id', $this->tenantId)->pending()->count(),
            'resolved_feedbacks' => Feedback::where('tenant_id', $this->tenantId)->where('status', 'resolved')->count(),
            'by_survey' => $this->getStatsBySurvey($filters),
        ];
    }

    private function getStatsBySurvey(array $filters): array
    {
        $query = SurveyResponse::where('tenant_id', $this->tenantId)
            ->with(['survey']);

        if (isset($filters['from_date'])) {
            $query->whereDate('completed_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('completed_at', '<=', $filters['to_date']);
        }

        return $query->get()
            ->groupBy('survey_id')
            ->map(function ($items) {
                $survey = $items->first()->survey;
                return [
                    'survey_title' => $survey->title,
                    'total' => $items->count(),
                    'average_score' => round($items->avg('score') ?? 0, 1),
                ];
            })
            ->values()
            ->toArray();
    }

    private function calculateScore(array $answers): ?int
    {
        if (empty($answers)) {
            return null;
        }

        $scores = array_filter($answers, function ($value) {
            return is_numeric($value) && $value >= 1 && $value <= 5;
        });

        if (empty($scores)) {
            return null;
        }

        return round(array_sum($scores) / count($scores));
    }
}
