<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained('lab_orders')->onDelete('cascade');
            $table->foreignId('lab_order_test_id')->nullable()->constrained('lab_order_tests')->nullOnDelete();
            $table->foreignId('lab_test_id')->constrained('lab_tests')->onDelete('cascade');

            $table->decimal('value', 15, 4)->nullable();
            $table->decimal('range_low', 15, 4)->nullable();
            $table->decimal('range_high', 15, 4)->nullable();
            $table->string('unit')->nullable();

            $table->string('status')->default('pending');
            $table->boolean('is_abnormal')->default(false);
            $table->boolean('is_critical')->default(false);

            $table->text('comment')->nullable();
            $table->text('interpretation')->nullable();

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['lab_order_id', 'lab_test_id']);
            $table->index(['is_abnormal', 'is_critical']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
