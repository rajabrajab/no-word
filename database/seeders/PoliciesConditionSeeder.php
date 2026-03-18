<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PoliciesConditionSeeder extends Seeder
{
    public function run(): void
    {
        $privacyPolicy = [
            [
                'title' => [
                    'en' => '1. Introduction',
                    'ar' => '1. مقدمة',
                ],
                'content' => [
                    'en' => 'We value your privacy. This policy explains how the The Big Father app collects, uses, and protects your information when you use our services to book parties and events.',
                    'ar' => 'نحن نقدر خصوصيتك. تشرح هذه السياسة كيفية قيام تطبيق The Big Father بجمع واستخدام وحماية معلوماتك عند استخدام خدماتنا لحجز الحفلات والمناسبات.',
                ],
            ],
            [
                'title' => [
                    'en' => '2. Information We Collect',
                    'ar' => '2. المعلومات التي نجمعها',
                ],
                'content' => [
                    'en' => "We collect information you provide directly, including:\n- **Personal Account Data:** Name, email address, phone number, and profile picture.\n- **Booking Data:** Party details, dates, and preferences.\n- **Third-Party Data:** If you book on behalf of a friend, we collect their name and contact details necessary to deliver the service.",
                    'ar' => "نقوم بجمع المعلومات التي تقدمها مباشرة، بما في ذلك:\n- **بيانات الحساب الشخصي:** الاسم، البريد الإلكتروني، رقم الهاتف، وصورة الملف الشخصي.\n- **بيانات الحجز:** تفاصيل الحفلة، التواريخ، والتفضيلات.\n- **بيانات الطرف الثالث:** إذا قمت بالحجز نيابة عن صديق، فإننا نجمع اسمه وتفاصيل الاتصال اللازمة لتقديم الخدمة.",
                ],
            ],
            [
                'title' => [
                    'en' => '3. Location Data Usage',
                    'ar' => '3. استخدام بيانات الموقع',
                ],
                'content' => [
                    'en' => 'Our app requires access to your precise location solely to determine the venue where the party services will be delivered. This location data is collected only when you select the booking address and is not tracked in the background.',
                    'ar' => 'يتطلب تطبيقنا الوصول إلى موقعك الدقيق فقط لتحديد المكان الذي سيتم فيه تقديم خدمات الحفلة. يتم جمع بيانات الموقع هذه فقط عند تحديد عنوان الحجز ولا يتم تتبعها في الخلفية.',
                ],
            ],
            [
                'title' => [
                    'en' => '4. Account Deletion',
                    'ar' => '4. حذف الحساب',
                ],
                'content' => [
                    'en' => 'You have the right to delete your account at any time via the in-app settings. Upon deletion, your profile, photos, and personal data will be permanently removed from our active databases. We may retain some transaction records for legal and tax purposes.',
                    'ar' => 'لديك الحق في حذف حسابك في أي وقت عبر إعدادات التطبيق. عند الحذف، ستتم إزالة ملفك الشخصي وصورك وبياناتك الشخصية بشكل دائم من قواعد بياناتنا النشطة. قد نحتفظ ببعض سجلات المعاملات لأغراض قانونية وضريبية.',
                ],
            ],
            [
                'title' => [
                    'en' => '5. Contact Us',
                    'ar' => '5. اتصل بنا',
                ],
                'content' => [
                    'en' => 'If you have any questions about this Privacy Policy or your data, please contact us via the support section in the app or email us directly.',
                    'ar' => 'إذا كان لديك أي أسئلة حول سياسة الخصوصية هذه أو بياناتك، يرجى الاتصال بنا عبر قسم الدعم في التطبيق أو مراسلتنا عبر البريد الإلكتروني مباشرة.',
                ],
            ],
            [
                'title' => [
                    'en' => '6. Data Security',
                    'ar' => '6. أمن البيانات',
                ],
                'content' => [
                    'en' => 'We implement industry-standard security measures to protect your personal information. Your data is encrypted in transit and at rest. However, please note that no method of transmission over the internet is 100% secure.',
                    'ar' => 'نحن نطبق معايير الأمان المعتمدة في الصناعة لحماية معلوماتك الشخصية. يتم تشفير بياناتك أثناء النقل وعند التخزين. ومع ذلك، يرجى ملاحظة أنه لا توجد طريقة نقل عبر الإنترنت آمنة بنسبة 100٪.',
                ],
            ],
        ];

        $termsConditions = [
            [
                'title' => ['en' => '1. Acceptance of Terms', 'ar' => '1. قبول الشروط'],
                'content' => [
                    'en' => 'By creating an account (as an Individual or Company) and using our booking services, you agree to be bound by these terms. You must verify your email address to activate your account.',
                    'ar' => 'من خلال إنشاء حساب (كفرد أو شركة) واستخدام خدمات الحجز الخاصة بنا، فإنك توافق على الالتزام بهذه الشروط. يجب عليك تأكيد عنوان بريدك الإلكتروني لتفعيل حسابك.',
                ],
            ],
            [
                'title' => ['en' => '2. Bookings and Payments', 'ar' => '2. الحجوزات والدفع'],
                'content' => [
                    'en' => 'All party details (food, price, activities) are presented before booking. We accept payments via Cash or Credit Card. By confirming a booking, you agree to pay the total amount displayed.',
                    'ar' => 'يتم عرض جميع تفاصيل الحفلة (الطعام، السعر، النشاطات) قبل الحجز. نقبل الدفع نقدًا أو عبر البطاقة الائتمانية. بتأكيد الحجز، فإنك توافق على دفع المبلغ الإجمالي المعروض.',
                ],
            ],
            [
                'title' => ['en' => '3. Cancellation and Refunds', 'ar' => '3. الإلغاء والاسترداد'],
                'content' => [
                    'en' => "You may cancel a booking through the app before the service is completed.\n- Cancellations made 24 hours before the event time are free of charge.\n- Late cancellations may be subject to a deduction fee depending on the preparations already made.",
                    'ar' => "يمكنك إلغاء الحجز من خلال التطبيق قبل اكتمال الخدمة.\n- الإلغاء قبل 24 ساعة من موعد الحفلة مجاني.\n- قد يخضع الإلغاء المتأخر لرسوم خصم اعتمادًا على التحضيرات التي تم إجراؤها بالفعل.",
                ],
            ],
            [
                'title' => ['en' => '4. User Content and Conduct', 'ar' => '4. محتوى وسلوك المستخدم'],
                'content' => [
                    'en' => 'You are responsible for the profile picture you upload. We prohibit uploading offensive, illegal, or inappropriate images. We reserve the right to suspend accounts that violate this rule.',
                    'ar' => 'أنت مسؤول عن صورة الملف الشخصي التي تقوم بتحميلها. نحن نمنع تحميل أي صور مسيئة أو غير قانونية أو غير لائقة. نحتفظ بالحق في تعليق الحسابات التي تنتهك هذه القاعدة.',
                ],
            ],
            [
                'title' => ['en' => '5. Service Delivery & Liability', 'ar' => '5. تقديم الخدمة والمسؤولية'],
                'content' => [
                    'en' => 'You must ensure the location provided is accurate. We are not liable for delays caused by incorrect details. Our liability is limited to the value of the booking service provided.',
                    'ar' => 'يجب عليك التأكد من أن الموقع المقدم دقيق. نحن لسنا مسؤولين عن التأخير الناتج عن تفاصيل غير صحيحة. مسؤوليتنا محدودة بقيمة خدمة الحجز المقدمة.',
                ],
            ],
            [
                'title' => ['en' => '6. Intellectual Property', 'ar' => '6. حقوق الملكية الفكرية'],
                'content' => [
                    'en' => 'All content included in the app, such as text, graphics, logos, and images, is the property of The Big Father company and is protected by copyright laws.',
                    'ar' => 'جميع المحتويات الموجودة في التطبيق، مثل النصوص والرسومات والشعارات والصور، هي ملك لشركة The Big Father ومحمية بموجب قوانين حقوق النشر.',
                ],
            ],
            [
                'title' => ['en' => '7. Changes to Terms', 'ar' => '7. تعديل الشروط'],
                'content' => [
                    'en' => 'We reserve the right to update these terms at any time. Users will be notified of significant changes via the app or email.',
                    'ar' => 'نحتفظ بالحق في تحديث هذه الشروط في أي وقت. سيتم إخطار المستخدمين بالتغييرات المهمة عبر التطبيق أو البريد الإلكتروني.',
                ],
            ],
            [
                'title' => ['en' => '8. Eligibility', 'ar' => '8. الأهلية'],
                'content' => [
                    'en' => 'By using our services, you represent and warrant that you are at least 18 years of age and have the legal capacity to enter into binding contracts.',
                    'ar' => 'باستخدام خدماتنا، فإنك تقر وتضمن أن عمرك لا يقل عن 18 عامًا وأنك تتمتع بالأهلية القانونية لإبرام عقود ملزمة.',
                ],
            ],
            [
                'title' => ['en' => '9. Governing Law', 'ar' => '9. القانون الحاكم'],
                'content' => [
                    'en' => 'These terms shall be governed by and construed in accordance with the laws of the country in which the company is registered. Any disputes will be resolved in the local courts.',
                    'ar' => 'تخضع هذه الشروط وتفسر وفقًا لقوانين الدولة المسجلة فيها الشركة. سيتم حل أي نزاعات في المحاكم المحلية.',
                ],
            ],
            [
                'title' => ['en' => '10. Force Majeure', 'ar' => '10. القوة القاهرة'],
                'content' => [
                    'en' => 'The company shall not be liable for any failure to perform its obligations where such failure results from any cause beyond our reasonable control, including but not limited to mechanical/electronic failure, natural disasters (storms, earthquakes), or government restrictions.',
                    'ar' => 'لا تتحمل الشركة المسؤولية عن أي فشل في أداء التزاماتها عندما ينتج هذا الفشل عن أي سبب خارج عن سيطرتنا المعقولة، بما في ذلك على سبيل المثال لا الحصر، الأعطال الميكانيكية/الإلكترونية، أو الكوارث الطبيعية (العواصف، الزلازل)، أو القيود الحكومية.',
                ],
            ],
        ];

        $this->insertPolicies('privacy_policy', $privacyPolicy);
        $this->insertPolicies('terms_and_conditions', $termsConditions);
    }

    private function insertPolicies(string $type, array $items): void
    {
        foreach ($items as $item) {
            DB::table('policies_conditions')->insert([
                'title'        => json_encode($item['title'], JSON_UNESCAPED_UNICODE),
                'content'      => json_encode($item['content'], JSON_UNESCAPED_UNICODE),
                'type'         => $type,
                'last_updated' => now()->toDateString(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
