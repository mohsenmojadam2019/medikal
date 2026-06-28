<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgery_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operation_room_id')->constrained('operation_rooms')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('surgeon_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('anesthesiologist_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('assistant_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();

            $table->string('surgery_type');
            $table->text('diagnosis')->nullable();
            $table->text('procedure')->nullable();
            $table->string('priority')->default('routine');
            $table->date('scheduled_date');
            $table->timestamp('scheduled_time');
            $table->integer('estimated_duration')->default(60);
            $table->integer('actual_duration')->nullable();

            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->text('pre_op_notes')->nullable();
            $table->text('post_op_notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['operation_room_id', 'scheduled_date']);
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'scheduled_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_schedules');
    }
};
