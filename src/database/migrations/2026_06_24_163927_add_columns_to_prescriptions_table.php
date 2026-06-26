<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            // اضافه کردن ستون‌های جدید
            if (!Schema::hasColumn('prescriptions', 'notes')) {
                $table->text('notes')->nullable()->after('instructions');
            }
            if (!Schema::hasColumn('prescriptions', 'side_effects')) {
                $table->text('side_effects')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('prescriptions', 'metadata')) {
                $table->json('metadata')->nullable()->after('side_effects');
            }
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['notes', 'side_effects', 'metadata']);
        });
    }
};
