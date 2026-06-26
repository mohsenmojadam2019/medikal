<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');

            $table->string('code')->unique();
            $table->string('drug_name');
            $table->string('dosage');
            $table->integer('frequency'); // تعداد در روز
            $table->integer('duration'); // روز
            $table->date('start_date');
            $table->date('end_date');

            $table->text('instructions')->nullable();
            $table->text('diagnosis')->nullable();

            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
