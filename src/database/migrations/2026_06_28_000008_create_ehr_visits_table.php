<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ehr_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ehr_record_id')->constrained('ehr_records')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->string('visit_type'); // initial, follow_up, emergency, consultation
            $table->text('chief_complaint')->nullable(); // شکایت اصلی
            $table->text('history_of_present_illness')->nullable(); // تاریخچه بیماری فعلی
            $table->text('past_medical_history')->nullable(); // سوابق پزشکی
            $table->text('family_history')->nullable(); // سوابق خانوادگی
            $table->text('social_history')->nullable(); // سوابق اجتماعی
            $table->text('physical_exam')->nullable(); // معاینه فیزیکی
            $table->text('assessment')->nullable(); // ارزیابی
            $table->text('plan')->nullable(); // برنامه درمانی
            $table->text('notes')->nullable();
            $table->json('vital_signs')->nullable(); // علائم حیاتی
            $table->timestamp('visit_date')->nullable();
            $table->timestamps();

            $table->index(['ehr_record_id', 'visit_date']);
            $table->index('doctor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ehr_visits');
    }
};
