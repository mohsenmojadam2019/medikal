<?php

namespace Database\Seeders\BI;

use App\Models\BI\BIReport;
use Illuminate\Database\Seeder;

class BISeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // REPORTS
        // ============================================================

        $reports = [
            [
                'name' => 'گزارش نوبت‌های روزانه',
                'slug' => 'daily-appointments',
                'description' => 'گزارش کامل نوبت‌های روزانه با جزئیات',
                'type' => 'predefined',
                'config' => [
                    'source' => 'appointments',
                    'group_by' => 'date',
                    'aggregates' => ['count', 'completed', 'cancelled'],
                ],
                'columns' => ['date', 'doctor', 'patient', 'status', 'time'],
                'chart_type' => 'bar',
                'is_public' => true,
                'created_by' => 1,
            ],
            [
                'name' => 'گزارش درآمد ماهانه',
                'slug' => 'monthly-revenue',
                'description' => 'گزارش درآمد ماهانه کلینیک',
                'type' => 'predefined',
                'config' => [
                    'source' => 'invoices',
                    'group_by' => 'month',
                    'aggregates' => ['sum', 'count'],
                ],
                'columns' => ['month', 'total_revenue', 'total_invoices', 'average'],
                'chart_type' => 'line',
                'is_public' => true,
                'created_by' => 1,
            ],
            [
                'name' => 'گزارش عملکرد پزشکان',
                'slug' => 'doctor-performance',
                'description' => 'تحلیل کامل عملکرد پزشکان',
                'type' => 'predefined',
                'config' => [
                    'source' => 'doctors',
                    'metrics' => ['appointments', 'revenue', 'rating', 'patients'],
                ],
                'columns' => ['doctor', 'appointments', 'revenue', 'rating', 'patients'],
                'chart_type' => 'table',
                'is_public' => false,
                'created_by' => 1,
            ],
        ];

        foreach ($reports as $report) {
            BIReport::updateOrCreate(
                ['slug' => $report['slug']],
                $report
            );
        }

        $this->command->info('✅ گزارش‌های پیش‌فرض BI ایجاد شدند.');
    }
}
