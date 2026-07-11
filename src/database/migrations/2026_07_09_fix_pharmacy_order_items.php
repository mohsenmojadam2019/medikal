<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_order_items', function (Blueprint $table) {
            // اضافه کردن tenant_id اگر وجود ندارد
            if (!Schema::hasColumn('pharmacy_order_items', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
            
            // تغییر unavailable_reason به nullable اگر not nullable است
            if (Schema::hasColumn('pharmacy_order_items', 'unavailable_reason')) {
                $table->text('unavailable_reason')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_order_items', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
