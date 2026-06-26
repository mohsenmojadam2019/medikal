<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('slug', 100)->unique();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['province_id', 'name']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
