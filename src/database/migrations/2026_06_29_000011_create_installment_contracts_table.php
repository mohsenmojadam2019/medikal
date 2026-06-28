<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->string('contract_number')->unique();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('down_payment', 15, 2)->default(0);
            $table->decimal('installment_amount', 15, 2);
            $table->integer('number_of_installments');
            $table->integer('installments_paid')->default(0);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->decimal('total_interest', 15, 2)->default(0);
            $table->decimal('penalty_rate', 5, 2)->default(2);
            $table->string('gateway')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index('contract_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_contracts');
    }
};
