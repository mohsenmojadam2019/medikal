<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_notes', function (Blueprint $table) {
            // اضافه کردن فیلدهای SOAP اگر وجود ندارند
            if (!Schema::hasColumn('medical_notes', 'subjective')) {
                $table->text('subjective')->nullable()->after('content');
            }
            if (!Schema::hasColumn('medical_notes', 'objective')) {
                $table->text('objective')->nullable()->after('subjective');
            }
            if (!Schema::hasColumn('medical_notes', 'assessment')) {
                $table->text('assessment')->nullable()->after('objective');
            }
            if (!Schema::hasColumn('medical_notes', 'plan')) {
                $table->text('plan')->nullable()->after('assessment');
            }
            
            // فیلدهای ساختاریافته
            if (!Schema::hasColumn('medical_notes', 'diagnoses')) {
                $table->json('diagnoses')->nullable()->after('plan');
            }
            if (!Schema::hasColumn('medical_notes', 'prescriptions')) {
                $table->json('prescriptions')->nullable()->after('diagnoses');
            }
            if (!Schema::hasColumn('medical_notes', 'lab_requests')) {
                $table->json('lab_requests')->nullable()->after('prescriptions');
            }
            if (!Schema::hasColumn('medical_notes', 'imaging_requests')) {
                $table->json('imaging_requests')->nullable()->after('lab_requests');
            }
            if (!Schema::hasColumn('medical_notes', 'referrals')) {
                $table->json('referrals')->nullable()->after('imaging_requests');
            }
            
            // وضعیت
            if (!Schema::hasColumn('medical_notes', 'note_status')) {
                $table->enum('note_status', ['draft', 'final', 'shared'])->default('draft')->after('is_shared');
            }
            
            // content رو nullable کن اگر نیست
            if (Schema::hasColumn('medical_notes', 'content')) {
                $table->text('content')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('medical_notes', function (Blueprint $table) {
            $columns = [
                'subjective', 'objective', 'assessment', 'plan',
                'diagnoses', 'prescriptions', 'lab_requests',
                'imaging_requests', 'referrals', 'note_status'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('medical_notes', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // content رو به حالت قبل برگردان (not nullable)
            if (Schema::hasColumn('medical_notes', 'content')) {
                $table->text('content')->nullable(false)->change();
            }
        });
    }
};
