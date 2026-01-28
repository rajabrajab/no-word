<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'التاريخ',
                'description' => 'أسئلة عن التاريخ العربي والإسلامي والعالمي',
                'country_id' => null,
            ],
            [
                'name' => 'الجغرافيا',
                'description' => 'أسئلة عن الجغرافيا والبلدان والمدن',
                'country_id' => null,
            ],
            [
                'name' => 'العلوم',
                'description' => 'أسئلة عن العلوم الطبيعية والفيزياء والكيمياء',
                'country_id' => null,
            ],
            [
                'name' => 'الأدب',
                'description' => 'أسئلة عن الأدب العربي والعالمي والشعر',
                'country_id' => null,
            ],
            [
                'name' => 'الرياضة',
                'description' => 'أسئلة عن الرياضة والألعاب الأولمبية',
                'country_id' => null,
            ],
            [
                'name' => 'الفنون',
                'description' => 'أسئلة عن الفنون والرسم والموسيقى',
                'country_id' => null,
            ],
            [
                'name' => 'التراث',
                'description' => 'أسئلة عن التراث والثقافة الشعبية',
                'country_id' => null,
            ],
            [
                'name' => 'الدين',
                'description' => 'أسئلة دينية عن الإسلام والديانات الأخرى',
                'country_id' => null,
            ],
        ];

        $countries = Country::where('is_active', true)->get();

        if ($countries->isEmpty()) {
            $this->command->warn('No active countries found. Please seed countries first.');
            return;
        }

        foreach ($countries as $country) {
            foreach ($categories as $category) {
                Category::updateOrCreate(
                    [
                        'name' => $category['name'],
                        'country_id' => $country->id,
                    ],
                    [
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'country_id' => $country->id,
                    ]
                );
            }
        }

        $this->command->info('Categories seeded successfully for all countries!');
    }
}

