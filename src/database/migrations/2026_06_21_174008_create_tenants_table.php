<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('logo')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('subdomain')->nullable()->unique();

            // Subscription
            $table->string('subscription_status')->default('trial'); // trial, active, inactive, expired, cancelled
            $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Limits
            $table->integer('max_doctors')->default(2);
            $table->integer('max_patients')->default(50);
            $table->integer('max_appointments_per_day')->default(10);
            $table->integer('max_prescriptions_per_month')->default(5);

            // Features
            $table->json('features')->nullable();
            $table->json('settings')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('subdomain');
            $table->index('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
