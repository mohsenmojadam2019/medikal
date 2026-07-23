<?php
// database/migrations/2026_07_23_000002_add_prescription_fields_to_pharmacy_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            // فیلدهای نسخه پزشکی
            $table->string('prescription_file')->nullable()->after('notes');

            $table->enum('prescription_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('prescription_file');
            $table->text('prescription_reject_reason')->nullable()->after('prescription_status');
            $table->timestamp('prescription_approved_at')->nullable()->after('prescription_reject_reason');
            $table->timestamp('prescription_rejected_at')->nullable()->after('prescription_approved_at');
            $table->unsignedBigInteger('prescription_approved_by')->nullable()->after('prescription_rejected_at');
            $table->decimal('tax', 15, 2)->default(0)->after('delivery_fee');

        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            $table->dropColumn([
                'prescription_file',
                'prescription_status',
                'prescription_reject_reason',
                'prescription_approved_at',
                'prescription_rejected_at',
                'prescription_approved_by',
            ]);
        });
    }
};
