<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_list', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->integer('queue_number');
            $table->string('status')->default('waiting'); // waiting, in_progress, completed, cancelled
            $table->string('type')->default('walk_in'); // walk_in, phone, online
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index('queue_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_list');
    }
};
