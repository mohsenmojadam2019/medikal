<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('lab_categories')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->foreignId('clinic_id')->nullable()->constrained('clinics')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('sample_type')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('min_range', 15, 4)->nullable();
            $table->decimal('max_range', 15, 4)->nullable();
            $table->decimal('critical_low', 15, 4)->nullable();
            $table->decimal('critical_high', 15, 4)->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('turnaround_time')->nullable()->comment('ساعت');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_fasting')->default(false);
            $table->integer('fasting_hours')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('code');
            $table->index(['category_id', 'is_active']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
