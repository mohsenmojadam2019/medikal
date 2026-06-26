<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('pharmacy_orders')->onDelete('cascade');
            $table->foreignId('drug_id')->constrained('drugs')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->text('unavailable_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_order_items');
    }
};
