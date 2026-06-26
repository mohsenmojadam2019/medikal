<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ratings')) {
            Schema::create('ratings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
                $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
                $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');

                $table->tinyInteger('score')->unsigned();
                $table->text('comment')->nullable();
                $table->json('categories')->nullable();
                $table->boolean('is_anonymous')->default(false);

                $table->timestamps();

                $table->index(['doctor_id', 'score']);
                $table->index('patient_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
