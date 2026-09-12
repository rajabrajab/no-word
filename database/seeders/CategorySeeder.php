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
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'الجغرافيا',
                'description' => 'أسئلة عن الجغرافيا والبلدان والمدن',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'العلوم',
                'description' => 'أسئلة عن العلوم الطبيعية والفيزياء والكيمياء',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'الأدب',
                'description' => 'أسئلة عن الأدب العربي والعالمي والشعر',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'الرياضة',
                'description' => 'أسئلة عن الرياضة والألعاب الأولمبية',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'الفنون',
                'description' => 'أسئلة عن الفنون والرسم والموسيقى',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'التراث',
                'description' => 'أسئلة عن التراث والثقافة الشعبية',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'الدين',
                'description' => 'أسئلة دينية عن الإسلام والديانات الأخرى',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'أغاني عربية',
                'description' => 'أسئلة عن الأغاني والمطربين العرب',
                'language' => Category::LANGUAGE_AR,
            ],
            [
                'name' => 'أغاني إنجليزية',
                'description' => 'أسئلة عن الأغاني والفنانين الأجانب',
                'language' => Category::LANGUAGE_EN,
            ],
            [
                'name' => 'عام',
                'description' => 'أسئلة منوعة في مختلف المجالات',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'أمثال',
                'description' => 'أسئلة عن الأمثال الشعبية ومعانيها',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'شخصيات',
                'description' => 'أسئلة عن شخصيات مشهورة وتاريخية',
                'language' => Category::LANGUAGE_BOTH,
            ],
            [
                'name' => 'فن عربي',
                'description' => 'أسئلة عن الأفلام والمسلسلات والفن العربي',
                'language' => Category::LANGUAGE_AR,
            ],
            [
                'name' => 'فن إنجليزي',
                'description' => 'أسئلة عن الأفلام والمسلسلات الأجنبية',
                'language' => Category::LANGUAGE_EN,
            ],
            [
                'name' => 'أنمي',
                'description' => 'أسئلة عن الأنمي والشخصيات اليابانية',
                'language' => Category::LANGUAGE_BOTH,
            ],
        ];

        $countries = Country::where('is_active', true)->get();

        if ($countries->isEmpty()) {
            $this->command->warn('No active countries found. Please seed countries first.');

            return;
        }

        foreach ($countries as $country) {
            foreach ($categories as $category) {
                // Answer times are intentionally left out so the column defaults
                // (90/60/30) apply on create and admin edits survive re-seeding.
                Category::updateOrCreate(
                    [
                        'name' => $category['name'],
                        'country_id' => $country->id,
                    ],
                    [
                        'description' => $category['description'],
                        'language' => $category['language'],
                    ]
                );
            }
        }

        $this->command->info('Categories seeded successfully for all countries!');
    }
}
