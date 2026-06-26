<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');

            $table->string('code')->unique();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->integer('duration')->default(30);

            $table->enum('status', [
                'pending', 'confirmed', 'arrived',
                'in_progress', 'completed', 'cancelled', 'no_show'
            ])->default('pending');

            $table->enum('type', ['in_person', 'online', 'home_visit'])->default('in_person');

            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('final_price', 15, 2)->default(0);

            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_id')->nullable();

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['doctor_id', 'date']);
            $table->index(['patient_id', 'date']);
            $table->index('status');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
