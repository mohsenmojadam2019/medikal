<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_sessions', fn (Blueprint $table) => $table->string('provider')->nullable()->after('status'));
        Schema::table('chat_messages', fn (Blueprint $table) => $table->string('provider')->nullable()->after('content'));
        Schema::create('ai_user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('ai_provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('model')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_preferences');
        Schema::table('chat_messages', fn (Blueprint $table) => $table->dropColumn('provider'));
        Schema::table('chat_sessions', fn (Blueprint $table) => $table->dropColumn('provider'));
    }
};
