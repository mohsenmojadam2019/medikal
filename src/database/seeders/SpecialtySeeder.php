<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            ['name' => 'داخلی', 'slug' => 'dakheli', 'icon' => 'fa-stethoscope'],
            ['name' => 'قلب و عروق', 'slug' => 'ghalb', 'icon' => 'fa-heart'],
            ['name' => 'ارتوپدی', 'slug' => 'ortopedi', 'icon' => 'fa-bone'],
            ['name' => 'اعصاب و روان', 'slug' => 'asab', 'icon' => 'fa-brain'],
            ['name' => 'کودکان', 'slug' => 'koodakan', 'icon' => 'fa-child'],
            ['name' => 'زنان و زایمان', 'slug' => 'zanan', 'icon' => 'fa-female'],
            ['name' => 'پوست و مو', 'slug' => 'poust', 'icon' => 'fa-hand'],
            ['name' => 'چشم پزشکی', 'slug' => 'cheshm', 'icon' => 'fa-eye'],
            ['name' => 'گوش و حلق و بینی', 'slug' => 'goosh', 'icon' => 'fa-ear'],
            ['name' => 'دندانپزشکی', 'slug' => 'dandan', 'icon' => 'fa-tooth'],
            ['name' => 'فیزیوتراپی', 'slug' => 'fizio', 'icon' => 'fa-person-walking'],
            ['name' => 'تغذیه', 'slug' => 'taghzie', 'icon' => 'fa-utensils'],
            ['name' => 'روانشناسی', 'slug' => 'ravanshenasi', 'icon' => 'fa-user-check'],
            ['name' => 'طب سوزنی', 'slug' => 'souzani', 'icon' => 'fa-needle'],
            ['name' => 'پزشکی ورزشی', 'slug' => 'varzeshi', 'icon' => 'fa-dumbbell'],
        ];

        foreach ($specialties as $specialty) {
            Specialty::updateOrCreate(
                ['slug' => $specialty['slug']],
                $specialty
            );
        }
    }
}
