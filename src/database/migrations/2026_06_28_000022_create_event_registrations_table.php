<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('registration_code')->unique();
            $table->string('status')->default('pending'); // pending, confirmed, attended, cancelled
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'patient_id']);
            $table->index(['event_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index('registration_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
