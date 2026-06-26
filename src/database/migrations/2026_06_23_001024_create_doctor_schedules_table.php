<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');

            $table->tinyInteger('day_of_week'); // 0-6 (شنبه=0)
            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->integer('slot_duration')->default(30); // minutes
            $table->integer('max_slots_per_day')->default(20);

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['doctor_id', 'day_of_week']);
            $table->index(['doctor_id', 'day_of_week', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
