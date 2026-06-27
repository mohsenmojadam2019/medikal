<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_drugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->string('drug_name');
            $table->string('dosage');
            $table->integer('frequency')->default(1);
            $table->string('route')->default('oral');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->foreignId('prescribed_by')->nullable()->constrained('doctors')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['admission_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_drugs');
    }
};
