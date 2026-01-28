<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'السعودية', 'is_active' => true],
            ['name' => 'الإمارات العربية المتحدة', 'is_active' => true],
            ['name' => 'الكويت', 'is_active' => true],
            ['name' => 'قطر', 'is_active' => true],
            ['name' => 'البحرين', 'is_active' => true],
            ['name' => 'عُمان', 'is_active' => true],
            ['name' => 'الأردن', 'is_active' => true],
            ['name' => 'لبنان', 'is_active' => true],
            ['name' => 'سوريا', 'is_active' => true],
            ['name' => 'العراق', 'is_active' => true],
            ['name' => 'فلسطين', 'is_active' => true],
            ['name' => 'اليمن', 'is_active' => true],
            ['name' => 'مصر', 'is_active' => true],
            ['name' => 'السودان', 'is_active' => true],
            ['name' => 'ليبيا', 'is_active' => true],
            ['name' => 'تونس', 'is_active' => true],
            ['name' => 'الجزائر', 'is_active' => true],
            ['name' => 'المغرب', 'is_active' => true],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['name' => $country['name']],
                $country
            );
        }

        $this->command->info('Countries seeded successfully!');
    }
}

