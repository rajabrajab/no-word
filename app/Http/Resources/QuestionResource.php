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
            'media' => $this->media ? asset('storage/'.$this->media) : null,
            'media_type' => $this->media_type,
            'answer_media' => $this->answer_media ? asset('storage/'.$this->answer_media) : null,
            'answer_media_type' => $this->answer_media_type,
            'qr_code' => $this->qr_code ? asset('storage/'.$this->qr_code) : null,
            'is_answered' => isset($this->pivot) ? (bool) $this->pivot->is_answered : false,
        ];
    }
}
