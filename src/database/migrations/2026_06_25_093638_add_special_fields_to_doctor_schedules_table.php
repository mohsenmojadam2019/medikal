<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('doctor_schedules', 'is_special')) {
                $table->boolean('is_special')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('doctor_schedules', 'special_date')) {
                $table->date('special_date')->nullable()->after('is_special');
            }
            if (!Schema::hasColumn('doctor_schedules', 'special_reason')) {
                $table->string('special_reason', 255)->nullable()->after('special_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropColumn(['is_special', 'special_date', 'special_reason']);
        });
    }
};
