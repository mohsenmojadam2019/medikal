<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            if (!Schema::hasColumn('drugs', 'price')) {
                $table->decimal('price', 15, 2)->default(0)->after('manufacturer');
            }
            if (!Schema::hasColumn('drugs', 'stock')) {
                $table->integer('stock')->default(0)->after('price');
            }
            if (!Schema::hasColumn('drugs', 'requires_prescription')) {
                $table->boolean('requires_prescription')->default(true)->after('stock');
            }
            if (!Schema::hasColumn('drugs', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('requires_prescription');
            }
        });
    }

    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->dropColumn(['price', 'stock', 'requires_prescription', 'is_active']);
        });
    }
};
