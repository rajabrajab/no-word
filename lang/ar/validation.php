<?php

/*
|--------------------------------------------------------------------------
| رسائل التحقق من المدخلات
|--------------------------------------------------------------------------
|
| أي قاعدة غير مترجمة هنا تعود تلقائياً إلى النص الإنجليزي، فالإضافة إلى هذا
| الملف آمنة ولا تكسر شيئاً.
|
*/

return [

    'accepted' => 'يجب قبول :attribute.',
    'accepted_if' => 'يجب قبول :attribute عندما يكون :other هو :value.',
    'active_url' => ':attribute ليس رابطاً صحيحاً.',
    'after' => 'يجب أن يكون :attribute تاريخاً بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخاً بعد أو يساوي :date.',
    'alpha' => 'يجب ألا يحتوي :attribute إلا على حروف.',
    'alpha_dash' => 'يجب ألا يحتوي :attribute إلا على حروف وأرقام وشرطات.',
    'alpha_num' => 'يجب ألا يحتوي :attribute إلا على حروف وأرقام.',
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'ascii' => 'يجب ألا يحتوي :attribute إلا على رموز وحروف إنجليزية.',
    'before' => 'يجب أن يكون :attribute تاريخاً قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخاً قبل أو يساوي :date.',

    'between' => [
        'array' => 'يجب أن يحتوي :attribute بين :min و :max عنصراً.',
        'file' => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'string' => 'يجب أن يكون طول :attribute بين :min و :max حرفاً.',
    ],

    'boolean' => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'contains' => 'ينقص :attribute قيمة مطلوبة.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => ':attribute ليس تاريخاً صحيحاً.',
    'date_equals' => 'يجب أن يكون :attribute تاريخاً مساوياً لـ :date.',
    'date_format' => 'لا يطابق :attribute الصيغة :format.',
    'decimal' => 'يجب أن يحتوي :attribute على :decimal منزلة عشرية.',
    'declined' => 'يجب رفض :attribute.',
    'declined_if' => 'يجب رفض :attribute عندما يكون :other هو :value.',
    'different' => 'يجب أن يكون :attribute مختلفاً عن :other.',
    'digits' => 'يجب أن يتكون :attribute من :digits رقماً.',
    'digits_between' => 'يجب أن يتكون :attribute من عدد أرقام بين :min و :max.',
    'dimensions' => 'أبعاد الصورة :attribute غير صحيحة.',
    'distinct' => 'قيمة :attribute مكررة.',
    'doesnt_end_with' => 'يجب ألا ينتهي :attribute بأحد التالي: :values.',
    'doesnt_start_with' => 'يجب ألا يبدأ :attribute بأحد التالي: :values.',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صحيحاً.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد التالي: :values.',
    'enum' => 'القيمة المختارة لـ :attribute غير صحيحة.',
    'exists' => 'القيمة المختارة لـ :attribute غير موجودة.',
    'extensions' => 'يجب أن يكون امتداد ملف :attribute أحد التالي: :values.',
    'file' => 'يجب أن يكون :attribute ملفاً.',
    'filled' => 'يجب تعبئة :attribute.',

    'gt' => [
        'array' => 'يجب أن يحتوي :attribute على أكثر من :value عنصراً.',
        'file' => 'يجب أن يكون حجم :attribute أكبر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أكبر من :value.',
        'string' => 'يجب أن يكون طول :attribute أكبر من :value حرفاً.',
    ],

    'gte' => [
        'array' => 'يجب أن يحتوي :attribute على :value عنصراً أو أكثر.',
        'file' => 'يجب أن يكون حجم :attribute :value كيلوبايت أو أكثر.',
        'numeric' => 'يجب أن تكون قيمة :attribute :value أو أكثر.',
        'string' => 'يجب أن يكون طول :attribute :value حرفاً أو أكثر.',
    ],

    'image' => 'يجب أن يكون :attribute صورة.',
    'in' => 'القيمة المختارة لـ :attribute غير صحيحة.',
    'in_array' => 'القيمة :attribute غير موجودة ضمن :other.',
    'integer' => 'يجب أن يكون :attribute رقماً صحيحاً.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صحيحاً.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صحيحاً.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صحيحاً.',
    'json' => 'يجب أن يكون :attribute نص JSON صحيحاً.',
    'lowercase' => 'يجب أن يكون :attribute بحروف صغيرة.',

    'lt' => [
        'array' => 'يجب أن يحتوي :attribute على أقل من :value عنصراً.',
        'file' => 'يجب أن يكون حجم :attribute أصغر من :value كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute أصغر من :value.',
        'string' => 'يجب أن يكون طول :attribute أقل من :value حرفاً.',
    ],

    'lte' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :value عنصراً.',
        'file' => 'يجب أن يكون حجم :attribute :value كيلوبايت أو أقل.',
        'numeric' => 'يجب أن تكون قيمة :attribute :value أو أقل.',
        'string' => 'يجب أن يكون طول :attribute :value حرفاً أو أقل.',
    ],

    'mac_address' => 'يجب أن يكون :attribute عنوان MAC صحيحاً.',

    'max' => [
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عنصراً.',
        'file' => 'يجب ألا يزيد حجم :attribute عن :max كيلوبايت.',
        'numeric' => 'يجب ألا تزيد قيمة :attribute عن :max.',
        'string' => 'يجب ألا يزيد طول :attribute عن :max حرفاً.',
    ],

    'max_digits' => 'يجب ألا يحتوي :attribute على أكثر من :max رقماً.',
    'mimes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute ملفاً من نوع: :values.',

    'min' => [
        'array' => 'يجب أن يحتوي :attribute على :min عنصراً على الأقل.',
        'file' => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'string' => 'يجب ألا يقل طول :attribute عن :min حرفاً.',
    ],

    'min_digits' => 'يجب أن يحتوي :attribute على :min رقماً على الأقل.',
    'missing' => 'يجب ألا يكون :attribute موجوداً.',
    'multiple_of' => 'يجب أن تكون قيمة :attribute من مضاعفات :value.',
    'not_in' => 'القيمة المختارة لـ :attribute غير صحيحة.',
    'not_regex' => 'صيغة :attribute غير صحيحة.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',

    'password' => [
        'letters' => 'يجب أن تحتوي :attribute على حرف واحد على الأقل.',
        'mixed' => 'يجب أن تحتوي :attribute على حرف كبير وحرف صغير على الأقل.',
        'numbers' => 'يجب أن تحتوي :attribute على رقم واحد على الأقل.',
        'symbols' => 'يجب أن تحتوي :attribute على رمز واحد على الأقل.',
        'uncompromised' => 'ظهرت :attribute في تسريب بيانات. يرجى اختيار كلمة مرور أخرى.',
    ],

    'present' => 'يجب أن يكون :attribute موجوداً.',
    'prohibited' => 'لا يُسمح بإرسال :attribute.',
    'prohibited_if' => 'لا يُسمح بإرسال :attribute عندما يكون :other هو :value.',
    'prohibited_unless' => 'لا يُسمح بإرسال :attribute إلا إذا كان :other ضمن :values.',
    'prohibits' => 'وجود :attribute يمنع إرسال :other.',
    'regex' => 'صيغة :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'يجب أن يحتوي :attribute على المفاتيح: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عند قبول :other.',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كان :other ضمن :values.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_with_all' => 'حقل :attribute مطلوب عند وجود :values.',
    'required_without' => 'حقل :attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => 'حقل :attribute مطلوب عند عدم وجود أي من :values.',
    'same' => 'يجب أن يتطابق :attribute مع :other.',

    'size' => [
        'array' => 'يجب أن يحتوي :attribute على :size عنصراً بالضبط.',
        'file' => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'numeric' => 'يجب أن تكون قيمة :attribute :size.',
        'string' => 'يجب أن يكون طول :attribute :size حرفاً.',
    ],

    'starts_with' => 'يجب أن يبدأ :attribute بأحد التالي: :values.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'timezone' => 'يجب أن يكون :attribute منطقة زمنية صحيحة.',
    'unique' => ':attribute مستخدم من قبل.',
    'uploaded' => 'فشل رفع :attribute.',
    'uppercase' => 'يجب أن يكون :attribute بحروف كبيرة.',
    'url' => 'يجب أن يكون :attribute رابطاً صحيحاً.',
    'ulid' => 'يجب أن يكون :attribute معرّف ULID صحيحاً.',
    'uuid' => 'يجب أن يكون :attribute معرّف UUID صحيحاً.',

    /*
    |--------------------------------------------------------------------------
    | رسائل مخصصة لحقول بعينها
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'password' => [
            'confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | أسماء الحقول
    |--------------------------------------------------------------------------
    |
    | تحل محل :attribute حتى تُقرأ الرسالة بالعربية بدل اسم العمود.
    |
    */

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'number' => 'رقم الجوال',
        'country_code' => 'رمز الدولة',
        'iso_code' => 'رمز الدولة',
        'profile_image' => 'الصورة الشخصية',
        'language' => 'اللغة',
        'otp' => 'رمز التحقق',
        'code' => 'رمز التحقق',

        'categories' => 'الفئات',
        'categories.*' => 'الفئة',
        'countries' => 'الدول',
        'countries.*' => 'الدولة',
        'question_id' => 'السؤال',
        'helping_method_id' => 'وسيلة المساعدة',
        'package_id' => 'الباقة',
        'coupon_code' => 'رمز الكوبون',
        'tournament_id' => 'البطولة',

        'teams' => 'الفرق',
        'teams.*.name' => 'اسم الفريق',
        'teams.*.players_number' => 'عدد اللاعبين',
        'teams.*.avatar_id' => 'صورة الفريق',
        'team1' => 'الفريق الأول',
        'team1.name' => 'اسم الفريق الأول',
        'team1.players_number' => 'عدد لاعبي الفريق الأول',
        'team1.avatar_id' => 'صورة الفريق الأول',
        'team2' => 'الفريق الثاني',
        'team2.name' => 'اسم الفريق الثاني',
        'team2.players_number' => 'عدد لاعبي الفريق الثاني',
        'team2.avatar_id' => 'صورة الفريق الثاني',
    ],

];
