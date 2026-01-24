<?php

namespace Database\Seeders;

use App\Models\HelpingMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class HelpingMethodSeeder extends Seeder
{
    public function run(): void
    {
        $sourceDir = public_path('helping_methods');
        $targetDir = 'helping_methods';

        if (!Storage::disk('public')->exists($targetDir)) {
            Storage::disk('public')->makeDirectory($targetDir);
        }

        $helpingMethods = [
            [
                'name' => 'اتصال بصديق',
                'description' => 'تتيح للفريق الاتصال بصديق خارجي عبر مكالمة افتراضية
                    (صوتية أو نصية) للحصول على مساعدة في الإجابة
                    على السؤال، مع وقت محدود (30 ثانية).',
                'icon_file' => 'friend_call.png',
            ],
            [
                'name' => 'تغيير السؤال',
                'description' => 'يسمح للفريق باستبدال السؤال الحالي بسؤال جديد من
 نفس الفئة، مع استخدام واحد فقط لكل لعبة.',
                'icon_file' => 'change_qustion.png',
            ],
            [
                'name' => 'تلميح عن الإجابة',
                'description' => 'يقدم تلميحاً نصياً أو بصرياً (مثل كلمة مفتاحية أو صورة
 مرتبطة) لتوجيه الفريق نحو الإجابة الصحيحة دون
 كشفها بالكامل.',
                'icon_file' => 'anwser_hint.png',
            ],
        ];

        foreach ($helpingMethods as $method) {
            $iconPath = null;

            if (isset($method['icon_file']) && File::exists($sourceDir . '/' . $method['icon_file'])) {
                $filename = $method['icon_file'];
                $targetPath = $targetDir . '/' . $filename;

                if (Storage::disk('public')->exists($targetPath)) {
                    $this->command->info("Icon {$filename} already exists in storage, skipping copy.");
                } else {
                    $fileContents = File::get($sourceDir . '/' . $filename);
                    Storage::disk('public')->put($targetPath, $fileContents);
                    $this->command->info("Copied icon {$filename} to storage.");
                }

                $iconPath = $targetPath;
            }

            HelpingMethod::updateOrCreate(
                ['name' => $method['name']],
                [
                    'name' => $method['name'],
                    'description' => $method['description'],
                    'icon' => $iconPath,
                ]
            );

            $this->command->info("Seeded helping method: {$method['name']} with icon: " . ($iconPath ?? 'none'));
        }

        $this->command->info("Helping methods seeding completed!");
    }
}

