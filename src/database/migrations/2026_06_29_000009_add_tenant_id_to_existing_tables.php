<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'doctors', 'patients', 'appointments', 'prescriptions', 
            'invoices', 'payments', 'notifications', 'messages',
            'posts', 'post_categories', 'post_comments', 'surveys',
            'survey_responses', 'feedbacks', 'ehr_records', 'ehr_visits',
            'medical_documents', 'medical_alerts', 'patient_vaccinations',
            'vaccination_reminders', 'events', 'event_registrations',
            'campaigns', 'campaign_interactions', 'telemedicine_sessions',
            'telemedicine_messages', 'telemedicine_files', 'medical_notes',
            'waiting_list', 'phone_appointments', 'appointment_cards',
            'drugs', 'ratings', 'referrals', 'reminders',
            'installment_contracts', 'installments', 'wallet_transactions'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'tenant_id')) {
                        $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->onDelete('cascade');
                        $table->index('tenant_id');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // برگشت داده نمیشه چون داده‌ها رو از دست می‌دیم
    }
};
