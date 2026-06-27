<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type')->default('health'); // health, awareness, screening, vaccination
            $table->string('status')->default('draft'); // draft, active, paused, completed, cancelled
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('target_audience')->nullable();
            $table->integer('target_count')->nullable();
            $table->integer('current_count')->default(0);
            $table->json('channels')->nullable(); // sms, email, push, social
            $table->json('content')->nullable();
            $table->json('statistics')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
            $table->index(['type', 'status']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
