<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'appointment_fee_type')) {
                $table->enum('appointment_fee_type', ['free', 'paid'])->default('paid')->after('consultation_fee');
            }
            if (!Schema::hasColumn('doctors', 'appointment_fee_amount')) {
                $table->decimal('appointment_fee_amount', 15, 2)->nullable()->after('appointment_fee_type');
            }
            if (!Schema::hasColumn('doctors', 'is_fee_editable_by_admin')) {
                $table->boolean('is_fee_editable_by_admin')->default(true)->after('appointment_fee_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['appointment_fee_type', 'appointment_fee_amount', 'is_fee_editable_by_admin']);
        });
    }
};
