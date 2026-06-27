<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receptionist_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->onDelete('cascade');
            $table->boolean('allow_walk_in')->default(true);
            $table->boolean('allow_phone_booking')->default(true);
            $table->boolean('print_appointment_card')->default(true);
            $table->integer('max_walk_in_per_day')->default(10);
            $table->integer('default_appointment_duration')->default(30);
            $table->json('notification_settings')->nullable();
            $table->json('display_settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receptionist_settings');
    }
};
