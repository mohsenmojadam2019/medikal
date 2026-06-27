<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_form_id')->constrained('digital_forms')->onDelete('cascade');
            $table->foreignId('form_response_id')->nullable()->constrained('form_responses')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('signature_data')->nullable();
            $table->string('signature_image')->nullable();

            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('signed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['digital_form_id', 'patient_id']);
            $table->index('signed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_signatures');
    }
};
