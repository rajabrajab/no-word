<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Category;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            $this->command->warn('No categories found. Please seed categories first.');
            return;
        }

        $questions = [
            [
                'question' => 'في أي عام وقعت معركة بدر الكبرى؟',
                'answer' => '624 ميلادي',
                'hint' => 'حدثت في السنة الثانية للهجرة',
                'score' => 200,
            ],
            [
                'question' => 'من هو الخليفة الذي بنى مدينة بغداد؟',
                'answer' => 'أبو جعفر المنصور',
                'hint' => 'كان ثاني خلفاء الدولة العباسية',
                'score' => 400,
            ],
            [
                'question' => 'متى سقطت الدولة العثمانية؟',
                'answer' => '1923 ميلادي',
                'hint' => 'بعد الحرب العالمية الأولى',
                'score' => 600,
            ],

            [
                'question' => 'ما هي أعلى قمة جبلية في الوطن العربي؟',
                'answer' => 'جبل توبقال',
                'hint' => 'يقع في دولة المغرب',
                'score' => 200,
            ],
            [
                'question' => 'ما هو أطول نهر في العالم؟',
                'answer' => 'نهر النيل',
                'hint' => 'يمر عبر عدة دول أفريقية',
                'score' => 400,
            ],
            [
                'question' => 'كم عدد الدول العربية في قارة أفريقيا؟',
                'answer' => '10 دول',
                'hint' => 'منها مصر والسودان والجزائر',
                'score' => 600,
            ],

            [
                'question' => 'ما هو الرمز الكيميائي للماء؟',
                'answer' => 'H2O',
                'hint' => 'يتكون من ذرتين هيدروجين وذرة أكسجين',
                'score' => 200,
            ],
            [
                'question' => 'كم عدد الكواكب في المجموعة الشمسية؟',
                'answer' => '8 كواكب',
                'hint' => 'بعد استبعاد بلوتو',
                'score' => 400,
            ],
            [
                'question' => 'ما هي سرعة الضوء في الفراغ؟',
                'answer' => '300,000 كيلومتر في الثانية',
                'hint' => 'حوالي 300 ألف كيلومتر',
                'score' => 600,
            ],

            [
                'question' => 'من هو شاعر النيل؟',
                'answer' => 'حافظ إبراهيم',
                'hint' => 'شاعر مصري من العصر الحديث',
                'score' => 200,
            ],
            [
                'question' => 'ما هي أشهر رواية للأديب نجيب محفوظ؟',
                'answer' => 'ثلاثية القاهرة',
                'hint' => 'تتكون من ثلاث روايات',
                'score' => 400,
            ],
            [
                'question' => 'من كتب كتاب "الأيام"؟',
                'answer' => 'طه حسين',
                'hint' => 'عميد الأدب العربي',
                'score' => 600,
            ],

            [
                'question' => 'في أي دولة أقيمت كأس العالم 2022؟',
                'answer' => 'قطر',
                'hint' => 'أول دولة عربية تستضيف البطولة',
                'score' => 200,
            ],
            [
                'question' => 'كم عدد لاعبي كرة القدم في الفريق الواحد؟',
                'answer' => '11 لاعب',
                'hint' => 'بما فيهم حارس المرمى',
                'score' => 400,
            ],
            [
                'question' => 'من هو اللاعب العربي الذي فاز بجائزة الكرة الذهبية؟',
                'answer' => 'لم يفز أي لاعب عربي',
                'hint' => 'لم يحصل أي لاعب عربي على هذه الجائزة حتى الآن',
                'score' => 600,
            ],

            [
                'question' => 'من هو فنان الخط العربي الشهير؟',
                'answer' => 'حامد الأمدي',
                'hint' => 'من أشهر خطاطي القرن العشرين',
                'score' => 200,
            ],
            [
                'question' => 'ما هي الآلة الموسيقية التي اشتهر بها عازف عربي شهير؟',
                'answer' => 'العود',
                'hint' => 'آلة وترية عربية أصيلة',
                'score' => 400,
            ],
            [
                'question' => 'من هو المطرب العربي الذي لقب بكوكب الشرق؟',
                'answer' => 'أم كلثوم',
                'hint' => 'أشهر مطربة عربية في القرن العشرين',
                'score' => 600,
            ],

            [
                'question' => 'ما هو الطبق التقليدي المشهور في السعودية؟',
                'answer' => 'الكبسة',
                'hint' => 'طبق أرز مع لحم',
                'score' => 200,
            ],
            [
                'question' => 'ما هي الرقصة الشعبية المشهورة في الخليج؟',
                'answer' => 'العرضة',
                'hint' => 'رقصة تراثية بالسيوف',
                'score' => 400,
            ],
            [
                'question' => 'ما هو المبنى الأثري الشهير في الأردن؟',
                'answer' => 'البتراء',
                'hint' => 'إحدى عجائب الدنيا السبع',
                'score' => 600,
            ],

            [
                'question' => 'كم عدد أركان الإسلام؟',
                'answer' => '5 أركان',
                'hint' => 'الشهادتان والصلاة والصوم والزكاة والحج',
                'score' => 200,
            ],
            [
                'question' => 'ما هي أول سورة نزلت في القرآن الكريم؟',
                'answer' => 'سورة العلق',
                'hint' => 'بدأت بـ "اقرأ باسم ربك الذي خلق"',
                'score' => 400,
            ],
            [
                'question' => 'كم عدد سور القرآن الكريم؟',
                'answer' => '114 سورة',
                'hint' => 'منها المكية والمدنية',
                'score' => 600,
            ],
        ];

        $categoryIndex = 0;
        $categoryNames = [
            'التاريخ' => 0,
            'الجغرافيا' => 3,
            'العلوم' => 6,
            'الأدب' => 9,
            'الرياضة' => 12,
            'الفنون' => 15,
            'التراث' => 18,
            'الدين' => 21,
        ];

        foreach ($categories as $category) {
            $categoryName = $category->name;

            if (isset($categoryNames[$categoryName])) {
                $startIndex = $categoryNames[$categoryName];
                $endIndex = $startIndex + 3;

                for ($i = $startIndex; $i < $endIndex && $i < count($questions); $i++) {
                    Question::updateOrCreate(
                        [
                            'category_id' => $category->id,
                            'question' => $questions[$i]['question'],
                        ],
                        [
                            'category_id' => $category->id,
                            'question' => $questions[$i]['question'],
                            'answer' => $questions[$i]['answer'],
                            'hint' => $questions[$i]['hint'],
                            'score' => $questions[$i]['score'],
                        ]
                    );
                }
            }
        }

        $this->command->info('Questions seeded successfully!');
    }
}

