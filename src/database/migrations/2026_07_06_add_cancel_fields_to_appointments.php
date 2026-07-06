<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'cancellation_fee')) {
                $table->decimal('cancellation_fee', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('appointments', 'refund_amount')) {
                $table->decimal('refund_amount', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['cancelled_at', 'cancellation_fee', 'refund_amount']);
        });
    }
};
