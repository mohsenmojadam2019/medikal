<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            // تغییر doctor_id به nullable
            if (Schema::hasColumn('lab_orders', 'doctor_id')) {
                $table->foreignId('doctor_id')->nullable()->change();
            }
            // تغییر appointment_id به nullable
            if (Schema::hasColumn('lab_orders', 'appointment_id')) {
                $table->foreignId('appointment_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            if (Schema::hasColumn('lab_orders', 'doctor_id')) {
                $table->foreignId('doctor_id')->nullable(false)->change();
            }
            if (Schema::hasColumn('lab_orders', 'appointment_id')) {
                $table->foreignId('appointment_id')->nullable(false)->change();
            }
        });
    }
};
