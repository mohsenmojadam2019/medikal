<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('manufacturer');
            $table->string('disease');
            $table->integer('doses_required')->default(1);
            $table->integer('interval_days')->nullable(); // فاصله بین دوزها
            $table->integer('age_min_months')->nullable();
            $table->integer('age_max_months')->nullable();
            $table->text('description')->nullable();
            $table->text('side_effects')->nullable();
            $table->string('storage_condition')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('disease');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccines');
    }
};
