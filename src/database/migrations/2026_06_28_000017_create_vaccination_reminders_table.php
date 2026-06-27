<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccination_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('vaccine_id')->constrained('vaccines')->onDelete('cascade');
            $table->foreignId('patient_vaccination_id')->nullable()->constrained('patient_vaccinations')->onDelete('set null');
            $table->date('reminder_date');
            $table->string('type')->default('next_dose'); // next_dose, follow_up
            $table->string('status')->default('pending'); // pending, sent, completed
            $table->timestamp('sent_at')->nullable();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'reminder_date']);
            $table->index(['status', 'reminder_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccination_reminders');
    }
};
