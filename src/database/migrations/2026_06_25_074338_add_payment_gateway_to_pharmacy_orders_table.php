<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pharmacy_orders', 'payment_gateway')) {
                $table->string('payment_gateway')->nullable()->after('payment_link');
            }
            if (!Schema::hasColumn('pharmacy_orders', 'payment_authority')) {
                $table->string('payment_authority')->nullable()->after('payment_gateway');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'payment_authority']);
        });
    }
};
