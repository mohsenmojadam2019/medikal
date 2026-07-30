<?php
// database/migrations/2026_07_30_000007_create_product_reviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->integer('rating')->unsigned(); // 1-5
            $table->text('comment')->nullable();
            $table->json('pros')->nullable(); // نکات مثبت
            $table->json('cons')->nullable(); // نکات منفی

            $table->boolean('is_approved')->default(false);
            $table->boolean('is_purchased')->default(false); // خریدار واقعی

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'user_id']);
            $table->index(['product_id', 'rating']);
            $table->index(['product_id', 'is_approved']);
            $table->index(['user_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
