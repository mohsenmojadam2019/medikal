<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->integer('day_number');
            $table->date('date');

            // Vital Signs
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('heart_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->integer('blood_pressure_systolic')->nullable();
            $table->integer('blood_pressure_diastolic')->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->integer('pain_score')->nullable();

            // Body Measurements
            $table->decimal('weight', 6, 1)->nullable();
            $table->decimal('height', 6, 1)->nullable();
            $table->decimal('bmi', 5, 1)->nullable();

            $table->string('consciousness_level')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['admission_id', 'day_number']);
            $table->index(['admission_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_days');
    }
};
