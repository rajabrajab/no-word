<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'score' => $this->score,
            'answer_time' => $this->answerTime(),
            'question' => $this->question,
            'answer' => $this->answer,
            'hint' => $this->hint,
            'media' => $this->questionMediaUrl(),
            // Resolved rather than raw: a blank or stale column would tell the app to
            // render a voice note as a picture.
            'media_type' => $this->questionMediaType(),
            'answer_media' => $this->answerMediaUrl(),
            'answer_media_type' => $this->answerMediaType(),
            'qr_code' => $this->qr_code ? asset('storage/'.$this->qr_code) : null,
            'is_answered' => isset($this->pivot) ? (bool) $this->pivot->is_answered : false,
        ];
    }
}
