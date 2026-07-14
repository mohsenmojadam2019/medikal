<?php

namespace App\Services\AiChat\System;

use App\Models\AiChat\ChatSession;
use App\Models\AiChat\ChatMessage;
use App\Models\AiChat\ChatFile;
use App\Models\AiChat\CleanupLog;
use App\Enums\AiChat\ChatSessionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DataCleanupService
{
    public function __construct(
        private ConfigManager $configManager
    ) {}

    /**
     * اجرای پاکسازی کامل
     */
    public function runCleanup(int $days = null): array
    {
        $days = $days ?? $this->configManager->getInt('session.cleanup_days', 1);
        $cutoffDate = Carbon::now()->subDays($days);

        $results = [
            'sessions' => 0,
            'messages' => 0,
            'files' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            // ۱. پاکسازی جلسات منقضی
            $sessions = ChatSession::where('status', ChatSessionStatus::EXPIRED)
                ->where('expires_at', '<', $cutoffDate)
                ->get();

            foreach ($sessions as $session) {
                // حذف پیام‌های جلسه
                $results['messages'] += ChatMessage::where('session_id', $session->id)->delete();

                // حذف فایل‌های جلسه
                $files = ChatFile::where('session_id', $session->id)->get();
                foreach ($files as $file) {
                    $this->deleteFile($file);
                    $results['files']++;
                }

                // حذف جلسه (استفاده از SoftDelete)
                $session->delete();
                $results['sessions']++;
            }

            // ۲. پاکسازی فایل‌های بدون جلسه
            $orphanFiles = ChatFile::whereNull('session_id')
                ->orWhereDoesntHave('session')
                ->get();

            foreach ($orphanFiles as $file) {
                $this->deleteFile($file);
                $results['files']++;
            }

            // ۳. ثبت لاگ پاکسازی
            $this->logCleanup($results, $days);

            DB::commit();

            Log::info('Cleanup completed', $results);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
            Log::error('Cleanup failed', ['error' => $e->getMessage()]);

            $this->logCleanup($results, $days, 'failed', $e->getMessage());
        }

        return $results;
    }

    /**
     * پاکسازی دستی فایل‌های قدیمی
     */
    public function cleanupFiles(int $days = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($days);
        $count = 0;

        $files = ChatFile::where('created_at', '<', $cutoffDate)
            ->where('is_deleted', false)
            ->get();

        foreach ($files as $file) {
            if ($this->deleteFile($file)) {
                $count++;
            }
        }

        Log::info('Files cleanup completed', ['deleted' => $count]);
        return $count;
    }

    /**
     * پاکسازی لاگ‌های قدیمی
     */
    public function cleanupLogs(int $days = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($days);
        $count = CleanupLog::where('created_at', '<', $cutoffDate)->delete();

        Log::info('Logs cleanup completed', ['deleted' => $count]);
        return $count;
    }

    /**
     * پاکسازی کامل (سخت)
     */
    public function hardCleanup(int $days = null): array
    {
        $days = $days ?? $this->configManager->getInt('session.cleanup_days', 1);
        $cutoffDate = Carbon::now()->subDays($days);

        $results = [
            'sessions' => 0,
            'messages' => 0,
            'files' => 0,
        ];

        DB::beginTransaction();

        try {
            // حذف سخت جلسات و همه داده‌های مرتبط
            $sessions = ChatSession::where('expires_at', '<', $cutoffDate)->get();

            foreach ($sessions as $session) {
                // حذف فایل‌ها
                foreach ($session->files as $file) {
                    $this->deleteFile($file);
                    $results['files']++;
                }

                // حذف پیام‌ها
                $results['messages'] += ChatMessage::where('session_id', $session->id)->forceDelete();

                // حذف سخت جلسه
                $session->forceDelete();
                $results['sessions']++;
            }

            DB::commit();
            Log::info('Hard cleanup completed', $results);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Hard cleanup failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $results;
    }

    /**
     * حذف فایل از دیسک
     */
    private function deleteFile(ChatFile $file): bool
    {
        try {
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            $file->delete();
            return true;

        } catch (\Exception $e) {
            Log::warning('Failed to delete file', [
                'file_id' => $file->id,
                'path' => $file->file_path,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * ثبت لاگ پاکسازی
     */
    private function logCleanup(array $results, int $days, string $status = 'success', ?string $error = null): void
    {
        CleanupLog::create([
            'table_name' => 'chat_sessions',
            'deleted_count' => $results['sessions'] + $results['messages'] + $results['files'],
            'deleted_before' => Carbon::now()->subDays($days),
            'triggered_by' => 'system',
            'status' => $status,
            'error_message' => $error,
            'metadata' => [
                'sessions' => $results['sessions'],
                'messages' => $results['messages'],
                'files' => $results['files'],
                'days' => $days,
            ],
        ]);
    }

    /**
     * دریافت آمار پاکسازی
     */
    public function getStats(): array
    {
        return [
            'total_sessions' => ChatSession::count(),
            'expired_sessions' => ChatSession::where('status', ChatSessionStatus::EXPIRED)->count(),
            'active_sessions' => ChatSession::where('status', ChatSessionStatus::ACTIVE)->count(),
            'total_messages' => ChatMessage::count(),
            'total_files' => ChatFile::count(),
            'cleanup_logs' => CleanupLog::count(),
            'last_cleanup' => CleanupLog::latest()->first()?->created_at,
        ];
    }

    /**
     * اجرای خشک (Dry Run) برای بررسی چه داده‌هایی پاک می‌شوند
     */
    public function dryRun(int $days = null): array
    {
        $days = $days ?? $this->configManager->getInt('session.cleanup_days', 1);
        $cutoffDate = Carbon::now()->subDays($days);

        return [
            'expired_sessions' => ChatSession::where('status', ChatSessionStatus::EXPIRED)
                ->where('expires_at', '<', $cutoffDate)
                ->count(),
            'orphan_messages' => ChatMessage::whereDoesntHave('session')->count(),
            'orphan_files' => ChatFile::whereDoesntHave('session')->count(),
            'files_to_delete' => ChatFile::where('created_at', '<', $cutoffDate)
                ->where('is_deleted', false)
                ->count(),
            'cutoff_date' => $cutoffDate->toDateTimeString(),
            'days' => $days,
        ];
    }
}
