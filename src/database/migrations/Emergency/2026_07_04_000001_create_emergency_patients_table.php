<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();

            $table->string('triage_level');
            $table->timestamp('arrival_time');
            $table->text('chief_complaint')->nullable();
            $table->text('history_of_present_illness')->nullable();
            $table->json('vital_signs')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medications')->nullable();
            $table->text('past_medical_history')->nullable();

            $table->string('status')->default('waiting');
            $table->string('disposition')->nullable();
            $table->timestamp('disposition_time')->nullable();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['triage_level', 'arrival_time']);
            $table->index('arrival_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_patients');
    }
};
