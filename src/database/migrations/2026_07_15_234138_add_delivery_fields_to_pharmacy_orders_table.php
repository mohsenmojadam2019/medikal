<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            // اضافه کردن فیلدهای اطلاعات تحویل
            $table->string('recipient_name')->nullable()->after('patient_id');
            $table->string('recipient_phone', 20)->nullable()->after('recipient_name');
            $table->text('delivery_address')->nullable()->after('recipient_phone');
            $table->text('delivery_notes')->nullable()->after('delivery_address');
        });
    }

    public function down()
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_name',
                'recipient_phone',
                'delivery_address',
                'delivery_notes'
            ]);
        });
    }
};
