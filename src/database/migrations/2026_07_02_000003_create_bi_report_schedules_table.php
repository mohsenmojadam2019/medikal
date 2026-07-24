<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bi_report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bi_report_id')->constrained('bi_reports')->onDelete('cascade');
            $table->string('frequency'); // daily, weekly, monthly, quarterly
            $table->json('recipients');
            $table->string('format')->default('pdf'); // pdf, excel, csv
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['bi_report_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_report_schedules');
    }
};
