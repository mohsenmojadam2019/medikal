<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('transaction_id')->unique();
            $table->string('gateway'); // zarinpal, asanpardakht, local
            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('IRT');
            
            $table->string('status')->default('pending'); // pending, success, failed, refunded
            $table->string('reference_code')->nullable();
            $table->text('description')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('transaction_id');
            $table->index('reference_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
