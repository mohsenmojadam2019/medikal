<?php

namespace App\Services\AiChat\Chat;

use App\Models\User;
use App\Models\AiChat\ChatFile;
use App\Models\AiChat\ChatSession;
use App\Services\AiChat\System\ConfigManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileUploadService
{
    public function __construct(
        private ConfigManager $configManager
    ) {}

    public function upload(
        UploadedFile $file,
        User $user,
        ?string $sessionToken = null,
        ?int $messageId = null
    ): ChatFile {
        // دریافت یا ایجاد جلسه
        $session = null;
        if ($sessionToken) {
            $session = ChatSession::where('session_token', $sessionToken)
                ->where('user_id', $user->id)
                ->first();
        }

        if (!$session) {
            $chatService = app(ChatService::class);
            $session = $chatService->startSession($user, 'آپلود فایل');
        }

        // تعیین نوع فایل
        $fileType = $this->determineFileType($file);

        // ذخیره در دیتابیس
        $chatFile = ChatFile::create([
            'session_id' => $session->id,
            'user_id' => $user->id,
            'message_id' => $messageId,
            'original_name' => $file->getClientOriginalName(),
            'file_name' => Str::uuid() . '.' . $file->getClientOriginalExtension(),
            'file_size' => round($file->getSize() / 1024),
            'mime_type' => $file->getMimeType(),
            'file_type' => $fileType,
            'expires_at' => $this->configManager->getBool('file.expire_with_session', true)
                ? $session->expires_at
                : now()->addDays(30),
        ]);

        // آپلود با Media Library
        $chatFile->addMedia($file)
            ->usingName($chatFile->original_name)
            ->usingFileName($chatFile->file_name)
            ->toMediaCollection('chat_files');

        return $chatFile;
    }

    public function delete(ChatFile $file): bool
    {
        // Media Library خودش فایل رو حذف میکنه
        $file->delete();
        return true;
    }

    public function download(ChatFile $file)
    {
        $media = $file->getFirstMedia('chat_files');
        if ($media) {
            return $media->toResponse(request());
        }
        return null;
    }

    private function determineFileType(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            return 'image';
        }

        if ($extension === 'pdf') {
            return 'pdf';
        }

        if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'])) {
            return 'document';
        }

        return 'other';
    }

    public function getAllowedTypes(): array
    {
        return $this->configManager->getArray('file.allowed_types', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
    }

    public function getMaxSize(): int
    {
        return $this->configManager->getInt('file.max_size', 5120);
    }

    public function cleanupExpiredFiles(): int
    {
        $count = 0;
        $files = ChatFile::where('expires_at', '<', now())->get();

        foreach ($files as $file) {
            $file->delete();
            $count++;
        }

        return $count;
    }
}
