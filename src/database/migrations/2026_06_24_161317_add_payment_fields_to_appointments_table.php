<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // فقط اگر ستون‌ها وجود نداشته باشند، اضافه کن
            if (!Schema::hasColumn('appointments', 'fee')) {
                $table->decimal('fee', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('appointments', 'discount')) {
                $table->decimal('discount', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('appointments', 'final_price')) {
                $table->decimal('final_price', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['fee', 'discount', 'final_price']);
        });
    }
};
