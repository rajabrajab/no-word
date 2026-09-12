<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'language' => $this->language,
            // Keyed by name rather than by score: JsonResource re-indexes nested
            // arrays whose keys are all numeric, which would drop the scores.
            'answer_times' => collect($this->answerTimes())
                ->map(fn ($seconds, $score) => ['score' => $score, 'seconds' => $seconds])
                ->values()
                ->all(),
            'image' => $this->image ? asset('storage/'.$this->image) : null,
            'country' => $this->whenLoaded('country', function () {
                return new CountryResource($this->country);
            }),
        ];
    }
}
