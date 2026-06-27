<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bi_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // appointment_prediction, revenue_forecast, patient_segment, doctor_performance
            $table->string('name');
            $table->json('data');
            $table->json('metadata')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['type', 'calculated_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_analytics');
    }
};
