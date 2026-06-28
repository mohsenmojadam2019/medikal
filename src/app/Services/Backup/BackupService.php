<?php

namespace App\Services\Backup;

use App\Models\BackupHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupService
{
    protected $disk;
    protected $backupPath;
    protected $tenantId;

    public function __construct()
    {
        $this->disk = config('backup.disk', 'local');
        $this->backupPath = config('backup.path', 'backups');
        $this->tenantId = session('tenant_id');
    }

    public function backupDatabase(): array
    {
        $backup = BackupHistory::create([
            'tenant_id' => $this->tenantId,
            'name' => 'database_backup_' . now()->format('Y-m-d_H-i-s'),
            'type' => 'database',
            'status' => 'pending',
            'started_at' => now(),
        ]);

        try {
            $backup->markAsRunning();

            $fileName = $backup->name . '.sql';
            $filePath = $this->backupPath . '/database/' . ($this->tenantId ? 'tenant_' . $this->tenantId . '/' : '') . $fileName;

            $this->dumpDatabase($filePath);

            $fileSize = Storage::disk($this->disk)->size($filePath);

            $backup->markAsCompleted($filePath, $fileSize);

            $this->compressBackup($filePath);

            return [
                'success' => true,
                'backup' => $backup,
                'file' => $filePath,
                'size' => $backup->file_size_display,
                'duration' => $backup->duration_display,
            ];

        } catch (\Exception $e) {
            $backup->markAsFailed($e->getMessage());
            Log::error('Database backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function dumpDatabase(string $filePath): void
    {
        $database = config('database.connections.mysql');
        $host = $database['host'];
        $port = $database['port'];
        $dbname = $database['database'];
        $user = $database['username'];
        $password = $database['password'];

        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
            $host,
            $port,
            $user,
            $password,
            $dbname,
            storage_path('app/' . $filePath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->dumpDatabaseWithPHP($filePath);
        }
    }

    private function dumpDatabaseWithPHP(string $filePath): void
    {
        $connection = DB::connection();
        $tables = $connection->getDoctrineSchemaManager()->listTableNames();

        $content = "-- Database Backup\n";
        $content .= "-- Generated: " . now() . "\n\n";

        foreach ($tables as $table) {
            $create = $connection->select("SHOW CREATE TABLE `$table`");
            if (empty($create) == false) {
                $content .= $create[0]->{'Create Table'} . ";\n\n";
            }

            $rows = $connection->table($table)->get();
            if ($rows->isEmpty() == false) {
                $content .= "INSERT INTO `$table` VALUES\n";
                $values = [];
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $escaped = array_map(function ($value) {
                        if (is_null($value)) return 'NULL';
                        return "'" . addslashes($value) . "'";
                    }, $rowArray);
                    $values[] = "(" . implode(", ", $escaped) . ")";
                }
                $content .= implode(",\n", $values) . ";\n\n";
            }
        }

        Storage::disk($this->disk)->put($filePath, $content);
    }

    public function backupFiles(array $paths = []): array
    {
        $backup = BackupHistory::create([
            'tenant_id' => $this->tenantId,
            'name' => 'files_backup_' . now()->format('Y-m-d_H-i-s'),
            'type' => 'files',
            'status' => 'pending',
            'started_at' => now(),
        ]);

        try {
            $backup->markAsRunning();

            $fileName = $backup->name . '.zip';
            $filePath = $this->backupPath . '/files/' . ($this->tenantId ? 'tenant_' . $this->tenantId . '/' : '') . $fileName;

            $this->zipFiles($paths, $filePath);

            $fileSize = Storage::disk($this->disk)->size($filePath);

            $backup->markAsCompleted($filePath, $fileSize);

            return [
                'success' => true,
                'backup' => $backup,
                'file' => $filePath,
                'size' => $backup->file_size_display,
                'duration' => $backup->duration_display,
            ];

        } catch (\Exception $e) {
            $backup->markAsFailed($e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function zipFiles(array $paths, string $output): void
    {
        $zip = new \ZipArchive();
        $zipPath = storage_path('app/' . $output);

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Cannot create zip file');
        }

        $defaultPaths = [
            storage_path('app/public'),
            storage_path('logs'),
        ];

        $paths = array_merge($defaultPaths, $paths);

        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->addDirectoryToZip($zip, $path, $path);
            } elseif (is_file($path)) {
                $zip->addFile($path, basename($path));
            }
        }

        $zip->close();
    }

    private function addDirectoryToZip($zip, $dir, $baseDir): void
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . '/' . $file;
            $relativePath = substr($filePath, strlen($baseDir) + 1);
            if (is_dir($filePath)) {
                $zip->addEmptyDir($relativePath);
                $this->addDirectoryToZip($zip, $filePath, $baseDir);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function compressBackup(string $filePath): void
    {
        try {
            $fullPath = storage_path('app/' . $filePath);
            if (file_exists($fullPath)) {
                $compressedPath = $fullPath . '.gz';
                $this->gzipFile($fullPath, $compressedPath);
                unlink($fullPath);
                $backup = BackupHistory::where('file_path', $filePath)->first();
                if ($backup) {
                    $backup->update(['file_path' => $filePath . '.gz']);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Backup compression failed: ' . $e->getMessage());
        }
    }

    private function gzipFile($source, $destination): void
    {
        $fp = gzopen($destination, 'wb9');
        $file = fopen($source, 'rb');
        while (feof($file) == false) {
            gzwrite($fp, fread($file, 1024 * 1024));
        }
        fclose($file);
        gzclose($fp);
    }

    public function restoreBackup(int $backupId): array
    {
        $backup = BackupHistory::with('tenant')->findOrFail($backupId);

        if ($backup->tenant_id && $backup->tenant_id != $this->tenantId) {
            return ['success' => false, 'error' => 'شما دسترسی به این بک‌آپ ندارید'];
        }

        if ($backup->status !== 'completed') {
            return ['success' => false, 'error' => 'Backup file is not valid'];
        }

        $filePath = $backup->file_path;
        $fullPath = storage_path('app/' . $filePath);

        if (file_exists($fullPath) == false) {
            return ['success' => false, 'error' => 'Backup file not found'];
        }

        try {
            if ($backup->type === 'database') {
                $this->restoreDatabase($fullPath);
            } elseif ($backup->type === 'files') {
                $this->restoreFiles($fullPath);
            }

            return [
                'success' => true,
                'message' => 'بک‌آپ با موفقیت بازیابی شد',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function restoreDatabase(string $filePath): void
    {
        if (pathinfo($filePath, PATHINFO_EXTENSION) === 'gz') {
            $content = gzdecode(file_get_contents($filePath));
            $tempFile = tempnam(sys_get_temp_dir(), 'sql');
            file_put_contents($tempFile, $content);
            $filePath = $tempFile;
        }

        $database = config('database.connections.mysql');
        $host = $database['host'];
        $port = $database['port'];
        $dbname = $database['database'];
        $user = $database['username'];
        $password = $database['password'];

        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
            $host,
            $port,
            $user,
            $password,
            $dbname,
            $filePath
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database restore failed');
        }
    }

    private function restoreFiles(string $filePath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $zip->extractTo(storage_path('app/restored'));
            $zip->close();
        } else {
            throw new \Exception('Cannot extract zip file');
        }
    }

    public function cleanupOldBackups(int $days = 30): int
    {
        $query = BackupHistory::where('status', 'completed')
            ->where('created_at', '<', Carbon::now()->subDays($days));

        if ($this->tenantId) {
            $query->where('tenant_id', $this->tenantId);
        }

        $oldBackups = $query->get();
        $count = 0;

        foreach ($oldBackups as $backup) {
            try {
                if ($backup->file_path) {
                    Storage::disk($this->disk)->delete($backup->file_path);
                }
                $backup->delete();
                $count++;
            } catch (\Exception $e) {
                Log::error('Cleanup backup failed: ' . $e->getMessage());
            }
        }

        return $count;
    }

    public function getBackupHistory(array $filters = [], int $perPage = 20)
    {
        $query = BackupHistory::query();

        if ($this->tenantId) {
            $query->where('tenant_id', $this->tenantId);
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
