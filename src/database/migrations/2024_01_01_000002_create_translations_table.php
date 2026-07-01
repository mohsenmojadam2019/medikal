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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')
                ->constrained('languages')
                ->onDelete('cascade');
            $table->string('group_name', 100);
            $table->string('key_name', 255);
            $table->text('value');
            $table->boolean('is_plural')->default(false);
            $table->string('plural_rule', 50)->nullable();
            $table->timestamps();
            
            // ایندکس‌های ترکیبی برای جستجوی سریع
            $table->unique(['language_id', 'group_name', 'key_name', 'plural_rule'], 'unique_translation');
            $table->index(['language_id', 'group_name']);
            $table->index('group_name');
            $table->index('key_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
