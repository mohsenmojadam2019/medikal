<?php
// app/Services/AiChat/AIChatService.php

namespace App\Services\AiChat;

use App\Models\User;
use App\Models\AiChat\ChatSession;
use App\Models\AiChat\ChatMessage;
use App\Services\AiChat\Providers\AIProviderFactory;
use App\Services\AiChat\Medical\MedicalFilterService;
use App\Services\AiChat\AI\PromptManager;
use App\Services\AiChat\System\ConfigManager;
use App\Services\AiChat\System\MetricsCollector;
use App\Enums\AiChat\ChatSessionStatus;
use App\Enums\AiChat\MessageRole;
use App\Enums\AiChat\SeverityLevel;
use App\Exceptions\AiChat\SessionExpiredException;
use App\Exceptions\AiChat\NonMedicalQuestionException;
use App\Exceptions\AiChat\EmergencyException;

class AIChatService
{
    public function __construct(
        public AIProviderFactory     $providerFactory,
        private MedicalFilterService $medicalFilter,
        private PromptManager        $promptManager,
        private ConfigManager        $configManager,
        private MetricsCollector     $metricsCollector
    ) {}

    public function startSession(User $user, ?string $title = null, ?string $provider = null, ?string $model = null): ChatSession
    {
        ChatSession::where('user_id', $user->id)
            ->where('status', ChatSessionStatus::ACTIVE)
            ->update(['status' => ChatSessionStatus::CLOSED]);

        $session = ChatSession::create([
            'user_id' => $user->id,
            'title' => $title ?? 'مکالمه پزشکی ' . now()->format('Y/m/d H:i'),
            'provider' => $provider ?? $this->configManager->get('provider.default', 'ollama'),
            'model_used' => $model ?? $this->configManager->get('models.default', 'qwen3:14b'),
            'expires_at' => now()->addMinutes($this->configManager->get('session.lifetime', 1440)),
            'status' => ChatSessionStatus::ACTIVE,
            'last_activity' => now(),
        ]);

        $this->metricsCollector->increment('sessions_started');
        $this->addSystemMessage($session, 'سلام! من "دکتر آنلاین" هستم. چطور می‌توانم به شما کمک کنم؟');

        return $session;
    }

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

    public function sendMessage(User $user, string $message, ?string $sessionToken = null, array $options = []): array
    {
        $session = $this->getActiveSession($user, $sessionToken);

        if (!$session) {
            $session = $this->startSession($user);
        }

        if ($session->isExpired()) {
            throw new SessionExpiredException('جلسه چت منقضی شده است. لطفاً جلسه جدیدی شروع کنید.');
        }

        $filterResult = $this->medicalFilter->filter($message);

        if ($filterResult->isEmergency) {
            $this->handleEmergency($user, $session, $filterResult);
            throw new EmergencyException('وضعیت اورژانسی تشخیص داده شد!', $filterResult->toArray());
        }

        if (!$filterResult->isMedical && $this->configManager->get('filter.strict', true)) {
            throw new NonMedicalQuestionException('من فقط به سوالات پزشکی پاسخ می‌دهم.');
        }

        $this->storeMessage($session, $user, MessageRole::USER, $message, [
            'is_medical' => $filterResult->isMedical,
            'is_emergency' => $filterResult->isEmergency,
            'category' => $filterResult->category->value,
            'severity' => $filterResult->severity->value,
        ]);

        $promptData = $this->promptManager->buildFullPrompt(
            $filterResult->category,
            array_merge($filterResult->toArray(), $options, [
                'question' => $message,
                'message' => $message,
                'history' => $this->getChatHistory($session, 5),
            ])
        );

        $provider = $this->providerFactory->make($options['provider'] ?? $session->provider);
        $provider->setModel($options['model'] ?? $session->model_used)
            ->setSystemPrompt($promptData['system'])
            ->setOptions([
                'temperature' => $options['temperature'] ?? $this->configManager->get('ollama.options.temperature', 0.7),
                'max_tokens' => $options['max_tokens'] ?? $this->configManager->get('ollama.options.max_tokens', 500),
            ]);

        $startTime = microtime(true);
        $response = $provider->generate($promptData['user']);
        $responseTime = round((microtime(true) - $startTime) * 1000);

        $assistantMessage = $this->storeMessage($session, $user, MessageRole::ASSISTANT, $response, [
            'provider' => $provider->getProviderName(),
            'model_used' => $provider->getModel(),
            'tokens_used' => $provider->getLastTokensUsed(),
            'response_time' => $responseTime,
            'confidence_score' => $provider->getLastConfidence(),
            'category' => $filterResult->category->value,
        ]);

        $session->incrementMessageCount();
        $session->update(['last_activity' => now()]);

        $this->medicalFilter->logMedicalQuery($session, $user, [
            'question' => $message,
            'response' => $response,
            'category' => $filterResult->category->value,
            'severity' => $filterResult->severity->value,
        ]);

        $this->metricsCollector->record([
            'message_processed' => 1,
            'tokens_used' => $provider->getLastTokensUsed() ?? 0,
            'response_time' => $responseTime,
            'category' => $filterResult->category->value,
        ]);

        return [
            'success' => true,
            'session' => $session->only(['id', 'session_token', 'status']),
            'message' => [
                'id' => $assistantMessage->id,
                'role' => 'assistant',
                'content' => $response,
                'provider' => $provider->getProviderName(),
                'model_used' => $provider->getModel(),
                'response_time' => $responseTime,
            ],
            'analysis' => $filterResult->toArray(),
        ];
    }

    private function storeMessage(ChatSession $session, User $user, MessageRole $role, string $content, array $metadata = []): ChatMessage
    {
        return ChatMessage::create(array_merge([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'role' => $role->value,
            'content' => $content,
            'is_emergency' => $metadata['is_emergency'] ?? false,
            'is_medical' => $metadata['is_medical'] ?? true,
            'category' => $metadata['category'] ?? null,
            'severity' => $metadata['severity'] ?? SeverityLevel::NORMAL->value,
        ], array_filter([
            'provider' => $metadata['provider'] ?? null,
            'model_used' => $metadata['model_used'] ?? null,
            'tokens_used' => $metadata['tokens_used'] ?? null,
            'response_time' => $metadata['response_time'] ?? null,
            'confidence_score' => $metadata['confidence_score'] ?? null,
        ])));
    }

    private function addSystemMessage(ChatSession $session, string $content): void
    {
        ChatMessage::create([
            'session_id' => $session->id,
            'user_id' => $session->user_id,
            'role' => MessageRole::SYSTEM->value,
            'content' => $content,
        ]);
    }

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

    private function handleEmergency(User $user, ChatSession $session, $filterResult): void
    {
        $this->storeMessage($session, $user, MessageRole::USER, $filterResult->message, [
            'is_emergency' => true,
            'category' => 'emergency',
            'severity' => SeverityLevel::EMERGENCY->value,
        ]);

        $emergencyResponse = "⚠️ **هشدار اورژانسی!**\n\n" .
            "وضعیت شما اورژانسی تشخیص داده شده است.\n" .
            "🔴 لطفاً فوراً با شماره **115** تماس بگیرید.\n" .
            "🏥 به نزدیک‌ترین بیمارستان مراجعه کنید.\n\n" .
            "علائم تشخیص داده شده:\n" .
            "• " . implode("\n• ", $filterResult->detectedSymptoms) . "\n\n" .
            "⚠️ این یک هشدار اضطراری است و زمان را از دست ندهید!";

        $this->storeMessage($session, $user, MessageRole::ASSISTANT, $emergencyResponse, [
            'is_emergency' => true,
            'category' => 'emergency',
            'severity' => SeverityLevel::EMERGENCY->value,
        ]);

        $this->metricsCollector->increment('emergencies_detected');
    }

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
                'provider' => $msg->provider,
                'model_used' => $msg->model_used,
                'response_time' => $msg->response_time,
            ])
            ->toArray();
    }

    public function closeSession(ChatSession $session): bool
    {
        return $session->update(['status' => ChatSessionStatus::CLOSED]);
    }

    public function extendSession(ChatSession $session, int $minutes = 1440): bool
    {
        return $session->update([
            'expires_at' => now()->addMinutes($minutes),
            'status' => ChatSessionStatus::ACTIVE,
        ]);
    }

    public function deleteSession(ChatSession $session): bool
    {
        return $session->delete();
    }

    public function submitFeedback(User $user, ChatMessage $message, int $rating, ?string $comment = null): bool
    {
        if ($message->session->user_id !== $user->id) {
            return false;
        }

        if ($message->feedback()->exists()) {
            return false;
        }

        $feedback = $message->feedback()->create([
            'user_id' => $user->id,
            'rating' => $rating,
            'comment' => $comment,
            'is_helpful' => $rating >= 4,
        ]);

        $this->metricsCollector->record([
            'feedback_submitted' => 1,
            'feedback_rating' => $rating,
            'feedback_is_helpful' => $rating >= 4,
        ]);

        return true;
    }

    public function getActiveProviders(): array
    {
        return $this->providerFactory->getAvailableProviders();
    }

    public function getDefaultProvider(): string
    {
        return $this->providerFactory->getDefaultProvider();
    }

    public function setDefaultProvider(string $provider): void
    {
        $this->providerFactory->setDefaultProvider($provider);
    }
}
