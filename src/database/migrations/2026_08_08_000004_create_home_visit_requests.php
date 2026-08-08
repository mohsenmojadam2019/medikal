<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::create('home_visit_requests',function(Blueprint $t){$t->id();$t->foreignId('patient_id')->constrained()->cascadeOnDelete();$t->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();$t->string('request_number')->unique();$t->string('status')->default('pending');$t->text('address');$t->decimal('patient_latitude',10,7);$t->decimal('patient_longitude',10,7);$t->decimal('doctor_latitude',10,7)->nullable();$t->decimal('doctor_longitude',10,7)->nullable();$t->timestamp('scheduled_at')->nullable();$t->timestamp('last_location_at')->nullable();$t->text('symptoms')->nullable();$t->string('contact_phone',30);$t->decimal('amount',14,2)->default(0);$t->string('payment_status')->default('pending');$t->json('metadata')->nullable();$t->timestamps();$t->softDeletes();$t->index(['patient_id','status']);$t->index(['doctor_id','status']);});}
 public function down():void{Schema::dropIfExists('home_visit_requests');}
};