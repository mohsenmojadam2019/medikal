<?php

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
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->onDelete('set null');
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->onDelete('set null');

            $table->string('license_number')->unique();
            $table->string('clinic_name')->nullable();
            $table->text('clinic_address')->nullable();
            $table->string('clinic_phone')->nullable();
            $table->string('clinic_email')->nullable();

            $table->text('biography')->nullable();
            $table->json('education')->nullable();
            $table->integer('experience_years')->default(0);

            $table->decimal('consultation_fee', 15, 2)->default(0);
            $table->integer('visit_duration')->default(30); // minutes

            $table->boolean('is_available')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);

            $table->json('metadata')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_available', 'is_verified']);
            $table->index('license_number');
            $table->index('specialty_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
