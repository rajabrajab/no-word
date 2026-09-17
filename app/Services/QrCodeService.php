<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Draw the QR code for a question's public page and store it.
     *
     * Both the encoded URL and the file name are keyed by the question's token
     * rather than its id: a file named after the id would let anyone read the
     * codes off the public disk in order and recover every token.
     */
    public function generateForQuestion(Question $question): string
    {
        if (blank($question->qr_token)) {
            $question->forceFill(['qr_token' => Question::newQrToken()])->save();
        }

        $url = route('question.show', $question);

        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($url);

        $path = 'qr-codes/'.$question->qr_token.'.svg';

        $this->forgetPreviousCode($question, $path);

        Storage::disk('public')->put($path, $qrCode);

        return $path;
    }

    /**
     * Drop the code a question used to point at, so regenerating one does not leave
     * a readable copy of the old URL behind.
     */
    private function forgetPreviousCode(Question $question, string $path): void
    {
        $previous = $question->qr_code;

        if (filled($previous) && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }
    }
}
