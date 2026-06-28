<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('status')->default('active'); // active, expired, cancelled, trial
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->string('payment_gateway')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('invoice_number')->nullable();
            
            $table->json('metadata')->nullable();
            
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
