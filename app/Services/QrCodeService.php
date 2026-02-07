<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generateForQuestion(Question $question): string
    {
        $url = route('question.show', ['question' => $question->id]);

        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($url);

        $path = 'qr-codes/question-' . $question->id . '.svg';
        Storage::disk('public')->put($path, $qrCode);

        return $path;
    }
}
