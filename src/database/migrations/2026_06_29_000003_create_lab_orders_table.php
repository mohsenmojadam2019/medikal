<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('lab_technician_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('pending');
            $table->string('priority')->default('routine');
            $table->string('sample_type')->nullable();

            $table->timestamp('sample_collected_at')->nullable();
            $table->timestamp('sample_received_at')->nullable();
            $table->timestamp('result_ready_at')->nullable();

            $table->text('notes')->nullable();
            $table->text('clinical_history')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_reason')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
            $table->index('order_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_orders');
    }
};
