<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // اضافه کردن فیلدهای موقعیت به جدول doctors
        Schema::table('doctors', function (Blueprint $table) {
            if (!Schema::hasColumn('doctors', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('clinic_address');
            }
            if (!Schema::hasColumn('doctors', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('doctors', 'clinic_phone')) {
                $table->string('clinic_phone', 20)->nullable()->after('clinic_address');
            }
            if (!Schema::hasColumn('doctors', 'clinic_email')) {
                $table->string('clinic_email', 255)->nullable()->after('clinic_phone');
            }
            if (!Schema::hasColumn('doctors', 'profile_image')) {
                $table->string('profile_image', 255)->nullable()->after('clinic_email');
            }
            if (!Schema::hasColumn('doctors', 'bio')) {
                $table->text('bio')->nullable()->after('profile_image');
            }
            if (!Schema::hasColumn('doctors', 'education')) {
                $table->json('education')->nullable()->after('bio');
            }
            if (!Schema::hasColumn('doctors', 'certificates')) {
                $table->json('certificates')->nullable()->after('education');
            }
            if (!Schema::hasColumn('doctors', 'social_links')) {
                $table->json('social_links')->nullable()->after('certificates');
            }
            if (!Schema::hasColumn('doctors', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('doctors', 'license_number')) {
                $table->string('license_number', 50)->unique()->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('doctors', 'working_hours')) {
                $table->json('working_hours')->nullable()->after('social_links');
            }
        });

        // اضافه کردن فیلدهای موقعیت به جدول clinics (اگر وجود داشته باشد)
        if (Schema::hasTable('clinics')) {
            Schema::table('clinics', function (Blueprint $table) {
                if (!Schema::hasColumn('clinics', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('address');
                }
                if (!Schema::hasColumn('clinics', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn([
                'latitude', 'longitude', 'clinic_phone', 'clinic_email',
                'profile_image', 'bio', 'education', 'certificates',
                'social_links', 'working_hours'
            ]);
        });

        if (Schema::hasTable('clinics')) {
            Schema::table('clinics', function (Blueprint $table) {
                $table->dropColumn(['latitude', 'longitude']);
            });
        }
    }
};
