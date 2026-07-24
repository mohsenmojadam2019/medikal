<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_history', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // database, files, full
            $table->string('status'); // pending, running, completed, failed
            $table->string('file_path')->nullable();
            $table->string('file_size')->nullable();
            $table->integer('duration')->nullable(); // seconds
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_history');
    }
};
