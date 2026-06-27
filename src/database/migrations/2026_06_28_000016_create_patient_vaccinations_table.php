<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('vaccine_id')->constrained('vaccines')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->integer('dose_number');
            $table->date('administration_date');
            $table->date('next_due_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('administration_site')->nullable(); // محل تزریق
            $table->string('status')->default('completed'); // scheduled, completed, missed, cancelled
            $table->text('reaction_notes')->nullable(); // عوارض
            $table->boolean('is_valid')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'vaccine_id']);
            $table->index(['patient_id', 'next_due_date']);
            $table->index('administration_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_vaccinations');
    }
};
