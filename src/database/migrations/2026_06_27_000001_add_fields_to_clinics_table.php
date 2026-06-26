<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            // فیلدهای جدید
            if (!Schema::hasColumn('clinics', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('name');
            }
            if (!Schema::hasColumn('clinics', 'website')) {
                $table->string('website')->nullable()->after('email');
            }
            if (!Schema::hasColumn('clinics', 'logo')) {
                $table->string('logo')->nullable()->after('website');
            }
            if (!Schema::hasColumn('clinics', 'favicon')) {
                $table->string('favicon')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('clinics', 'timezone')) {
                $table->string('timezone')->default('Asia/Tehran')->after('favicon');
            }
            if (!Schema::hasColumn('clinics', 'currency')) {
                $table->string('currency')->default('تومان')->after('timezone');
            }
            if (!Schema::hasColumn('clinics', 'language')) {
                $table->string('language')->default('fa')->after('currency');
            }
            if (!Schema::hasColumn('clinics', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(9)->after('language');
            }
            if (!Schema::hasColumn('clinics', 'invoice_prefix')) {
                $table->string('invoice_prefix')->default('INV')->after('tax_rate');
            }
            if (!Schema::hasColumn('clinics', 'appointment_prefix')) {
                $table->string('appointment_prefix')->default('APP')->after('invoice_prefix');
            }
            if (!Schema::hasColumn('clinics', 'primary_color')) {
                $table->string('primary_color')->default('#2b6cb0')->after('appointment_prefix');
            }
            if (!Schema::hasColumn('clinics', 'secondary_color')) {
                $table->string('secondary_color')->default('#ed8936')->after('primary_color');
            }
            if (!Schema::hasColumn('clinics', 'theme')) {
                $table->string('theme')->default('default')->after('secondary_color');
            }
            if (!Schema::hasColumn('clinics', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('clinics', 'settings')) {
                $table->json('settings')->nullable()->after('metadata');
            }
        });

        // برای کلینیک موجود، slug بساز
        \App\Models\Clinic::whereNull('slug')->each(function ($clinic) {
            $clinic->slug = \Illuminate\Support\Str::slug($clinic->name) . '-' . $clinic->id;
            $clinic->save();
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'website', 'logo', 'favicon', 'timezone',
                'currency', 'language', 'tax_rate', 'invoice_prefix',
                'appointment_prefix', 'primary_color', 'secondary_color',
                'theme', 'is_verified', 'settings'
            ]);
        });
    }
};
