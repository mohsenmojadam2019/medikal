<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ratings')) {
            Schema::table('ratings', function (Blueprint $table) {
                if (!Schema::hasColumn('ratings', 'reply')) {
                    $table->text('reply')->nullable()->after('comment');
                }
                if (!Schema::hasColumn('ratings', 'replied_at')) {
                    $table->timestamp('replied_at')->nullable()->after('reply');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ratings')) {
            Schema::table('ratings', function (Blueprint $table) {
                $table->dropColumn(['reply', 'replied_at']);
            });
        }
    }
};
