<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->onDelete('cascade');
            $table->boolean('enable_installments')->default(true);
            $table->integer('max_installments')->default(12);
            $table->integer('min_installment_amount')->default(100000);
            $table->decimal('default_interest_rate', 5, 2)->default(0);
            $table->decimal('default_penalty_rate', 5, 2)->default(2);
            $table->integer('grace_days')->default(3);
            $table->json('available_gateways')->nullable();
            $table->boolean('require_down_payment')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_settings');
    }
};
