<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->index();
            $table->string('locale', 5)->default('fa');
            $table->string('name', 120);
            $table->string('phone', 30)->index();
            $table->string('email', 190)->nullable();
            $table->string('subject', 190);
            $table->text('message');
            $table->string('status', 30)->default('new')->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_inquiries');
    }
};
