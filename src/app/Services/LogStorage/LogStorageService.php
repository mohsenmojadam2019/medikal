<?php

namespace App\Services\LogStorage;

use App\Models\AuditLog;
use App\Models\LogArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LogStorageService
{
    protected $tenantId;

    public function __construct()
    {
        $this->tenantId = session('tenant_id');
    }

    public function log($event, $modelType = null, $modelId = null, $oldValues = null, $newValues = null, $metadata = null): AuditLog
    {
        return AuditLog::create([
            'tenant_id' => $this->tenantId,
            'user_id' => auth()->id(),
            'event' => $event,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'route' => request()->route()?->uri(),
            'method' => request()->method(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    public function getAuditLogs(array $filters = [], int $perPage = 50)
    {
        $query = AuditLog::where('tenant_id', $this->tenantId)->with(['user']);

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['event'])) {
            $query->byEvent($filters['event']);
        }

        if (isset($filters['model_type'])) {
            $query->byModel($filters['model_type']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->recent($perPage);
    }

    public function archiveLogs(string $type = 'laravel'): array
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        $archived = 0;
        $errors = 0;

        foreach ($files as $file) {
            $filename = $file->getFilename();
            $date = $this->extractDateFromLogFile($filename);

            if (!$date) continue;

            if ($date->diffInDays(now()) > 7) {
                try {
                    $archivePath = $this->moveToArchive($file, $type);
                    LogArchive::create([
                        'tenant_id' => $this->tenantId,
                        'file_name' => $filename,
                        'file_path' => $archivePath,
                        'file_size' => $file->getSize(),
                        'type' => $type,
                        'date' => $date,
                        'is_compressed' => true,
                        'archived_at' => now(),
                    ]);
                    $archived++;
                } catch (\Exception $e) {
                    $errors++;
                }
            }
        }

        return [
            'archived' => $archived,
            'errors' => $errors,
        ];
    }

    private function extractDateFromLogFile(string $filename): ?Carbon
    {
        if (preg_match('/laravel-(\d{4}-\d{2}-\d{2})\.log/', $filename, $matches)) {
            return Carbon::parse($matches[1]);
        }
        return null;
    }

    private function moveToArchive($file, string $type): string
    {
        $archiveDir = storage_path('logs/archive/' . $type);
        if (!File::exists($archiveDir)) {
            File::makeDirectory($archiveDir, 0755, true);
        }

        $filename = $file->getFilename();
        $archivePath = $archiveDir . '/' . $filename;
        $compressedPath = $archivePath . '.gz';
        $this->gzipFile($file->getPathname(), $compressedPath);
        File::delete($file->getPathname());

        return $compressedPath;
    }

    private function gzipFile($source, $destination): void
    {
        $fp = gzopen($destination, 'wb9');
        $file = fopen($source, 'rb');
        while (!feof($file)) {
            gzwrite($fp, fread($file, 1024 * 1024));
        }
        fclose($file);
        gzclose($fp);
    }

    public function getArchivedLogs(array $filters = [], int $perPage = 20)
    {
        $query = LogArchive::where('tenant_id', $this->tenantId);

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('date', '<=', $filters['to_date']);
        }

        return $query->orderBy('archived_at', 'desc')->paginate($perPage);
    }

    public function restoreArchivedLog(int $archiveId): array
    {
        $archive = LogArchive::where('tenant_id', $this->tenantId)->findOrFail($archiveId);

        if (!File::exists($archive->file_path)) {
            return ['success' => false, 'error' => 'فایل آرشیو یافت نشد'];
        }

        try {
            $content = gzdecode(file_get_contents($archive->file_path));
            $restorePath = storage_path('logs/restored/' . $archive->file_name);

            $restoreDir = dirname($restorePath);
            if (!File::exists($restoreDir)) {
                File::makeDirectory($restoreDir, 0755, true);
            }

            File::put($restorePath, $content);

            return [
                'success' => true,
                'file' => $restorePath,
                'message' => 'فایل با موفقیت بازیابی شد',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function cleanupArchivedLogs(int $days = 90): int
    {
        $count = 0;
        $oldArchives = LogArchive::where('tenant_id', $this->tenantId)
            ->where('archived_at', '<', Carbon::now()->subDays($days))
            ->get();

        foreach ($oldArchives as $archive) {
            try {
                if (File::exists($archive->file_path)) {
                    File::delete($archive->file_path);
                }
                $archive->delete();
                $count++;
            } catch (\Exception $e) {
                \Log::error('Cleanup archived log failed: ' . $e->getMessage());
            }
        }

        return $count;
    }

    public function getStats(): array
    {
        return [
            'total_audit_logs' => AuditLog::where('tenant_id', $this->tenantId)->count(),
            'today_audit_logs' => AuditLog::where('tenant_id', $this->tenantId)->whereDate('created_at', today())->count(),
            'total_archived_logs' => LogArchive::where('tenant_id', $this->tenantId)->count(),
            'archived_by_type' => LogArchive::where('tenant_id', $this->tenantId)
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type')
                ->toArray(),
            'total_backups' => \App\Models\BackupHistory::where('tenant_id', $this->tenantId)->count(),
            'successful_backups' => \App\Models\BackupHistory::where('tenant_id', $this->tenantId)->where('status', 'completed')->count(),
            'failed_backups' => \App\Models\BackupHistory::where('tenant_id', $this->tenantId)->where('status', 'failed')->count(),
            'total_backup_size' => \App\Models\BackupHistory::where('tenant_id', $this->tenantId)->where('status', 'completed')->sum('file_size'),
        ];
    }
}
