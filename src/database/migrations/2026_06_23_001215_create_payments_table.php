<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');

            $table->string('transaction_id')->unique();
            $table->string('reference_code')->nullable();

            $table->decimal('amount', 15, 2);
            $table->string('gateway'); // پویا برای هر درگاهی

            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');

            $table->string('authority')->nullable();
            $table->text('message')->nullable();
            $table->json('raw_data')->nullable();

            $table->timestamp('payment_date')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index('transaction_id');
            $table->index('reference_code');
            $table->index('gateway');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
