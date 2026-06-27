<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_archives', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_size')->nullable();
            $table->string('type'); // laravel, audit, backup
            $table->date('date');
            $table->boolean('is_compressed')->default(false);
            $table->timestamp('archived_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'date']);
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_archives');
    }
};
