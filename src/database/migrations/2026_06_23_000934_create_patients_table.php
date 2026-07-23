<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            $table->string('national_code')->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('emergency_contact', 15)->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->decimal('latitude', 10, 7)->nullable();

            $table->decimal('longitude', 10, 7)->nullable();


            $table->boolean('is_active')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();

            $table->softDeletes();
            $table->timestamps();
            $table->index('province_id');
            $table->index('city_id');
            $table->index('national_code');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
