<?php
// database/migrations/2026_07_30_000003_create_product_attributes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name'); // کارایی، فرم محصول، حجم، ...
            $table->string('slug')->unique();
            $table->string('type'); // select, text, number, color, ...
            $table->json('options')->nullable(); // گزینه‌ها برای select
            $table->boolean('is_filterable')->default(true);
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_filterable', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
