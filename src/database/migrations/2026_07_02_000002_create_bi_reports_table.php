<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bi_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type'); // custom, predefined
            $table->json('config');
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->string('chart_type')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_reports');
    }
};
