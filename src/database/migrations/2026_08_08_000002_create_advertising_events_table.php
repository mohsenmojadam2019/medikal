<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('advertising_events', function (Blueprint $table) { $table->id(); $table->string('campaign_key',100)->index(); $table->string('event_type',20)->index(); $table->string('placement',100); $table->string('locale',5)->nullable(); $table->string('ip_address',45)->nullable(); $table->string('user_agent',500)->nullable(); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('advertising_events'); }
};
