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
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            $table->decimal('request_latitude', 10, 7)->nullable();
            $table->decimal('request_longitude', 10, 7)->nullable();
            $table->text('request_address')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('ambulance_number', 50)->nullable();
            $table->string('ambulance_team', 100)->nullable();

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
