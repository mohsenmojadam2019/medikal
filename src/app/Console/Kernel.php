    protected function schedule(Schedule $schedule)
    {
        // اجرای هر ۵ دقیقه
        $schedule->job(new \App\Jobs\ProcessRemindersJob())->everyFiveMinutes();

        // اجرای هر دقیقه برای دقت بیشتر
        $schedule->command('reminders:process')->everyMinute();
    }
