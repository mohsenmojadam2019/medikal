<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            if (!Schema::hasColumn('clinics', 'webhook_enabled')) {
                $table->boolean('webhook_enabled')->default(false)->after('is_verified');
            }
            if (!Schema::hasColumn('clinics', 'webhook_secret')) {
                $table->string('webhook_secret')->nullable()->after('webhook_enabled');
            }
            if (!Schema::hasColumn('clinics', 'webhook_logs')) {
                $table->json('webhook_logs')->nullable()->after('webhook_secret');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['webhook_enabled', 'webhook_secret', 'webhook_logs']);
        });
    }
};
