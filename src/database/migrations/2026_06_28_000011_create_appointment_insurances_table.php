<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('patient_insurance_id')->constrained('patient_insurances')->onDelete('cascade');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('insurance_share', 15, 2);
            $table->decimal('patient_share', 15, 2);
            $table->decimal('deductible', 15, 2)->default(0);
            $table->string('status')->default('pending'); // pending, approved, rejected, paid
            $table->string('claim_number')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'status']);
            $table->index('claim_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_insurances');
    }
};
