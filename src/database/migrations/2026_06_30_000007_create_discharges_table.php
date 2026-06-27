<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->string('discharge_number')->unique();
            $table->timestamp('discharge_date');
            $table->text('final_diagnosis')->nullable();
            $table->text('summary')->nullable();
            $table->text('medications_at_discharge')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->timestamp('follow_up_date')->nullable();
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['admission_id', 'status']);
            $table->index('discharge_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discharges');
    }
};
