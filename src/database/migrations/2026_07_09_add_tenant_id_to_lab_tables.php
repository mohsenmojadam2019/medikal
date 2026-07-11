<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اضافه کردن tenant_id به lab_categories
        Schema::table('lab_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_categories', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });

        // اضافه کردن tenant_id به lab_tests
        Schema::table('lab_tests', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_tests', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });

        // اضافه کردن tenant_id به lab_orders
        Schema::table('lab_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_orders', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });

        // اضافه کردن tenant_id به lab_order_tests
        Schema::table('lab_order_tests', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_order_tests', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });

        // اضافه کردن tenant_id به lab_results
        Schema::table('lab_results', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_results', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });

        // اضافه کردن tenant_id به lab_result_files
        Schema::table('lab_result_files', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_result_files', 'tenant_id')) {
                $table->integer('tenant_id')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lab_categories', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
        Schema::table('lab_tests', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
        Schema::table('lab_order_tests', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
        Schema::table('lab_results', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
        Schema::table('lab_result_files', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
