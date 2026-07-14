<?php

namespace App\Contracts\AiChat;

use App\Models\AiChat\ChatSession;
use App\Models\AiChat\ChatMessage;
use App\Models\User;

interface ChatServiceInterface
{
    /**
     * شروع یک جلسه چت جدید
     */
    public function startSession(User $user, ?string $title = null, ?string $model = null): ChatSession;

    /**
     * دریافت جلسه فعال کاربر
     */
    public function getActiveSession(User $user, ?string $sessionToken = null): ?ChatSession;

    /**
     * ارسال پیام به هوش مصنوعی
     */
    public function sendMessage(User $user, string $message, ?string $sessionToken = null, array $options = []): array;

    /**
     * دریافت تاریخچه پیام‌های یک جلسه
     */
    public function getMessages(ChatSession $session, int $limit = 50, int $offset = 0): array;

    /**
     * بستن جلسه چت
     */
    public function closeSession(ChatSession $session): bool;

    /**
     * تمدید اعتبار جلسه
     */
    public function extendSession(ChatSession $session, int $minutes = 1440): bool;

    /**
     * حذف جلسه (نرم)
     */
    public function deleteSession(ChatSession $session): bool;

    /**
     * ثبت بازخورد کاربر در مورد پیام
     */
    public function submitFeedback(User $user, ChatMessage $message, int $rating, ?string $comment = null): bool;
}
