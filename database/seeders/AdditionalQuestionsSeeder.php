<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Database\Seeder;

class AdditionalQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $country1 = Country::find(1);

        if (!$country1) {
            $this->command->warn('Country with ID 1 not found. Please seed countries first.');
            return;
        }

        $category1 = Category::where('country_id', $country1->id)->orderBy('id')->first();
        $category2 = Category::where('country_id', $country1->id)->orderBy('id')->skip(1)->first();

        if (!$category1 || !$category2) {
            $this->command->warn('Categories not found for country 1. Please seed categories first.');
            return;
        }

        $this->command->info("Adding questions for Country: {$country1->name}");
        $this->command->info("Category 1: {$category1->name} (ID: {$category1->id})");
        $this->command->info("Category 2: {$category2->name} (ID: {$category2->id})");

        $category1Questions = [
            [
                'question' => 'من هو مؤسس الدولة السعودية الأولى؟',
                'answer' => 'محمد بن سعود',
                'hint' => 'كان أمير الدرعية',
                'score' => 200,
            ],
            [
                'question' => 'في أي عام تأسست الدولة السعودية الأولى؟',
                'answer' => '1744 ميلادي',
                'hint' => 'في منتصف القرن الثامن عشر',
                'score' => 200,
            ],
            [
                'question' => 'ما هي عاصمة الدولة السعودية الأولى؟',
                'answer' => 'الدرعية',
                'hint' => 'تقع في منطقة نجد',
                'score' => 200,
            ],
            [
                'question' => 'من هو الملك الذي وحد المملكة العربية السعودية؟',
                'answer' => 'الملك عبدالعزيز آل سعود',
                'hint' => 'الملك المؤسس',
                'score' => 400,
            ],
            [
                'question' => 'في أي عام تم توحيد المملكة العربية السعودية؟',
                'answer' => '1932 ميلادي',
                'hint' => 'في القرن العشرين',
                'score' => 400,
            ],
            [
                'question' => 'ما هي المعركة الشهيرة التي قادها الملك عبدالعزيز؟',
                'answer' => 'معركة الرياض',
                'hint' => 'استعاد فيها الرياض',
                'score' => 400,
            ],
            [
                'question' => 'من هو أول ملك للمملكة العربية السعودية؟',
                'answer' => 'الملك عبدالعزيز',
                'hint' => 'الملك المؤسس',
                'score' => 600,
            ],
            [
                'question' => 'ما هو نظام الحكم في المملكة العربية السعودية؟',
                'answer' => 'ملكي',
                'hint' => 'نظام وراثي',
                'score' => 600,
            ],
            [
                'question' => 'كم عدد ملوك المملكة العربية السعودية حتى الآن؟',
                'answer' => '7 ملوك',
                'hint' => 'من الملك عبدالعزيز حتى الملك سلمان',
                'score' => 600,
            ],
        ];

        $category2Questions = [
            [
                'question' => 'ما هي أكبر منطقة إدارية في المملكة العربية السعودية؟',
                'answer' => 'المنطقة الشرقية',
                'hint' => 'تطل على الخليج العربي',
                'score' => 200,
            ],
            [
                'question' => 'ما هي أعلى قمة جبلية في المملكة؟',
                'answer' => 'جبل السودة',
                'hint' => 'يقع في منطقة عسير',
                'score' => 200,
            ],
            [
                'question' => 'ما هو أطول نهر في المملكة العربية السعودية؟',
                'answer' => 'لا يوجد أنهار دائمة',
                'hint' => 'المملكة صحراوية',
                'score' => 200,
            ],
            [
                'question' => 'كم عدد المناطق الإدارية في المملكة؟',
                'answer' => '13 منطقة',
                'hint' => 'منها الرياض ومكة والشرقية',
                'score' => 400,
            ],
            [
                'question' => 'ما هي أكبر صحراء في المملكة؟',
                'answer' => 'الربع الخالي',
                'hint' => 'أكبر صحراء رملية في العالم',
                'score' => 400,
            ],
            [
                'question' => 'ما هي أعلى هضبة في المملكة؟',
                'answer' => 'هضبة نجد',
                'hint' => 'تقع في وسط المملكة',
                'score' => 400,
            ],
            [
                'question' => 'ما هو أطول وادي في المملكة؟',
                'answer' => 'وادي الرمة',
                'hint' => 'يمتد لمسافات طويلة',
                'score' => 600,
            ],
            [
                'question' => 'كم تبلغ مساحة المملكة العربية السعودية؟',
                'answer' => 'حوالي 2.15 مليون كيلومتر مربع',
                'hint' => 'أكبر دولة في شبه الجزيرة العربية',
                'score' => 600,
            ],
            [
                'question' => 'ما هي الحدود البرية للمملكة؟',
                'answer' => 'تحدها 8 دول',
                'hint' => 'منها الأردن والعراق واليمن',
                'score' => 600,
            ],
        ];

        $this->command->info("Adding questions for Category 1: {$category1->name}");
        foreach ($category1Questions as $questionData) {
            $question = Question::updateOrCreate(
                [
                    'category_id' => $category1->id,
                    'question' => $questionData['question'],
                ],
                [
                    'category_id' => $category1->id,
                    'question' => $questionData['question'],
                    'answer' => $questionData['answer'],
                    'hint' => $questionData['hint'],
                    'score' => $questionData['score'],
                ]
            );

            $this->command->info("  - Created/Updated: {$questionData['question']} (Score: {$questionData['score']})");
        }

        $this->command->info("Adding questions for Category 2: {$category2->name}");
        foreach ($category2Questions as $questionData) {
            $question = Question::updateOrCreate(
                [
                    'category_id' => $category2->id,
                    'question' => $questionData['question'],
                ],
                [
                    'category_id' => $category2->id,
                    'question' => $questionData['question'],
                    'answer' => $questionData['answer'],
                    'hint' => $questionData['hint'],
                    'score' => $questionData['score'],
                ]
            );


            $this->command->info("  - Created/Updated: {$questionData['question']} (Score: {$questionData['score']})");
        }

        $this->command->info('Additional questions seeded successfully!');
        $this->command->info("Total questions for Category 1: " . Question::where('category_id', $category1->id)->count());
        $this->command->info("Total questions for Category 2: " . Question::where('category_id', $category2->id)->count());
    }
}
