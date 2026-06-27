<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();

            $table->string('image_type');
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();

            // DICOM fields
            $table->string('study_uid')->nullable();
            $table->string('series_uid')->nullable();
            $table->string('instance_uid')->nullable();
            $table->string('body_part')->nullable();
            $table->string('modality')->nullable();

            $table->text('description')->nullable();
            $table->timestamp('study_date')->nullable();
            $table->text('report')->nullable();

            $table->boolean('is_confidential')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'image_type']);
            $table->index(['patient_id', 'study_date']);
            $table->index('study_uid');
            $table->index('modality');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_images');
    }
};
