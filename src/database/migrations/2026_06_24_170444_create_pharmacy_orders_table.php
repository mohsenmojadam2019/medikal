<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->onDelete('cascade');
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');

            $table->enum('status', [
                'pending', 'checking', 'partial_available', 'all_available',
                'payment_pending', 'paid', 'preparing', 'ready', 'delivered', 'cancelled'
            ])->default('pending');

            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('insurance_share', 15, 2)->default(0);
            $table->decimal('patient_share', 15, 2)->default(0);
            $table->decimal('delivery_fee', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->string('payment_link')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->json('available_items')->nullable();
            $table->json('unavailable_items')->nullable();
            $table->json('metadata')->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_orders');
    }
};
