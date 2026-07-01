<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('translation_fallbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')
                ->constrained('languages')
                ->onDelete('cascade');
            $table->foreignId('fallback_language_id')
                ->constrained('languages')
                ->onDelete('cascade');
            $table->timestamps();
            
            // هر زبان فقط یک fallback می‌تواند داشته باشد
            $table->unique('language_id');
            
            // ایندکس‌ها
            $table->index('fallback_language_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_fallbacks');
    }
};
