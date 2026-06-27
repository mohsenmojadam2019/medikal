<?php

namespace App\Console\Commands;

use App\Models\LabResultFile;
use Illuminate\Console\Command;
use Carbon\Carbon;

class LabCleanupFilesCommand extends Command
{
    protected $signature = 'lab:cleanup-files {--days=30 : Number of days to keep files}';
    protected $description = 'Clean up old lab result files';

    public function handle(): void
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up lab files older than {$days} days...");

        $files = LabResultFile::where('created_at', '<', $cutoffDate)->get();
        $count = $files->count();

        foreach ($files as $file) {
            // Delete physical file
            if (\Storage::exists($file->file_path)) {
                \Storage::delete($file->file_path);
            }
            // Delete record
            $file->delete();
        }

        $this->info("Deleted {$count} old files.");
    }
}
