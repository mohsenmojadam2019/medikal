<?php

namespace App\Services\AiChat\Chat;

use App\Models\User;
use App\Models\AiChat\ChatSession;
use App\Models\AiChat\ChatMessage;
use App\Enums\AiChat\ChatSessionStatus;
use App\Enums\AiChat\MessageRole;
use App\Enums\AiChat\SeverityLevel;
use App\Exceptions\AiChat\SessionExpiredException;
use App\Exceptions\AiChat\NonMedicalQuestionException;
use App\Exceptions\AiChat\EmergencyException;
use App\Services\AiChat\Medical\MedicalFilterService;
use App\Services\AiChat\AI\OllamaClient;
use App\Services\AiChat\AI\PromptManager;
use App\Services\AiChat\System\ConfigManager;
use App\Services\AiChat\System\MetricsCollector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatService
{
    public function __construct(
        private MedicalFilterService $medicalFilter,
        private OllamaClient $ollamaClient,
        private PromptManager $promptManager,
        private ConfigManager $configManager,
        private MetricsCollector $metricsCollector
    ) {}

    /**
     * شروع یک جلسه چت جدید
     */
    public function startSession(User $user, ?string $title = null, ?string $model = null): ChatSession
    {
        // بستن جلسات فعال قبلی کاربر
        ChatSession::where('user_id', $user->id)
            ->where('status', ChatSessionStatus::ACTIVE)
            ->update(['status' => ChatSessionStatus::CLOSED]);

        // ایجاد جلسه جدید
        $session = ChatSession::create([
            'user_id' => $user->id,
            'title' => $title ?? 'مکالمه پزشکی ' . now()->format('Y/m/d H:i'),
            'model_used' => $model ?? $this->configManager->get('models.default', 'qwen3:14b'),
            'expires_at' => now()->addMinutes($this->configManager->get('session.lifetime', 1440)),
            'status' => ChatSessionStatus::ACTIVE,
            'last_activity' => now(),
        ]);

        // ثبت رویداد شروع جلسه
        $this->metricsCollector->increment('sessions_started');
        
        // پیام خوش‌آمدگویی سیستم
        $this->addSystemMessage($session, 'سلام! من "دکتر آنلاین" هستم. چطور می‌توانم به شما کمک کنم؟');

        return $session;
    }

    /**
     * دریافت جلسه فعال کاربر
     */
    public function getActiveSession(User $user, ?string $sessionToken = null): ?ChatSession
    {
        $query = ChatSession::where('user_id', $user->id)
            ->where('status', ChatSessionStatus::ACTIVE)
            ->where('expires_at', '>', now());

        if ($sessionToken) {
            $query->where('session_token', $sessionToken);
        }

        return $query->latest()->first();
    }

    /**
     * ارسال پیام و دریافت پاسخ از هوش مصنوعی
     */
    public function sendMessage(
        User $user,
        string $message,
        ?string $sessionToken = null,
        array $options = []
    ): array {
        // ۱. دریافت یا ایجاد جلسه
        $session = $this->getActiveSession($user, $sessionToken);
        
        if (!$session) {
            $session = $this->startSession($user);
        }

        // ۲. بررسی انقضای جلسه
        if ($session->isExpired()) {
            throw new SessionExpiredException('جلسه چت منقضی شده است. لطفاً جلسه جدیدی شروع کنید.');
        }

        // ۳. فیلتر و تحلیل سوال (پزشکی/غیرپزشکی/اورژانسی)
        $filterResult = $this->medicalFilter->filter($message);
        
        // ۴. مدیریت وضعیت اورژانسی
        if ($filterResult->isEmergency) {
            $this->handleEmergency($user, $session, $filterResult);
            throw new EmergencyException(
                'وضعیت اورژانسی تشخیص داده شد!',
                $filterResult->toArray()
            );
        }

        // ۵. مدیریت سوال غیرپزشکی
        if (!$filterResult->isMedical && $this->configManager->get('filter.strict', true)) {
            throw new NonMedicalQuestionException(
                'من فقط به سوالات پزشکی پاسخ می‌دهم. لطفاً سوال خود را در مورد سلامت بپرسید.'
            );
        }

        // ۶. ذخیره پیام کاربر
        $userMessage = $this->storeMessage($session, $user, MessageRole::USER, $message, [
            'is_medical' => $filterResult->isMedical,
            'is_emergency' => $filterResult->isEmergency,
            'category' => $filterResult->category->value,
            'severity' => $filterResult->severity->value,
            'detected_symptoms' => $filterResult->detectedSymptoms,
        ]);

        // ۷. ساخت پرامپت برای مدل
        $promptData = $this->promptManager->buildFullPrompt(
            $filterResult->category,
            array_merge($filterResult->toArray(), $options, [
                'question' => $message,
                'message' => $message,
                'history' => $this->getChatHistory($session, 5),
            ])
        );

        // ۸. تولید پاسخ توسط هوش مصنوعی
        $startTime = microtime(true);
        
        $response = $this->ollamaClient
            ->setModel($options['model'] ?? $this->configManager->get('models.default'))
            ->setSystemPrompt($promptData['system'])
            ->setOptions([
                'temperature' => $options['temperature'] ?? $this->configManager->get('ollama.options.temperature', 0.7),
                'max_tokens' => $options['max_tokens'] ?? $this->configManager->get('ollama.options.max_tokens', 500),
            ])
            ->generate($promptData['user']);

        $responseTime = round((microtime(true) - $startTime) * 1000);

        // ۹. ذخیره پاسخ هوش مصنوعی
        $assistantMessage = $this->storeMessage($session, $user, MessageRole::ASSISTANT, $response, [
            'model_used' => $this->ollamaClient->getCurrentModel(),
            'tokens_used' => $this->ollamaClient->getLastTokensUsed(),
            'response_time' => $responseTime,
            'confidence_score' => $this->calculateConfidence($response, $filterResult),
            'category' => $filterResult->category->value,
        ]);

        // ۱۰. به‌روزرسانی جلسه
        $session->incrementMessageCount();
        $session->update(['last_activity' => now()]);

        // ۱۱. ذخیره در سوالات پزشکی
        $this->medicalFilter->logMedicalQuery($session, $user, [
            'question' => $message,
            'response' => $response,
            'category' => $filterResult->category->value,
            'severity' => $filterResult->severity->value,
            'detected_symptoms' => $filterResult->detectedSymptoms,
            'suggested_actions' => $filterResult->suggestedActions,
            'ai_confidence' => $this->ollamaClient->getLastConfidence(),
        ]);

        // ۱۲. ثبت متریک‌ها
        $this->metricsCollector->record([
            'message_processed' => 1,
            'tokens_used' => $this->ollamaClient->getLastTokensUsed() ?? 0,
            'response_time' => $responseTime,
            'category' => $filterResult->category->value,
            'severity' => $filterResult->severity->value,
        ]);

        return [
            'success' => true,
            'session' => $session->only(['id', 'session_token', 'status']),
            'message' => [
                'id' => $assistantMessage->id,
                'role' => 'assistant',
                'content' => $response,
                'model_used' => $this->ollamaClient->getCurrentModel(),
                'response_time' => $responseTime,
            ],
            'analysis' => $filterResult->toArray(),
            'suggestions' => $this->generateSuggestions($filterResult),
        ];
    }

    /**
     * ذخیره پیام در دیتابیس
     */
    private function storeMessage(
        ChatSession $session,
        User $user,
        MessageRole $role,
        string $content,
        array $metadata = []
    ): ChatMessage {
        return ChatMessage::create(array_merge([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'role' => $role->value,
            'content' => $content,
            'is_emergency' => $metadata['is_emergency'] ?? false,
            'is_medical' => $metadata['is_medical'] ?? true,
            'category' => $metadata['category'] ?? null,
            'severity' => $metadata['severity'] ?? SeverityLevel::NORMAL->value,
            'metadata' => $metadata['metadata'] ?? null,
        ], array_filter([
            'model_used' => $metadata['model_used'] ?? null,
            'tokens_used' => $metadata['tokens_used'] ?? null,
            'response_time' => $metadata['response_time'] ?? null,
            'confidence_score' => $metadata['confidence_score'] ?? null,
        ])));
    }

    /**
     * اضافه کردن پیام سیستمی
     */
    private function addSystemMessage(ChatSession $session, string $content): void
    {
        ChatMessage::create([
            'session_id' => $session->id,
            'user_id' => $session->user_id,
            'role' => MessageRole::SYSTEM->value,
            'content' => $content,
        ]);
    }

    /**
     * دریافت تاریخچه چت
     */
    private function getChatHistory(ChatSession $session, int $limit = 10): array
    {
        return ChatMessage::where('session_id', $session->id)
            ->whereIn('role', [MessageRole::USER->value, MessageRole::ASSISTANT->value])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->reverse()
            ->values()
            ->toArray();
    }

    /**
     * مدیریت وضعیت اورژانسی
     */
    private function handleEmergency(User $user, ChatSession $session, $filterResult): void
    {
        // ذخیره پیام اورژانسی کاربر
        $this->storeMessage($session, $user, MessageRole::USER, $filterResult->message, [
            'is_emergency' => true,
            'is_medical' => true,
            'category' => MedicalCategory::EMERGENCY->value,
            'severity' => SeverityLevel::EMERGENCY->value,
            'detected_symptoms' => $filterResult->detectedSymptoms,
        ]);

        // ارسال پاسخ اورژانسی
        $emergencyResponse = $this->configManager->get('emergency.response_template', '');
        $emergencyResponse = str_replace(
            ['{phone}', '{additional_guidance}'],
            [
                $this->configManager->get('emergency.phone', '115'),
                $this->generateEmergencyGuidance($filterResult),
            ],
            $emergencyResponse
        );

        $this->storeMessage($session, $user, MessageRole::ASSISTANT, $emergencyResponse, [
            'is_emergency' => true,
            'is_medical' => true,
            'category' => MedicalCategory::EMERGENCY->value,
            'severity' => SeverityLevel::EMERGENCY->value,
        ]);

        // ثبت در سوالات پزشکی
        $this->medicalFilter->logMedicalQuery($session, $user, [
            'question' => $filterResult->message,
            'response' => $emergencyResponse,
            'category' => MedicalCategory::EMERGENCY->value,
            'severity' => SeverityLevel::EMERGENCY->value,
            'detected_symptoms' => $filterResult->detectedSymptoms,
            'suggested_actions' => array_merge(
                ['تماس با اورژانس 115'],
                $filterResult->suggestedActions ?? []
            ),
            'is_handled' => false,
        ]);

        // ثبت رویداد اورژانسی
        $this->metricsCollector->increment('emergencies_detected');
        Log::warning('وضعیت اورژانسی تشخیص داده شد', [
            'user_id' => $user->id,
            'session_id' => $session->id,
            'message' => $filterResult->message,
            'symptoms' => $filterResult->detectedSymptoms,
        ]);
    }

    /**
     * محاسبه امتیاز اطمینان
     */
    private function calculateConfidence(string $response, $filterResult): float
    {
        $confidence = 0.5; // مقدار پایه
        
        // افزایش اطمینان بر اساس دسته‌بندی دقیق
        if ($filterResult->category !== MedicalCategory::GENERAL) {
            $confidence += 0.2;
        }
        
        // افزایش اطمینان در صورت تشخیص علائم
        if (!empty($filterResult->detectedSymptoms)) {
            $confidence += 0.15;
        }
        
        // کاهش اطمینان در صورت پاسخ کوتاه
        if (strlen($response) < 50) {
            $confidence -= 0.1;
        }
        
        return min(max($confidence, 0), 1);
    }

    /**
     * تولید راهنمای اورژانسی
     */
    private function generateEmergencyGuidance($filterResult): string
    {
        $guidance = [];
        
        if (!empty($filterResult->detectedSymptoms)) {
            $guidance[] = 'علائم تشخیص داده شده: ' . implode('، ', $filterResult->detectedSymptoms);
        }
        
        if (!empty($filterResult->suggestedActions)) {
            $guidance[] = 'اقدامات توصیه شده: ' . implode('، ', $filterResult->suggestedActions);
        }
        
        return implode("\n", $guidance);
    }

    /**
     * تولید پیشنهادات
     */
    private function generateSuggestions($filterResult): array
    {
        $suggestions = [];
        
        // پیشنهاد بر اساس دسته‌بندی
        switch ($filterResult->category) {
            case MedicalCategory::SYMPTOM:
                $suggestions[] = '📋 برای تشخیص دقیق‌تر، به پزشک مراجعه کنید.';
                $suggestions[] = '💊 از مصرف خودسرانه دارو خودداری کنید.';
                break;
            case MedicalCategory::DRUG:
                $suggestions[] = '💊 داروها را فقط با نسخه پزشک مصرف کنید.';
                $suggestions[] = '📋 عوارض جانبی را جدی بگیرید و به پزشک اطلاع دهید.';
                break;
            case MedicalCategory::NUTRITION:
                $suggestions[] = '🥗 رژیم غذایی متعادل و متنوع داشته باشید.';
                $suggestions[] = '💧 مصرف آب کافی در طول روز را فراموش نکنید.';
                break;
            case MedicalCategory::PSYCHOLOGY:
                $suggestions[] = '🧘‍♂️ تمرینات تنفس و مدیتیشن می‌تواند مفید باشد.';
                $suggestions[] = '💬 با یک مشاور یا روانشناس صحبت کنید.';
                break;
        }
        
        // پیشنهاد کلی
        $suggestions[] = '🏥 این پاسخ فقط جنبه اطلاع‌رسانی دارد و تشخیص نهایی با پزشک است.';
        
        return $suggestions;
    }

    /**
     * دریافت تاریخچه پیام‌های جلسه
     */
    public function getMessages(ChatSession $session, int $limit = 50, int $offset = 0): array
    {
        return ChatMessage::where('session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->skip($offset)
            ->limit($limit)
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'is_emergency' => $msg->is_emergency,
                'is_medical' => $msg->is_medical,
                'category' => $msg->category,
                'severity' => $msg->severity,
                'severity_label' => $msg->severity?->label(),
                'created_at' => $msg->created_at->toDateTimeString(),
                'model_used' => $msg->model_used,
                'response_time' => $msg->response_time,
            ])
            ->toArray();
    }

    /**
     * بستن جلسه چت
     */
    public function closeSession(ChatSession $session): bool
    {
        return $session->update(['status' => ChatSessionStatus::CLOSED]);
    }

    /**
     * تمدید اعتبار جلسه
     */
    public function extendSession(ChatSession $session, int $minutes = 1440): bool
    {
        return $session->update([
            'expires_at' => now()->addMinutes($minutes),
            'status' => ChatSessionStatus::ACTIVE,
        ]);
    }

    /**
     * حذف جلسه (نرم)
     */
    public function deleteSession(ChatSession $session): bool
    {
        return $session->delete(); // استفاده از SoftDelete
    }

    /**
     * ثبت بازخورد کاربر
     */
    public function submitFeedback(
        User $user,
        ChatMessage $message,
        int $rating,
        ?string $comment = null
    ): bool {
        // بررسی تعلق پیام به کاربر
        if ($message->session->user_id !== $user->id) {
            return false;
        }

        // جلوگیری از ثبت بازخورد تکراری
        if ($message->feedback()->exists()) {
            return false;
        }

        $feedback = $message->feedback()->create([
            'user_id' => $user->id,
            'rating' => $rating,
            'comment' => $comment,
            'is_helpful' => $rating >= 4,
        ]);

        // به‌روزرسانی متریک‌ها
        $this->metricsCollector->record([
            'feedback_submitted' => 1,
            'feedback_rating' => $rating,
            'feedback_is_helpful' => $rating >= 4,
        ]);

        return true;
    }
}
