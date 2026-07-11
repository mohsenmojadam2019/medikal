<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اضافه کردن tenant_id به pharmacy_orders
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pharmacy_orders', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });

        // اضافه کردن tenant_id به pharmacy_order_items
        Schema::table('pharmacy_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pharmacy_order_items', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });

        // اضافه کردن tenant_id به pharmacy_notifications
        Schema::table('pharmacy_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('pharmacy_notifications', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });

        // اضافه کردن tenant_id به pharmacies
        Schema::table('pharmacies', function (Blueprint $table) {
            if (!Schema::hasColumn('pharmacies', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
        Schema::table('pharmacy_order_items', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
        Schema::table('pharmacy_notifications', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
