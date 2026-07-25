<?php
// app/Contracts/AiChat/AIProviderInterface.php

namespace App\Contracts\AiChat;

interface AIProviderInterface
{
    /**
     * تنظیم مدل
     */
    public function setModel(string $model): self;

    /**
     * تنظیم پرامپت سیستم
     */
    public function setSystemPrompt(string $prompt): self;

    /**
     * تنظیم پارامترها
     */
    public function setOptions(array $options): self;

    /**
     * تولید پاسخ
     */
    public function generate(string $prompt): string;

    /**
     * تولید پاسخ با تاریخچه مکالمه
     */
    public function chat(array $messages): string;

    /**
     * تولید پاسخ به صورت جریانی
     */
    public function stream(string $prompt, callable $callback): void;

    /**
     * بررسی در دسترس بودن
     */
    public function isAvailable(): bool;

    /**
     * لیست مدل‌های موجود
     */
    public function listModels(): array;

    /**
     * دریافت اطلاعات مدل
     */
    public function getModelInfo(): array;

    /**
     * دریافت نام provider
     */
    public function getProviderName(): string;
}
