<?php
// database/migrations/xxxx_create_doctors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->onDelete('set null');
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->onDelete('set null');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();

            $table->string('license_number')->unique();


            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('bio')->nullable();
            $table->text('biography')->nullable();
            $table->json('education')->nullable();
            $table->json('certificates')->nullable();
            $table->json('social_links')->nullable();
            $table->json('working_hours')->nullable();
            $table->integer('experience_years')->default(0);

            $table->decimal('consultation_fee', 15, 2)->default(0);

            // هزینه نوبت
            $table->enum('appointment_fee_type', ['free', 'paid'])->default('paid');
            $table->decimal('appointment_fee_amount', 15, 2)->nullable();
            $table->boolean('is_fee_editable_by_admin')->default(true);

            $table->integer('visit_duration')->default(30);

            $table->boolean('is_available')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);

            $table->json('metadata')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_available', 'is_verified']);
            $table->index('license_number');
            $table->index('specialty_id');
            $table->index('clinic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
