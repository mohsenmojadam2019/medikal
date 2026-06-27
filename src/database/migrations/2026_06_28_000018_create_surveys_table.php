<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('appointment'); // appointment, general, doctor
            $table->boolean('is_active')->default(true);
            $table->json('questions')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('max_attempts')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'type']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
