<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 11);
            $table->string('code', 4);
            $table->string('type')->default('login');
            $table->integer('attempts')->default(0);
            $table->boolean('is_used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['mobile', 'code']);
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp_codes');
    }
};
