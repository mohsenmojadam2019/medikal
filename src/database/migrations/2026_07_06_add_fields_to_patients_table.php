<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'address')) {
                $table->text('address')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('patients', 'insurance_type')) {
                $table->string('insurance_type', 50)->nullable()->after('address');
            }
            if (!Schema::hasColumn('patients', 'insurance_number')) {
                $table->string('insurance_number', 50)->nullable()->after('insurance_type');
            }
            if (!Schema::hasColumn('patients', 'doctor_id')) {
                $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn(['address', 'insurance_type', 'insurance_number', 'doctor_id']);
        });
    }
};
