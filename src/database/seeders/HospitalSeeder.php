<?php

namespace Database\Seeders;

use App\Models\Ward;
use App\Models\Bed;
use App\Enums\WardTypeEnum;
use App\Enums\BedStatusEnum;
use Illuminate\Database\Seeder;

class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // WARDS
        // ============================================================

        $wards = [
            [
                'name' => 'بخش داخلی مردان',
                'code' => 'WRD-24-0001',
                'type' => WardTypeEnum::GENERAL,
                'floor' => 1,
                'capacity' => 20,
                'description' => 'بخش داخلی مخصوص آقایان',
                'is_active' => true,
            ],
            [
                'name' => 'بخش داخلی زنان',
                'code' => 'WRD-24-0002',
                'type' => WardTypeEnum::GENERAL,
                'floor' => 1,
                'capacity' => 20,
                'description' => 'بخش داخلی مخصوص بانوان',
                'is_active' => true,
            ],
            [
                'name' => 'بخش CCU',
                'code' => 'WRD-24-0003',
                'type' => WardTypeEnum::CCU,
                'floor' => 2,
                'capacity' => 10,
                'description' => 'مراقبت‌های ویژه قلبی',
                'is_active' => true,
            ],
            [
                'name' => 'بخش ICU',
                'code' => 'WRD-24-0004',
                'type' => WardTypeEnum::ICU,
                'floor' => 2,
                'capacity' => 8,
                'description' => 'مراقبت‌های ویژه',
                'is_active' => true,
            ],
            [
                'name' => 'بخش جراحی',
                'code' => 'WRD-24-0005',
                'type' => WardTypeEnum::SURGERY,
                'floor' => 3,
                'capacity' => 15,
                'description' => 'بخش جراحی عمومی',
                'is_active' => true,
            ],
            [
                'name' => 'بخش زایمان',
                'code' => 'WRD-24-0006',
                'type' => WardTypeEnum::MATERNITY,
                'floor' => 3,
                'capacity' => 12,
                'description' => 'بخش زایمان و پس از زایمان',
                'is_active' => true,
            ],
            [
                'name' => 'بخش کودکان',
                'code' => 'WRD-24-0007',
                'type' => WardTypeEnum::PEDIATRICS,
                'floor' => 4,
                'capacity' => 15,
                'description' => 'بخش مخصوص کودکان',
                'is_active' => true,
            ],
            [
                'name' => 'بخش VIP',
                'code' => 'WRD-24-0008',
                'type' => WardTypeEnum::VIP,
                'floor' => 5,
                'capacity' => 5,
                'description' => 'بخش ویژه VIP',
                'is_active' => true,
            ],
        ];

        foreach ($wards as $ward) {
            Ward::updateOrCreate(
                ['code' => $ward['code']],
                $ward
            );
        }

        $this->command->info('✅ بخش‌ها ایجاد شدند.');

        // ============================================================
        // BEDS
        // ============================================================

        $wards = Ward::all();

        foreach ($wards as $ward) {
            for ($i = 1; $i <= $ward->capacity; $i++) {
                Bed::updateOrCreate(
                    [
                        'ward_id' => $ward->id,
                        'bed_number' => (string) $i,
                    ],
                    [
                        'code' => 'BED-24-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                        'status' => $i <= 3 ? BedStatusEnum::OCCUPIED : BedStatusEnum::AVAILABLE,
                        'is_private' => $ward->type === WardTypeEnum::VIP,
                        'price_per_day' => $ward->daily_rate,
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('✅ تخت‌ها ایجاد شدند.');
        $this->command->info('✅ سیدر بستری با موفقیت انجام شد!');
    }
}
